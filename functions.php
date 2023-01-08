<?php
/**
 * Wrapper functions for use in themes and plugins.
 */

/**
 * Gets languages data.
 *
 * @param bool $type  Whith type of langs data you want to retrieve.
 *                    Can be: codes|ids, names, locales, flags.
 *
 * @return array
 */
function langs_data( $type = false ){

	$langs = Langs()->langs_data;

	if( $type === 'codes' || $type === 'ids' ){
		return array_keys( $langs );
	}

	if( $type === 'names' ){
		return wp_list_pluck( $langs, 'lang_name' );
	}

	if( $type === 'locales' ){
		return wp_list_pluck( $langs, 'locale' );
	}

	if( $type === 'flags' ){
		return wp_list_pluck( $langs, 'flag' );
	}

	return $langs;
}

/**
 * Gets current defined language. Eg: ru, en.
 */
function current_lang(): string {
	return Langs()->lang;
}

/**
 * Validates specified language name. Compares it with registered languages and check if it exists.
 *
 * @param string $lang Lang name.
 *
 * @return string Passed lang if it exists in the list of current languages. $default otherwise.
 */
function sanitize_lang( $lang, $default = '' ){
	return Langs()->is_lang_active( $lang ) ? $lang : $default;
}

/**
 * Checks if the current language is the default language
 * (the language in which the site works, in which the data in the database is stored).
 */
function is_current_lang_default(): bool {
	return current_lang() === i18n_opt()->default_lang;
}

/**
 * Gets the flag URL by country code
 */
function flag_url_by_country_code( $country_code ) {

	( 'en' === $country_code )    && ( $country_code = 'us' );
	( 'pt-br' === $country_code ) && ( $country_code = 'br' );

	return I18N_URL . 'img/flags/4x3/' . strtolower( $country_code ) . ".svg";
}

/**
 * Replaces the language prefix in the passed URL.
 */
function uri_replace_lang_prefix( $url, $new_lang = '' ){

	if( ! $new_lang ){
		$new_lang = current_lang();
	}

	return preg_replace(
		'~^(https?://[^/]+)?('. i18n_opt()->URI_prefix .'/)(?:'. Langs()->langs_regex .')(?=/)~',
		"\\1\\2$new_lang", $url, 1
	);
}

/**
 * Removes the language prefix from the passed URL.
 */
function uri_delete_lang_prefix( string $url ): string {

	$url = preg_replace(
		'~^(https?://[^/]+)?('. i18n_opt()->URI_prefix .'/)(?:'. Langs()->langs_regex .')(?=/)~',
		'\1\2', $url, 1
	);

	return preg_replace( '~(?<!:)/+~', '/', $url ); //> //foo >>> /foo
}


