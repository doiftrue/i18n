<?php

class I18n_Permalink_Fixer {

	public static function early_init(): void {

//		add_filter( 'pre_post_link', [ __CLASS__, 'add_lang_tag_prefix' ], 0, 3 );

		add_action( 'init', [ __CLASS__, 'init' ], 0 );
	}

	public static function init(): void {

		foreach (
			[
				// posts
				'post_link',              // $url, WP_Post $post, bool $leavename
				'page_link',              // $url, int $post_id, bool $sample
				'post_type_link',         // $url, WP_Post $post, bool $leavename, bool $sample
				'attachment_link',        // $url, int $attach_id
				'post_type_archive_link', // $url, string $post_type
				// terms
				'term_link',              // $url, WP_Term $term, string $taxonomy
				// others
				'search_link',            // $url, string $search_query
				'author_link',            // $url, int $author_id, string $nicename
				// date
				'year_link',              // $url, int $year
				'month_link',             // $url, int $year, int $month
				'day_link',               // $url, int $year, int $month, int $day
				// feeds
				'feed_link',                  // $url, string $feed_type
				'the_feed_link',              // $url, string $feed_type
				'category_feed_link',         // $url, string $feed_type
				'tag_feed_link',              // $url, string $feed_type
				'taxonomy_feed_link',         // $url, string $feed_type, string $taxonomy
				'author_feed_link',           // $url, string $feed_type
				'search_feed_link',           // $url, string $feed_type, $search_type - 'posts' or 'comments'
				'post_type_archive_feed_link' // $url, string $feed_type
			] as $filter_name
		){
			// TODO add lang pleceholder and then replace it
			// Simplify fix_home_url() to add prefix only if $path parameter is empty - all
			// known urls in this case will be processed separatelly - prefix will be added separatelly for oll known urls
			// add_filter( $filter_name, [ __CLASS__, 'add_lang_tag_prefix_to_url' ], 49, 3 );
			add_filter( $filter_name, [ __CLASS__, 'replace_permalink_lang_placeholder' ], 50, 3 );
		}

		add_filter( 'home_url',         [ __CLASS__, 'fix_home_url' ], 10, 2 );
		add_filter( 'network_home_url', [ __CLASS__, 'fix_home_url' ], 10, 2 );
	}

	public static function fix_home_url( $url, $path ){

		$cur_lang = current_lang();

		/**
		 * We need the URL to change only after the main query, because this
		 * function should return the result without changes. (just for the frontend).
		 * @see https://wp-kama.ru/function/WP::parse_request
		 */
		if( ! did_action( 'parse_request' ) && ! is_admin() && ! wp_doing_ajax() ){
			return $url;
		}

		if( self::is_skip_fix_home_url( $url, $path ) ){
			return $url;
		}

		// Replace '%lang%' tag
		if( false !== strpos( $url, '%lang%' ) ){
			return self::replace_permalink_lang_placeholder( $url, 'fix_home_url' );
		}

		// add required language prefix for homepage
		if( ! $path || $path === '/' ){
			if( ! i18n_opt()->home_page_add_prefix ){
				return $url;
			}

			return untrailingslashit( $url ) . "/$cur_lang/";
		}

		// path is specified, but there is no language prefix in it - add it.
		$url = preg_replace(
			sprintf( '~(https?://[^/]+)(%s/)(?!(%s)/)~', i18n_opt()->URI_prefix, Langs()->langs_regex ),
			"\\1\\2$cur_lang/",
			$url
		);

		return apply_filters( 'i18n__fix_home_url', $url, $path );
	}

	public static function is_skip_fix_home_url( $url, $path ): bool {

		$trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 6 ); // fast - 0.025sec for 50k iterations

		// Don't add LANG prefix if home_url() called from:
		if(
			// rest_url()
			'get_rest_url' === ( $trace[4]['function'] ?? '' )
			||
			// WP_Rewrite::rewrite_rules()
			// robots.txt removed from revrite rules
			'rewrite_rules' === ( $trace[5]['function'] ?? '' )
		){
			return true;
		}

		// Don't add prefix for default wp-sitemap pages
		// TODO add support for other languages sitemaps
		if( 0 === strpos( $path, '/wp-sitemap' ) ){
			return true;
		}

		// skip url to /wp-content
		if( 0 === strpos( $url, content_url() ) ){
			return true;
		}

		// wp-json exception
		if( false !== strpos( $url, sprintf( '/%s/', rest_get_url_prefix() ) ) ){
			return true;
		}

		// the language is specified in $path - don't do anything.
		if( $path && preg_match( sprintf( '~^%s/?(%s)/~', i18n_opt()->URI_prefix, Langs()->langs_regex ), $path ) ){
			return true;
		}

		// skip if it's url to file
		static $file_ext_regex;
		$file_ext_regex || $file_ext_regex = implode( '|', array_keys( wp_get_mime_types() ) );
		if( preg_match( "~\.($file_ext_regex)$~", $url ) ){
			return true;
		}

		return apply_filters( 'i18n__is_skip_fix_home_url', false, $url, $path );
	}

	/**
	 * Hooks callback function.
	 *
	 * @param string $url      The permalink of post|page|post type|date archive|search page etc...
	 * @param mixed  ...$args  Additional parameters passed to the function - depends on what hook is used.
	 */
	public static function replace_permalink_lang_placeholder( string $url, ...$args ): string {

		$filter_name = current_filter();
		$lang = current_lang();

		// wp post-like objects - any post types or attachments.
		if( preg_match( '/^(?:post_link|page_link|post_type_link|attachment_link)$/', $filter_name ) ){

			/**
			 * Allow to change language value before replace `%lang%` placeholder in post permalink.
			 *
			 * @param string  $lang  Current language (ru, en).
			 * @param WP_Post $post  Post object (including attachment).
			 * @param string  $url   URL.
			 */
			$lang = apply_filters( 'i18n__post_url__lang_tag_value', $lang, get_post( $args[0] ), $url );
		}
		elseif( 'term_link' === $filter_name ){

			/**
			 * Allow to change language value before replace `%lang%` placeholder in term permalink.
			 *
			 * @param string  $lang  Current language (ru, en).
			 * @param WP_Term $term  WP Term object.
			 * @param string  $url   URL.
			 */
			$lang = apply_filters( 'i18n__term_url__lang_tag_value', $lang, $args[0], $url );
		}
		elseif( false !== strpos( $filter_name, 'feed_' ) ){

			/**
			 * Allow to change language value before replace `%lang%` placeholder in feed permalink.
			 *
			 * @param string $lang       Current language (ru, en).
			 * @param string $feed_type  Eg: 'rss', 'atom'.
			 * @param string $url        URL.
			 */
			$lang = apply_filters( 'i18n__feed_url__lang_tag_value', $lang, $args[0], $url );
		}

		/**
		 * Allow to change language value before replace `%lang%` placeholder in other permalinks.
		 *
		 * @param string $lang         Current language (ru, en).
		 * @param string $filter_name  Current using filter name.
		 * @param array  $args         Additional parameters passed to the function - depends on what hook is used.
		 * @param string $url          URL.
		 */
		$lang = apply_filters( 'i18n__url__lang_tag_value', $lang, $filter_name, $args, $url );

		return str_replace( '%lang%', $lang, $url );
	}

	public static function add_lang_tag_prefix( $struct ): string {

		$struct = ltrim( $struct, '/' );
		$struct = preg_replace( '~^%lang%/?~', '', $struct );

		return "/%lang%/$struct";
	}

}
