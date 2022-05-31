<?php

/**
 * @return Langs
 */
function Langs(){
	return Langs::instance();
}

class Langs {

	// use lang if it's not set
	public static string $default_lang = 'ru';

	public static array $active_langs = [ 'ru' ];

	// данные всех языков
	public static array $langs_data;

	// регулярка для ru|en, для упрощения...
	public static string $langs_regex = '';

	// текущий язык ru или en - по умолчанию пустая строка - не определен
	public static string $lang  = '';

	// поддиректория в которой расположен сайт: `//site.ru/asia/` > `/asia`
	public static string $URI_prefix = '';

	/**
	 * @return self
	 */
	public static function instance(): self {
		static $instance;
		$instance || $instance = new self();
		return $instance;
	}

	private function __construct(){

		// set $langs_data
		self::$langs_data = require __DIR__ . '/langs-data.php';

		// добавим flag_path для всех активных языков
		foreach( self::$active_langs as $lang ){
			self::$langs_data[ $lang ]['flag_path'] = str_replace( I18N_URL, I18N_PATH, self::$langs_data[ $lang ]['flag'] );
		}

	}

	public function init(): void {

		self::$URI_prefix = untrailingslashit( parse_url( get_option('home'), PHP_URL_PATH ) );

		// раньше всех
		self::$langs_regex = implode( '|', self::$active_langs );

		// Устанавливает текущий язык: self::$lang, локаль и куки.
		self::set_lang();

		// после установки локали...
		//load_muplugin_textdomain( 'i18n', plugin_basename(I18N_PATH) .'/lang' );

		I18n_Rewrite_Rules::early_init();

		add_action( 'init', [ 'I18n_Rewrite_Rules', 'init' ], 0 );

		add_action( 'template_redirect', [ __CLASS__, 'lang_redirect' ] );
	}

	/**
	 * Определяет текущий язык и устанавливает его. Устанавливает локаль и куки.
	 */
	static function set_lang(){

		// установим self::$lang
		if(
			// определяем по URL
			preg_match( '~^'. self::$URI_prefix .'/('. self::$langs_regex .')(/|$)~', $_SERVER['REQUEST_URI'], $mm ) &&
			// пропускаем странные URL: на css, js, php, с запросами, от wp и т.д.
			! preg_match( '~[.]|wp-~', $_SERVER['REQUEST_URI'] ) // ?= юзать нельзя - это параметры запроса
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
		elseif( ! empty($_COOKIE['lang']) ){
			self::$lang = $_COOKIE['lang'];
		}

		if( ! self::$lang || ! self::active_langs_contains() ){
			self::$lang = self::$default_lang;
		}

		// локаль, для фронта и аякс
		if( ! is_admin() || wp_doing_ajax() ){

			$lang_locale = self::active_langs_contains() ? self::$langs_data[ self::$lang ]['locale'] : '';

			if( $lang_locale && $lang_locale !== get_locale() ){

				add_filter( 'locale', function( $locale ) use ( $lang_locale ){
					return $lang_locale;
				} );
			}
		}

		// кука языка
		if( ! is_admin() && ! wp_doing_ajax() ){

			if( I18N_MUPLUG_INSTALL ){
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
	 * Checks if specified lang is one of active langs.
	 *
	 * @param string $lang Lang to check or current self::$lang.
	 *
	 * @return bool
	 */
	public static function active_langs_contains( $lang = '' ){
		return in_array( $lang ?: self::$lang, self::$active_langs, true );
	}

	# устанавливает куку языка
	static function set_cookie(){

		if( empty( $_COOKIE['lang'] ) || $_COOKIE['lang'] != self::$lang ){

			setcookie( 'lang', self::$lang, ( time() + DAY_IN_SECONDS * 365 ), COOKIEPATH, COOKIE_DOMAIN );
			$_COOKIE['lang'] = self::$lang;
		}
	}

	# устанавливает куку языка
	static function update_user_lang(){

		$cuser = function_exists('get_current_hb_user') ? get_current_hb_user() : wp_get_current_user();

		if( ! $cuser )
			return;

		if( $cuser->user_lang !== self::$lang ){
			if( function_exists('update_hb_user') )
				update_hb_user([ 'ID'=>$cuser->ID, 'user_lang'=>self::$lang ]); // обновим с очисткой кэша
			else
				update_user_meta( $cuser->ID, 'user_lang', self::$lang );
		}
	}

	# редирект на нужный язык, если его нет в URL, только для запросов с шаблоном - template_redirect
	static function lang_redirect(){

		$URI = $_SERVER['REQUEST_URI'];

		// Удалим префикc, если надо. Чтобы удобно было проверять
		if( self::$URI_prefix ){
			$URI = substr( $URI, strlen( self::$URI_prefix ) );
		}

		// возомжность отключить перенаправление через фильтр.
		/**
		 * Allow to disble redirect.
		 *
		 * @param bool   $disable_redirect
		 * @param string $URI               Current $_SERVER['REQUEST_URI'].
		 */
		if( apply_filters( 'disable_lang_redirect', false, $URI ) ){
			return;
		}

		// исключения
		if(
			$URI === '/robots.txt'
			// /wp-json/
			|| preg_match( '~/' . rest_get_url_prefix() . '~', $URI )
		){
			return;
		}

		// в запросе нет языка, перенаправим на дефолтный
		if( ! preg_match( '~^/('. self::$langs_regex .')(/|$)~', $URI ) ){
			wp_safe_redirect( home_url( ( self::$lang ?: self::$default_lang ) . $URI ), 301 );
			exit;
		}

	}

}
