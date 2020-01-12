<?php
/*
if(!is_admin()) add_action('get_header', 'debug_rewrite', 90);
function debug_rewrite(  ){
	global $wp_rewrite;
	global $wp_query;

	die( print_r($wp_query) );
	die( print_r($wp_rewrite) );
}
//*/

/*
add_filter( 'query_vars', function( $public_query_vars ){
	die( print_r($public_query_vars) );
	return $public_query_vars;
} );
//*/

/*
add_filter('pre_get_posts', function($query){
	die( print_r($query) );
	unset( $query->query['lang'], $query->query_vars['lang'] );
});
//*/

/*
add_filter( 'request', function($query){
	die( print_r($query) );
	unset( $query->query['lang'], $query->query_vars['lang'] );
} );
//*/

/*
add_filter( 'pre_handle_404', function($false, $wp_query){
	die( print_r($wp_query) );
}, 10, 2 );
//*/



// вешается на init
// на базе класса (переделано почти все...) - http://stackoverflow.com/questions/20754505/wordpress-add-rewrite-tag-add-rewrite-rule-and-post-link/20755049
class I18n_Rewrite_Rules {

	static $leave_origin_rules = false; // оставить в ЧПУ оригинальные правила?

	static function init(){

		$hook_name = is_admin() ? 'setup_theme' : 'wp' /*'do_parse_request'*/;
		add_filter( $hook_name, [ __CLASS__, 'do_parse_request' ], 0 );
		//add_filter( 'init', array( __CLASS__, 'do_parse_request'), PHP_INT_MAX );

		// add new rw rule
		if( is_admin() )
			add_filter( 'rewrite_rules_array', [ __CLASS__, 'global_rules_correction' ], PHP_INT_MAX, 1);

		// add rw tag + query var
		add_rewrite_tag( '%lang%', '('. Langs::$langs_regex .')' );

		// fix permalinks
		add_filter( 'pre_post_link', [ __CLASS__, 'add_lang_prefix_tag' ], 10, 3);

		foreach ( [
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
		] as $filter_name )
			add_filter( $filter_name, [ __CLASS__, 'replacere_lang_tag_permalink' ], 11, 3 );

		add_filter( 'home_url',         [ __CLASS__, 'fix_home_url' ], 10, 2 );
		add_filter( 'network_home_url', [ __CLASS__, 'fix_home_url' ], 10, 2 );

		// удалим `en/` из `RewriteBase /en/` или RewriteRule . `/en/index.php [L]`
		add_filter( 'mod_rewrite_rules', [ __CLASS__, 'fix_home_url_in_mod_rewrite_rules' ] );
	}

	static function fix_home_url_in_mod_rewrite_rules( $rules ){
		// удалим `en/` из `RewriteBase /en/` или RewriteRule . `/en/index.php [L]`
		return preg_replace( '~/(?:'. Langs::$langs_regex .')/~', '/', $rules );
	}

	static function fix_home_url( $url, $path ){

		// язык еще не определен
		if( ! Langs::$lang )
			return $url;

		// нужно чтобы URL изменялся после основного запроса, потому что в нем эта функция должна вернуть результат без изменений...
		// справедливо только для фронта.
		// см https://wp-kama.ru/function/WP::parse_request
		if( ! did_action('parse_request') && ! is_admin() && ! wp_doing_ajax() )
			return $url;

		// исключение для wp-json
		if( false !== strpos( $url, '/wp-json/' ) )
			return $url;

		// в $path указан язык - ничего не делаем...
		if( $path && preg_match('~^'. Langs::$URI_prefix .'/?(?:'. Langs::$langs_regex .')/~', $path) )
			return $url;

		// заменяе тег '%lang%'
		if( false !== strpos( $url, '%lang%' ) )
			return self::replacere_lang_tag_permalink( $url );

		// добавим обязательный язык для главной
		if( ! $path || $path === '/' )
			return untrailingslashit( $url ) .'/'. Langs::$lang .'/';

		// указан путь, но нет языка в нем...
		if( $path )
			return preg_replace( '~(https?://[^/]+)('. Langs::$URI_prefix .'/)(?!('. Langs::$langs_regex .')/)~', '\1\2'. Langs::$lang .'/' , $url );

		return $url;
	}

	static function replacere_lang_tag_permalink( $permalink, $post = 0, $leavename = false ) {
		$lang = Langs::$lang;

		// find the default post language via a function you have created to
		// determine the default language url. this could be based on the current
		// language the user has selected on the frontend, or based on the current
		// url, or based on the post itself. it is up to you
		if( $post )
			$lang = Langs::$lang;

		// once you have the default language, it is a simple search and replace
		return str_replace( '%lang%', $lang, $permalink );
	}

	static function do_parse_request( $cur ){

		self::get_page_permastruct();
		self::get_author_permastruct();
		self::correct_extras();

		return $cur;
	}

	protected static function get_page_permastruct(){
		global $wp_rewrite;

		if ( empty($wp_rewrite->permalink_structure) )
			return $wp_rewrite->page_structure = '';

		return $wp_rewrite->page_structure = self::add_lang_prefix_tag( $wp_rewrite->root . '%pagename%' );
	}

	protected static function get_author_permastruct(){
		global $wp_rewrite;

		if ( empty($wp_rewrite->permalink_structure) )
			return $wp_rewrite->author_structure = '';

		return $wp_rewrite->author_structure = self::add_lang_prefix_tag( $wp_rewrite->front . $wp_rewrite->author_base . '/%author%');
	}

	protected static function correct_extras(){
		global $wp_rewrite;

		foreach ( $wp_rewrite->extra_permastructs as $k => & $val )
			$val['struct'] = self::add_lang_prefix_tag( $val['struct'] );
	}

	static function add_lang_prefix_tag( $struct ){
		$struct = ltrim( $struct, '/' );
		$struct = preg_replace('~^%lang%/?~', '', $struct );
		return '/%lang%/'. $struct;
	}

	## заменяет все правила перезаписи, разом...
	static function global_rules_correction( $rules ){

		$new_rules = array();

		foreach ( $rules as $rule => $query ){
			// исключения
			if(
				preg_match('~^robots~', $rule ) ||
				preg_match('~^\^~', $rule )
			){
				$new_rules[ $rule ] = $query;
			}
			// префикс во все правила...
			else {
				$query = preg_replace_callback('~matches\[([0-9]+)\]~', function($mm){
					return 'matches['. ( intval($mm[1]) + 1 ) .']';
				} , $query );
				//$query = str_replace('index.php?', 'index.php?lang=$matches[1]&', $query );
				//$query = str_replace('&&', '&', $query );
				//$query = rtrim( $query, '&' );

				$new_rules[ '('. Langs::$langs_regex .')/' . $rule ] = $query;
			}

		}

		// главная
		//$new_rules = array( '^('. Langs::$langs_regex .')/?$' => 'index.php?lang=$matches[1]' ) + $new_rules;
		$new_rules = [ '^(' . Langs::$langs_regex . ')/?$' => 'index.php' ] + $new_rules;

		return $new_rules;
	}


}



