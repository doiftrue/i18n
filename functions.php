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
 * Gets current defined language. Ex: ru, en
 *
 * @return string
 */
function current_lang(){
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
 * Проверяет являться ли текущий язык языком по умолчанию (языком на котором
 * в первую очередь работает сайт, на котором храняться базовые данные в БД).
 *
 * @return bool
 */
function is_current_lang_default(){
	return current_lang() === i18n_opt()->default_lang;
}

## Получает URL флага по коду страниы
function flag_url_by_country_code( $country_code ){

	'en' === $country_code && $country_code = 'us';
	'pt-br' === $country_code && $country_code = 'br';

	return I18N_URL . "img/flags/4x3/". strtolower( $country_code ) .".svg";
}

## заменяет префикс языка на указанный в переданном URL
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
 * Удаляет префикс языка из переданного URL.
 *
 * @param string $url
 *
 * @return string
 */
function uri_delete_lang_prefix( $url ){

	$url = preg_replace(
		'~^(https?://[^/]+)?('. i18n_opt()->URI_prefix .'/)(?:'. Langs()->langs_regex .')(?=/)~',
		'\1\2', $url, 1
	);

	return preg_replace( '~(?<!:)/+~', '/', $url ); // //foo >>> /foo
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
	if( $meta !== '' ){
		return $meta;
	}

	// fallback
	$meta = $meta_func( $id, "{$meta_key}_" . i18n_opt()->default_lang, $single );
	if( $meta !== '' ){
		return $meta;
	}

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
function _get_post_field_i18n( $post, $field = 'content' ) {

	$post = get_post( $post );

	if( ! $post ){
		return '';
	}

	$value = $post->{"post_$field"};

	if( $value && is_current_lang_default() ){
		return $value;
	}

	$meta_value = get_post_meta_i18n( $post->ID, $field );

	if( $meta_value ){
		$value = $meta_value;
	}

	return $value;
}



