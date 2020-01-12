<?php


class Langs {

	// данные всех языков
	static $langs = [
		'ru' => [
			'lang_name' => 'Русский',
			'locale'    => 'ru_RU',
			'flag'      => I18N_URL .'img/flags/4x3/ru.svg',
			'flag_path' => '', // ставиться позднее...
		],
		'en' => [
			'lang_name' => 'English',
			'locale'    => 'en_US',
			'flag'      => I18N_URL .'img/flags/4x3/gb.svg',
		],
		'de' => [
			'lang_name' => 'Deutsch',
			'locale'    => 'de_DE',
			'flag'      => I18N_URL .'img/flags/4x3/de.svg',
		],
		'fr' => [
			'lang_name' => 'Français',
			'locale'    => 'fr_FR',
			'flag'      => I18N_URL .'img/flags/4x3/fr.svg',
		],
		'es' => [
			'lang_name' => 'Español',
			'locale'    => 'es_ES',
			'flag'      => I18N_URL .'img/flags/4x3/es.svg',
		],
//		'nl' => [
//			'lang_name' => 'Nederlandse',
//			'locale'    => 'nl_NL',
//			'flag'      => I18N_URL .'img/flags/4x3/nl.svg',
//		],
//		'it' => [
//			'lang_name' => 'Italiano',
//			'locale'    => 'it_IT',
//			'flag'      => I18N_URL .'img/flags/4x3/it.svg',
//		],
//		'ja' => [
//			'lang_name' => '日本語',
//			'locale'    => 'ja',
//			'flag'      => I18N_URL .'img/flags/4x3/jp.svg',
//		],
//		'pl' => [
//			'lang_name' => 'Polski',
//			'locale'    => 'pl_PL',
//			'flag'      => I18N_URL .'img/flags/4x3/pl.svg',
//		],
//		'tr' => [
//			'lang_name' => 'Türkçe',
//			'locale'    => 'tr_TR',
//			'flag'      => I18N_URL .'img/flags/4x3/tr.svg',
//		],
//		'pt-br' => [
//			'lang_name' => 'Português brasileiro',
//			'locale'    => 'pt_BR',
//			'flag'      => I18N_URL .'img/flags/4x3/br.svg',
//		],
	];

	static $langs_regex = '';  // регулярка для ru|en, для упрощения...

	static $lang  = '';  // текущий язык ru или en - по умолчанию пустая строка - не определен

	static $default_lang = 'en';

	static $URI_prefix = ''; // поддиректория в которой расположен сайт: `//site.ru/asia/` > `/asia`

	static $inst; // экземпляр

	static function init(){
		if( self::$inst === null ) self::$inst = new self;
		return self::$inst;
	}

	function __construct(){

		self::$URI_prefix = untrailingslashit( parse_url( get_option('home'), PHP_URL_PATH ) );

		// раньше всех
		self::$langs_regex = implode( '|', array_keys( self::$langs ) );


		// добавим путь
		foreach( self::$langs as $lang => & $data ){
			$data['flag_path'] = str_replace( I18N_URL, I18N_PATH, $data['flag'] );
		}
		unset( $data );

		// Устанавливает текущий язык: self::$lang, локаль и куки.
		self::set_lang_init();

		// после установки локали...
		//load_muplugin_textdomain( 'i18n', plugin_basename(I18N_PATH) .'/lang' );

		add_action( 'init', [ 'I18n_Rewrite_Rules', 'init' ], 0 );

		add_action( 'template_redirect', [ __CLASS__, 'lang_redirect' ] );

	}

	## определяет текущий язык и устанавливает его. Устанавливает локаль и куки.
	static function set_lang_init(){

		$supported_langs = array_keys( self::$langs );

		// установим self::$lang
		if(0){}
		// определяем по URL
		elseif(
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
		elseif( 0 // skip
			// TODO определить WP юзера по кукам (до plugins_loaded) WP is_user_logged_in
			&& ($uid = 0)
			&& ($user_lang = get_user_meta( $uid, 'user_lang', 1))
		){
			self::$lang = $user_lang;
		}
		elseif( ! empty($_COOKIE['lang']) ){
			self::$lang = $_COOKIE['lang'];
		}

		if( ! self::$lang || ! in_array( self::$lang, $supported_langs ) ){
			self::$lang = self::$default_lang;
		}

		// установим локаль, для фронта и аякс
		if( ! is_admin() || wp_doing_ajax() ){
			$lang_locale = isset(self::$langs[ self::$lang ]) ? self::$langs[ self::$lang ]['locale'] : '';
			if( $lang_locale && $lang_locale != get_locale() ){
				//switch_to_locale( $lang_locale );
				add_filter( 'locale', function( $locale ) use ( $lang_locale ){
					return $lang_locale;
				} );
			}
		}

		// установим куку языка, если надо
		if( ! is_admin() && ! wp_doing_ajax() ){

			if( I18N_MUPLUG_INSTALL ){
				// на момент хука 'muplugins_loaded' константы куков пр. COOKIE_DOMAIN еще не определены...
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

	## устанавливает куку языка
	static function set_cookie(){
		if( empty($_COOKIE['lang']) || $_COOKIE['lang'] != self::$lang ){
			setcookie( 'lang', self::$lang, (time() + DAY_IN_SECONDS * 365), COOKIEPATH, COOKIE_DOMAIN );
			$_COOKIE['lang'] = self::$lang;
		}
	}

	## устанавливает куку языка
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

	## редирект на нужный язык, если его нет в URL, только для запросов с шаблоном - template_redirect
	static function lang_redirect(){

		$URI = $_SERVER['REQUEST_URI'];

		// удалим префик, если надо. Чтобы удобно было проверять
		if( self::$URI_prefix )
			$URI = substr( $URI, strlen(self::$URI_prefix) );

		// возомжность отключить перенаправление через фильтр.
		if( apply_filters('disable_lang_redirect', false, $URI ) ) return;

		// исключения
		if( $URI === '/robots.txt' ) return;
		if( preg_match( '/\/wp-json/', $URI ) ) return;

		// в запросе нет языка, перенаправим на дефолтный
		if( ! preg_match( '~^/('. self::$langs_regex .')(/|$)~', $URI ) ){
			wp_redirect( home_url( (self::$lang ?: self::$default_lang) . $URI), 301 );
			die;
		}

	}

}
