<?php

/**
 * @return Langs
 */
function Langs(): Langs {
	return Langs::instance();
}

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
	private static $langs_data;

	/**
	 * Regular for ru|en, to simplify.
	 * @var string
	 */
	private static $langs_regex = '';

	/**
	 * Current language ru|en|... Default is empty string - not defined.
	 * @var string
	 */
	private static $lang = '';

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

	public function __set( $name, $val ){}

	public function __get( $name ){

		if( property_exists( __CLASS__, $name ) ){
			return self::${$name};
		}

		return null;
	}

	private function __construct(){

		// init options
		i18n_opt();

		// set $langs_data
		$langs_data = require __DIR__ . '/langs-data.php';

		// добавим flag_path для всех активных языков
		foreach( i18n_opt()->active_langs as $lang ){
			self::$langs_data[ $lang ] = $langs_data[ $lang ];
			self::$langs_data[ $lang ]['flag_path'] = str_replace( I18N_URL, I18N_PATH, $langs_data[ $lang ]['flag'] );
		}

	}

	public function init(): void {

		// раньше всех
		self::$langs_regex = implode( '|', array_keys( self::$langs_data ) );

		// Set current lang self::$lang, locale and cookies.
		$this->set_lang();

		// после установки локали...
		//load_muplugin_textdomain( 'i18n', plugin_basename(I18N_PATH) .'/lang' );

		I18n_Rewrite_Rules::early_init();

		add_action( 'init', [ 'I18n_Rewrite_Rules', 'init' ], 0 );

		add_action( 'template_redirect', [ __CLASS__, 'lang_redirect' ] );
	}

	/**
	 * Определяет текущий язык и устанавливает его. Устанавливает локаль и куки.
	 */
	private function set_lang(): void {

		// установим self::$lang
		if(
			// пропускаем странные URL: на css, js, php, с запросами, от wp и т.д.
			! preg_match( '~[.]|wp-~', $_SERVER['REQUEST_URI'] ) // ?= юзать нельзя - это параметры запроса
			&&
			// определяем по URL
			preg_match( '~^'. i18n_opt()->URI_prefix .'/('. self::$langs_regex .')(/|$)~', $_SERVER['REQUEST_URI'], $mm )
		){
			self::$lang = $mm[1];
		}
		elseif( ! empty($_GET['lang']) ){
			self::$lang = trim( $_GET['lang'] );
		}
		elseif( ! empty($_POST['lang']) ){
			self::$lang = trim( $_POST['lang'] );
		}
		// wp_get_current_user() определяется после `plugins_loaded`
		elseif( function_exists('get_current_hb_user') && ($cuser = get_current_hb_user()) && ($user_lang = $cuser->user_lang) ){
			self::$lang = $user_lang;
		}
		elseif(
			0 // skip
			// TODO определить WP юзера по кукам (до plugins_loaded) WP is_user_logged_in
			&& ( $uid = 0 )
			&& ( $user_lang = get_user_meta( $uid, 'user_lang', 1) )
		){
			self::$lang = $user_lang;
		}
		elseif( ! empty( $_COOKIE['lang'] ) ){
			self::$lang = $_COOKIE['lang'];
		}

		if( ! self::$lang || ! $this->is_lang_active( self::$lang ) ){
			self::$lang = i18n_opt()->default_lang;
		}

		// локаль, для фронта и аякс
		if( ! is_admin() || wp_doing_ajax() ){

			$lang_locale = $this->is_lang_active( self::$lang ) ? self::$langs_data[ self::$lang ]['locale'] : '';

			if( $lang_locale && $lang_locale !== get_locale() ){

				add_filter( 'locale', function( $locale ) use ( $lang_locale ){
					return $lang_locale;
				} );
			}
		}

		// кука языка
		if( ! is_admin() && ! wp_doing_ajax() ){

			if( I18N_IS_MUPLUG_INSTALL ){
				// на момент хука 'muplugins_loaded' константы куков пр. COOKIE_DOMAIN еще не определены.
				add_action( 'plugins_loaded', [ __CLASS__, 'set_cookie' ]);

				// позже, потому что для обновления текущего юзера нужны разные фукнции, которых еще нет до plugins_loaded
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
	 * @param string $lang Lang to check or current self::$lang.
	 *
	 * @return bool
	 */
	public function is_lang_active( $lang = '' ){

		return isset( self::$langs_data[ $lang ?: self::$lang ] );
	}

	# устанавливает куку языка
	public static function set_cookie(){

		if( empty( $_COOKIE['lang'] ) || $_COOKIE['lang'] != self::$lang ){

			setcookie( 'lang', self::$lang, ( time() + DAY_IN_SECONDS * 365 ), COOKIEPATH, COOKIE_DOMAIN );
			$_COOKIE['lang'] = self::$lang;
		}
	}

	# устанавливает куку языка
	public static function update_user_lang(){

		if( function_exists('get_current_hb_user') ){
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
			} // обновим с очисткой кэша
			else{
				update_user_meta( $cuser->ID, 'user_lang', self::$lang );
			}
		}
	}

	# редирект на нужный язык, если его нет в URL, только для запросов с шаблоном - template_redirect
	public static function lang_redirect(){

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
		if( ! i18n_opt()->process_home_url
		    &&
		    (
		        '/' === $URI_parts['path']
				//||
		        //str_starts_with( $URI_parts['path'], '/page/' )
		    )
		){
			return;
		}

		// exceptions
		if(
			$URI === '/robots.txt'
			||
			preg_match( '~^/(wp-sitemap|sitemap)~', $URI_parts['path'] )
			// /wp-json/
			|| preg_match( '~/' . rest_get_url_prefix() . '~', $URI_parts['path'] )
		){
			return;
		}

		// language is already set
		if( preg_match( '~^/('. self::$langs_regex .')(/|$)~', $URI_parts['path'] ) ){
			return;
		}

		// redirect to default or current language
		$new_url = home_url( ( self::$lang ?: i18n_opt()->default_lang ) . $URI );

		wp_safe_redirect( $new_url, 301 );
		exit;

	}

}
