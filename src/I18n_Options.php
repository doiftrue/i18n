<?php

/**
 * @return I18n_Rewrite_Rules
 */
function i18n_opt(): I18n_Options {
	return I18n_Options::instance();
}

/**
 * @property-read bool     $process_home_url  Do we need to use a language prefix for the main home_url().
 * @property-read string[] $active_langs      Active langs slugs [ 'ru', 'en' ].
 * @property-read string   $default_lang      ru|en.
 * @property-read string   $URI_prefix        Subdir whrer the site is located: `//site.com/asia/` > `/asia`.
 */
class I18n_Options {

	/**
	 * Plugin options.
	 *
	 * @var array
	 */
	private $opts;

	public static function instance(){
		static $inst;
		$inst || $inst = new self();
		return $inst;
	}

	public function __construct(){

		$this->opts = apply_filters( 'i18n__options', [
			'default_lang' => 'ru',
			'active_langs' => [ 'ru' ],
			// (bool) Нужно ли использовать префикс языка для главной home_url().
			'process_home_url' => true,
			'URI_prefix' => untrailingslashit( parse_url( get_option('home'), PHP_URL_PATH ) ),
		] );

	}

	public function __isset( $name ){
		return null !== $this->__get( $name );
	}

	public function __set( $name, $val ){}

	public function __get( $name ){

		return $this->opts[ $name ] ?? null;
	}

}