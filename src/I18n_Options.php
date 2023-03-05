<?php

/**
 * @property-read bool     $home_page_add_prefix  Do we need to use a language prefix for the main home page?
 * @property-read string[] $active_langs          Active langs slugs [ 'ru', 'en' ].
 * @property-read string   $default_lang          ru|en.
 * @property-read string   $URI_prefix            Subdir whrer the site is located: `//site.com/asia/` > `/asia`.
 */
class I18n_Options {

	private array $options;

	public static function instance(){
		static $inst;
		$inst || $inst = new self();

		return $inst;
	}

	public function __construct(){

		$this->options = [
			'default_lang' => 'ru',
			'active_langs' => [ 'ru' ],
			// (bool) Do we need to use a language prefix for the main page - home_url().
			'home_page_add_prefix' => true,
			'URI_prefix' => untrailingslashit( parse_url( get_option( 'home' ), PHP_URL_PATH ) ),
		];

		/**
		 * Allow to change plugin options.
		 *
		 * @param array $options Plugin options.
		 */
		$this->options = apply_filters( 'i18n__options', $this->options );
	}

	public function __isset( $name ){
		return null !== $this->__get( $name );
	}

	public function __set( $name, $val ){
		return null;
	}

	public function __get( $name ){
		return $this->options[ $name ] ?? null;
	}

}
