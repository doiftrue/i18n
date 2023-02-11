<?php

class I18n_Rewrite_Rules {

	private function __construct(){}

	public static function early_init(): void {

		add_filter( 'rewrite_rules_array', [ __CLASS__, 'global_rules_correction' ], PHP_INT_MAX );

		// fix permalinks
		add_filter( 'pre_post_link', [ __CLASS__, 'add_lang_prefix_tag' ], 10, 3 );
	}

	public static function init(): void {

		$hook_name = is_admin() ? 'setup_theme' : 'wp';
		add_filter( $hook_name, [ __CLASS__, '_do_parse_request' ], 0 );
		//add_filter( 'init', array( __CLASS__, 'do_parse_request'), PHP_INT_MAX );

		// add rw tag + query var
		add_rewrite_tag( '%lang%', '('. Langs()->langs_regex .')' );

		foreach (
			[
				'post_link',
				'post_type_link',
				'page_link',
				'attachment_link',
				'search_link',
				'post_type_archive_link',
				'year_link',
				'month_link',
				'day_link',
				'feed_link',
				'author_link',
				'term_link',
				'category_feed_link',
				'term_feed_link',
				'taxonomy_feed_link',
				'author_feed_link',
				'search_feed_link',
				'post_type_archive_feed_link'
			] as $filter_name
		){
			add_filter( $filter_name, [ __CLASS__, 'replacere_lang_tag_permalink' ], 11, 3 );
		}

		if( i18n_opt()->process_home_url ){
			add_filter( 'home_url',         [ __CLASS__, 'fix_home_url' ], 10, 2 );
			add_filter( 'network_home_url', [ __CLASS__, 'fix_home_url' ], 10, 2 );
		}

		add_filter( 'mod_rewrite_rules', [ __CLASS__, 'fix_home_url_in_mod_rewrite_rules' ] );
	}

	/**
	 * Remove `en/` from `RewriteBase /en/` or RewriteRule . `/en/index.php [L]`.
	 */
	public static function fix_home_url_in_mod_rewrite_rules( string $rules ): string {
		return preg_replace( '~/('. Langs()->langs_regex .')/~', '/', $rules );
	}

	public static function fix_home_url( $url, $path ){

		// language has not yet been determined
		if( ! current_lang() ){
			return $url;
		}

		/**
		 * We need the URL to change only after the main query, because this
		 * function should return the result without changes. (just for the frontend).
		 * @see https://wp-kama.ru/function/WP::parse_request
		 */
		if( ! did_action( 'parse_request' ) && ! is_admin() && ! wp_doing_ajax() ){
			return $url;
		}

		$trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 6 ); // fast - 0.025sec for 50k iterations

		// Don't add LANG prefix if home_url() called from:
		if(
			// rest_url()
			'get_rest_url' === $trace[4]['function']
			||
			// WP_Rewrite::rewrite_rules()
			// robots.txt removed from revrite rules
			'rewrite_rules' === $trace[5]['function']
		){
			return $url;
		}

		// wp-json exception
		if( false !== strpos( $url, '/' . rest_get_url_prefix() . '/' ) ){
			return $url;
		}

		// the language is specified in $path - don't do anything.
		if( $path && preg_match( '~^' . i18n_opt()->URI_prefix . '/?(?:' . Langs()->langs_regex . ')/~', $path ) ){
			return $url;
		}

		// Replace '%lang%' tag
		if( false !== strpos( $url, '%lang%' ) ){
			return self::replacere_lang_tag_permalink( $url );
		}

		// add mandatory language for homepage
		if( ! $path || $path === '/' ){
			return untrailingslashit( $url ) . '/' . current_lang() . '/';
		}

		// path is specified, but there is no language in it.
		return preg_replace(
			'~(https?://[^/]+)(' . i18n_opt()->URI_prefix . '/)(?!(' . Langs()->langs_regex . ')/)~',
			'\1\2' . current_lang() . '/',
			$url
		);
	}

	/**
	 * Hooks callback function.
	 *
	 * @param string  $permalink The post's permalink.
	 * @param WP_Post $post      The post in question.
	 * @param bool    $leavename Whether to keep the post name.
	 *
	 * @return string
	 */
	public static function replacere_lang_tag_permalink( $permalink, $post = 0, $leavename = false ) {
		$lang = current_lang();

		// find the default post language via a function you have created to
		// determine the default language url. this could be based on the current
		// language the user has selected on the frontend, or based on the current
		// url, or based on the post itself. it is up to you
		if( $post ){
			$lang = current_lang();
		}

		// once you have the default language, it is a simple search and replace
		return str_replace( '%lang%', $lang, $permalink );
	}

	public static function _do_parse_request( $cur ){

		self::correct_page_permastruct();
		self::correct_author_permastruct();
		self::correct_extra_permastructs();

		return $cur;
	}

	protected static function correct_page_permastruct(): void {
		global $wp_rewrite;

		if( empty( $wp_rewrite->permalink_structure ) ){
			$wp_rewrite->page_structure = '';
			return;
		}

		$wp_rewrite->page_structure = self::add_lang_prefix_tag( $wp_rewrite->root . '%pagename%' );
	}

	protected static function correct_author_permastruct(): void {
		global $wp_rewrite;

		if( empty( $wp_rewrite->permalink_structure ) ){
			$wp_rewrite->author_structure = '';
			return;
		}

		$wp_rewrite->author_structure = self::add_lang_prefix_tag( $wp_rewrite->front . $wp_rewrite->author_base . '/%author%' );
	}

	protected static function correct_extra_permastructs(): void {
		global $wp_rewrite;

		foreach( $wp_rewrite->extra_permastructs as & $val ){
			$val['struct'] = self::add_lang_prefix_tag( $val['struct'] );
		}
		unset( $val );
	}

	public static function add_lang_prefix_tag( $struct ): string {

		$struct = ltrim( $struct, '/' );
		$struct = preg_replace( '~^%lang%/?~', '', $struct );

		return "/%lang%/$struct";
	}

	/**
	 * Replaces all overwriting rules, all at once.
	 */
	public static function global_rules_correction( array $rules ): array {

		$new_rules = [];

		// add prefix for all rules
		foreach( $rules as $rule => $query ){

			// skip
			if(
				preg_match( '~^(robots|favicon)~', $rule )
				||
				preg_match( '~^\^~', $rule ) // TODO consider list all strings to skip separatelly
			){
				$new_rules[ $rule ] = $query;

				continue;
			}

			// add prefix
			$query = preg_replace_callback( '~matches\[(\d+)\]~', static function( $mm ) {
				return 'matches[' . ( (int) $mm[1] + 1 ) . ']';
			}, $query );

			$new_rules[ '(' . Langs()->langs_regex . ")/$rule" ] = $query;
		}

		// home
		if( i18n_opt()->process_home_url ){
			//$new_rules = [ '^(' . Langs()->langs_regex . ')/?$' => 'index.php?lang=$matches[1]' ] + $new_rules;
			$new_rules = [ '^(' . Langs()->langs_regex . ')/?$' => 'index.php' ] + $new_rules;
		}

		return $new_rules;
	}

}



