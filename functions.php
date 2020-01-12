<?php

/**
 * Функции обертки для использвоания в теме и плагинах...
 */

## Получает данные языков
function langs_data( $type = false ){
	if( $type === 'names' )
		return array_keys( Langs::$langs );

	return Langs::$langs;
}

## Получает текущий язык: en, ru
function current_lang(){
	return Langs::$lang;
}

## Проверяет является ли текущий язык языком по умолчанию (языком на котором в первую очередь работает сайт, на котором храняться базовые данные в БД)
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
	return preg_replace( '~^(https?://[^/]+)?('. Langs::$URI_prefix .'/)(?:'. Langs::$langs_regex .')(?=/)~', '\1\2', $url, 1 );
}


// i18n functions ---

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
	$meta = $meta_func( $id, "{$meta_key}_en", $single );
	if( $meta !== '' )
		return $meta;

	// fallback
	return $meta_func( $id, "{$meta_key}_ru", $single );
}

## Получает поле поста или метаполе ПОЛЕ_{lang} на основе текущего языка.
## Обертка для get_post_meta_i18n()
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


