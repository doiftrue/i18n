<?php

/**
 * Функции обертки для использвоания в теме и плагинах...
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

	if( $type === 'codes' || $type === 'ids' ){
		return Langs::$active_langs;
	}

	$langs = array_intersect_key( Langs::$langs_data, array_flip( Langs::$active_langs ) );

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
 * Gets current defined language. Ex: ru, en
 *
 * @return string
 */
function current_lang(){
	return Langs::$lang;
}

/**
 * Validates specified language name. Compares it with registered languages and check if it exists.
 *
 * @param string $lang Lang name.
 *
 * @return string Passed lang if it exists in the list of current languages. $default otherwise.
 */
function sanitize_lang( $lang, $default = '' ){
	return Langs::active_langs_contains( $lang ) ? $lang : $default;
}

/**
 * Проверяет являться ли текущий язык языком по умолчанию (языком на котором
 * в первую очередь работает сайт, на котором храняться базовые данные в БД).
 *
 * @return bool
 */
function is_current_lang_default(){
	return Langs::$lang === Langs::$default_lang;
}

## Получает URL флага по коду страниы
function flag_url_by_country_code( $country_code ){
	if( $country_code === 'en' )    $country_code = 'us';
	if( $country_code === 'pt-br' ) $country_code = 'br';
	return I18N_URL . "img/flags/4x3/". strtolower($country_code) .".svg";
}

## заменяет префикс языка на указанный в переданном URL
function uri_replace_lang_prefix( $url, $new_lang = '' ){
	if( ! $new_lang )
		$new_lang = current_lang();

	return preg_replace( '~^(https?://[^/]+)?('. Langs::$URI_prefix .'/)(?:'. Langs::$langs_regex .')(?=/)~', "\\1\\2$new_lang", $url, 1 );
}

## удаляет префикс языка из переданного URL
function uri_delete_lang_prefix( $url ){
	$url = preg_replace( '~^(https?://[^/]+)?('. Langs::$URI_prefix .'/)(?:'. Langs::$langs_regex .')(?=/)~', '\1\2', $url, 1 );
	return preg_replace( '~(?<!:)/+~', '/', $url ); // //bar >>> /bar
}


// i18n functions ---

## Получает переведенное на текущий язык произвольное поле комментария
function get_comment_meta_i18n( $post_id, $meta_key, $single = true ) {
	return _get_meta_i18n( 'get_comment_meta', $post_id, $meta_key, $single );
}

## Получает переведенное на текущий язык произвольное поле
function get_post_meta_i18n( $post_id, $meta_key, $single = true ) {
	return _get_meta_i18n( 'get_post_meta', $post_id, $meta_key, $single );
}

## Получает переведенное на текущий язык произвольное поле
function get_term_meta_i18n( $term_id, $meta_key, $single = true ) {
	return _get_meta_i18n( 'get_term_meta', $term_id, $meta_key, $single );
}

## Получает переведенное на текущий язык метаполе HB юзера
function get_user_meta_i18n( $obj_id, $meta_key, $single = true ){
	return _get_meta_i18n( 'get_user_meta', $obj_id, $meta_key, $single );
}

## Получает переведенное на текущий язык метаполе HB юзера
function get_hb_user_meta_i18n( $post_id, $meta_key, $single = true ){
	return _get_meta_i18n( 'get_hb_user_meta', $post_id, $meta_key, $single );
}

## Получает поле post_content или метаполе content_{lang} на основе текущего языка.
function get_post_content_i18n( $post ){
	return _get_post_field_i18n( $post, 'content' );
}

## Получает поле post_content или метаполе content_{lang} на основе текущего языка.
## Обертка для get_post_meta_i18n()
function get_post_title_i18n( $post ){
	return _get_post_field_i18n( $post, 'title' );
}

/**
 * Получает метаполе на основе указанной функции и текущего языка.
 *
 * @param string $meta_func Функция получения метаполя: get_post_meta, get_term_meta ...
 * @param int    $id
 * @param string $meta_key
 * @param bool   $single
 *
 * @return mixed
 */
function _get_meta_i18n( $meta_func, $id, $meta_key, $single ){

	$meta = $meta_func( $id, "{$meta_key}_" . current_lang(), $single );
	if( $meta !== '' )
		return $meta;

	// fallback
	$meta = $meta_func( $id, "{$meta_key}_". Langs::$default_lang, $single );
	if( $meta !== '' )
		return $meta;

	return $meta_func( $id, $meta_key, $single );
}

/**
 * Получает поле поста или метаполе ПОЛЕ_{lang} на основе текущего языка.
 * Обертка для get_post_meta_i18n()
 *
 * @param int|WP_Post $post
 * @param string      $field
 *
 * @return mixed|string
 */
function _get_post_field_i18n( $post, $field = 'content' ){
	$post = get_post( $post );

	if( ! $post ) return '';

	$value = $post->{"post_$field"};

	if( is_current_lang_default() && $value )
		return $value;

	if( $meta_value = get_post_meta_i18n( $post->ID, $field ) )
		$value = $meta_value;

	return $value;
}




/**
 * Replase current MO data with specified one by locale.
 *
 * @param string $set_locale `en_US`, `ru_RU`, or simple `ru`, `en`.
 * @param string $domain
 *
 * @return bool
 */
function hb_switch_locale( $set_locale, $domain = 'hb' ){
	global $l10n, $locale, $hb_MOs, $hb_switched_locale;

	// aliases: ru >>> ru_RU
	if( isset( Langs::$langs_data[ $set_locale ] ) ){
		$set_locale = Langs::$langs_data[ $set_locale ]['locale'];
	}

	// save original
	if( ! isset( $hb_MOs[ $locale ] ) ){
		$hb_MOs[ $locale ] = $l10n[ $domain ];
	}

	// exclusion for hard coded locale
	if( 'en_US' === $set_locale ){
		$hb_switched_locale = $locale;
		$l10n[ $domain ] = null;
		return true;
	}

	if( isset( $hb_MOs[ $set_locale ] ) ){
		$mo = $hb_MOs[ $set_locale ];
	}
	else{
		$mofile = __DIR__ . "/lang/hb-$set_locale.mo";

		if( ! is_readable( $mofile ) ){
			return false;
		}

		$mo = new MO();
		if( ! $mo->import_from_file( $mofile ) ){
			return false;
		}

		$hb_MOs[ $set_locale ] = $mo;
	}

	// set switched + save
	$hb_switched_locale = $locale;

	$l10n[ $domain ] = $mo;

	return true;
}

/**
 * Restore switched by hb_switch_locale() function MO data.
 *
 * @param string $domain
 *
 * @return bool
 */
function hb_restore_locale( $domain = 'hb' ){
	global $l10n, $hb_MOs, $hb_switched_locale;

	if( ! empty($hb_switched_locale) ){
		$l10n[ $domain ] = $hb_MOs[ $hb_switched_locale ];
		$hb_switched_locale = null;

		return true;
	}

	return false;
}