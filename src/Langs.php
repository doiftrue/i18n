<?php

/**
 * @property-read array  $langs_data
 * @property-read string $langs_regex
 * @property-read string $lang
 */
class Langs {

	/**
	 * All active  langs data.
	 * @var array[]
	 */
	private static array $langs_data;

	/**
	 * Regular for ru|en, to simplify.
	 */
	private static string $langs_regex = '';

	/**
	 * Current language ru|en|... Default is empty string - not defined.
	 */
	private static string $lang = '';

	/**
	 * @return self
	 */
	public static function instance(): self {
		static $instance;
		$instance || $instance = new self();

		return $instance;
	}

	public function __isset( $name ){
		return false;
	}

	public function __set( $name, $val ){
		return null;
	}

	public function __get( $name ){

		if( property_exists( __CLASS__, $name ) ){
			return self::${$name};
		}

		return null;
	}

	private function __construct(){
	}

	public function run(): void {

		// init options
		i18n_opt();

		// set $langs_data
		$langs_data = require __DIR__ . '/langs-data.php';

		// добавим flag_path для всех активных языков
		foreach( i18n_opt()->active_langs as $lang ){
			self::$langs_data[ $lang ] = $langs_data[ $lang ];
			self::$langs_data[ $lang ]['flag_path'] = str_replace( I18N_URL, I18N_PATH, $langs_data[ $lang ]['flag'] );
		}

		$this->init();
	}

	public function init(): void {

		// before anything else
		self::$langs_regex = implode( '|', array_keys( self::$langs_data ) );

		// Set current lang self::$lang, locale and cookies.
		$this->set_lang();

		// After setting the locale...
		//load_muplugin_textdomain( 'i18n', plugin_basename(I18N_PATH) .'/lang' );

		I18n_Rewrite_Rules::early_init();
		I18n_Permalink_Fixer::early_init();
		//__NOTUSED__I18n_Permastruct_fixer::early_init();

		add_action( 'template_redirect', [ __CLASS__, 'lang_redirect' ] );
	}

	/**
	 * Detects the current language and sets it.
	 * Sets locale and cookies.
	 */
	private function set_lang(): void {

		if(
			// Skip weird URLs: css, js, php, with queries, from wp, etc.
			! preg_match( '~[.]|wp-~', $_SERVER['REQUEST_URI'] ) // ?= it cannot be used - these are query parameters
			&&
			// determine by URL
			preg_match( '~^' . i18n_opt()->URI_prefix . '/(' . self::$langs_regex . ')(/|$)~', $_SERVER['REQUEST_URI'], $mm )
		){
			self::$lang = $mm[1];
		}
		elseif( ! empty( $_GET['lang'] ) ){
			self::$lang = trim( $_GET['lang'] );
		}
		elseif( ! empty( $_POST['lang'] ) ){
			self::$lang = trim( $_POST['lang'] );
		}
		// NOTE: wp_get_current_user() determined after `plugins_loaded`.
		elseif(
			function_exists( 'get_current_hb_user' )
			&& ( $cuser = get_current_hb_user() )
			&& ( $user_lang = $cuser->user_lang ) )
		{
			self::$lang = $user_lang;
		}
		// NOTE: wp_get_current_user() determined after `plugins_loaded`.
		elseif(
			0 // skip
			// TODO identify WP user by cookies (before plugins_loaded) WP is_user_logged_in
			&& ( $uid = 0 )
			&& ( $user_lang = get_user_meta( $uid, 'user_lang', true ) )
		){
			self::$lang = $user_lang;
		}
		elseif( ! empty( $_COOKIE['lang'] ) ){
			self::$lang = $_COOKIE['lang'];
		}

		if( ! self::$lang || ! $this->is_lang_active( self::$lang ) ){
			self::$lang = i18n_opt()->default_lang;
		}

		// locale, for front and ajax
		if( ! is_admin() || wp_doing_ajax() ){

			$lang_locale = $this->is_lang_active( self::$lang ) ? self::$langs_data[ self::$lang ]['locale'] : '';

			if( $lang_locale && $lang_locale !== get_locale() ){

				add_filter( 'locale', function( $locale ) use ( $lang_locale ){
					return $lang_locale;
				} );
			}
		}

		// language cookie
		if( ! is_admin() && ! wp_doing_ajax() ){

			if( I18N_IS_MUPLUG_INSTALL ){
				// At the 'muplugins_loaded' hook, the cookie constants such as COOKIE_DOMAIN have not yet been defined.
				add_action( 'plugins_loaded', [ __CLASS__, 'set_cookie' ]);

				// later, because to update the current user we need different functions, which do not yet exist before `plugins_loaded`
				add_action( 'plugins_loaded', [ __CLASS__, 'update_user_lang' ]);
			}
			else {
				self::set_cookie();
				self::update_user_lang();
			}
		}

	}

	/**
	 * Checks if specified lang is one of the active langs.
	 *
	 * @param string $lang  Lang to check or current self::$lang.
	 *
	 * @return bool
	 */
	public function is_lang_active( string $lang = '' ): bool {

		return isset( self::$langs_data[ $lang ?: self::$lang ] );
	}

	/**
	 * Sets the language cookie.
	 */
	public static function set_cookie(): void {

		if( empty( $_COOKIE['lang'] ) || $_COOKIE['lang'] !== self::$lang ){

			setcookie( 'lang', self::$lang, ( time() + DAY_IN_SECONDS * 365 ), COOKIEPATH, COOKIE_DOMAIN );
			$_COOKIE['lang'] = self::$lang;
		}
	}

	public static function update_user_lang(): void {

		if( function_exists( 'get_current_hb_user' ) ){
			$cuser = get_current_hb_user();
		}
		else {
			$cuser = is_user_logged_in() ? wp_get_current_user() : false;
		}

		if( ! $cuser ){
			return;
		}

		if( $cuser->user_lang !== self::$lang ){

			if( function_exists( 'update_hb_user' ) ){
				update_hb_user( [ 'ID' => $cuser->ID, 'user_lang' => self::$lang ] );
			}
			// update with cache cleanup
			else {
				update_user_meta( $cuser->ID, 'user_lang', self::$lang );
			}
		}
	}

	/**
	 * Redirects to the language URL, if lang is not in the URL.
	 * Only for requests with a template - `template_redirect`.
	 */
	public static function lang_redirect(): void {

		$URI = $_SERVER['REQUEST_URI'];

		// Remove the prefix, if necessary. To make it easier to check
		if( i18n_opt()->URI_prefix ){
			$URI = substr( $URI, strlen( i18n_opt()->URI_prefix ) );
		}

		/**
		 * Allows to disble redirect.
		 *
		 * @param bool   $disable_redirect
		 * @param string $URI               Current $_SERVER['REQUEST_URI'].
		 */
		if( apply_filters( 'disable_lang_redirect', false, $URI ) ){
			return;
		}

		$URI_parts = wp_parse_url( $URI );

		// do nothing for home (if it needs)
		if(
			! i18n_opt()->home_page_add_prefix
		    &&
		    (
		        '/' === $URI_parts['path']
				|| str_starts_with( $URI_parts['path'], '/page/' )
		    )
		){
			return;
		}

		// exceptions
		if(
			$URI === '/robots.txt'
			|| preg_match( '~^/(wp-sitemap|sitemap)~', $URI_parts['path'] )
			// /wp-json/
			|| preg_match( sprintf( '~/%s~', rest_get_url_prefix() ), $URI_parts['path'] )
		){
			return;
		}

		// language is already set
		if( preg_match( sprintf( '~^/(%s)(/|$)~', self::$langs_regex ), $URI_parts['path'] ) ){
			return;
		}

		// redirect to default or current language
		$new_url = home_url( ( self::$lang ?: i18n_opt()->default_lang ) . $URI );

		wp_safe_redirect( $new_url, 301 );
		exit;
	}

}
