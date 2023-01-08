<?php

## Gets an arbitrary comment field translated into the current language
function get_comment_meta_i18n( $post_id, $meta_key, $single = true ) {
	return _get_meta_i18n( 'get_comment_meta', $post_id, $meta_key, $single );
}

## Gets an arbitrary field translated into the current language
function get_post_meta_i18n( $post_id, $meta_key, $single = true ) {
	return _get_meta_i18n( 'get_post_meta', $post_id, $meta_key, $single );
}

## Get the arbitrary field, translated into your language
function get_term_meta_i18n( $term_id, $meta_key, $single = true ) {
	return _get_meta_i18n( 'get_term_meta', $term_id, $meta_key, $single );
}

## Get the HB metafield of user translated into your language
function get_user_meta_i18n( $obj_id, $meta_key, $single = true ){
	return _get_meta_i18n( 'get_user_meta', $obj_id, $meta_key, $single );
}

## Gets the HB metafield translated into your language
function get_hb_user_meta_i18n( $post_id, $meta_key, $single = true ){
	return _get_meta_i18n( 'get_hb_user_meta', $post_id, $meta_key, $single );
}

## Gets the post_content or content_{lang} metafield based on the current language.
function get_post_content_i18n( $post ){
	return _get_post_field_i18n( $post, 'content' );
}

## Gets the post_content or content_{lang} metafield based on the current language.
## Wrapper for get_post_meta_i18n()
function get_post_title_i18n( $post ){
	return _get_post_field_i18n( $post, 'title' );
}

/**
 * Gets the meta-field based on the specified function and the current language.
 *
 * @param string $meta_func The function for obtaining meta-fields: get_post_meta, get_term_meta ...
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
 * Gets the post field or meta-field POLE_{lang} based on the current language.
 * Wrapper for get_post_meta_i18n().
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
