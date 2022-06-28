<?php

// дополнительные фильтры
// фронт
if( ! is_admin() ) {

	add_filter( 'the_title',         'the_title_i18n', 0, 2 );
	add_filter( 'the_content',       'the_content_i18n', 0  );
	add_filter( 'get_term',          'term_title_i18n', 0   );
	add_filter( 'kama_excerpt_args', 'kama_excerpt_args_i18n', 0 );

}

// TODO проверить очистку для переводов...
function the_title_i18n( $title, $id = null ) {
	$post = get_post( $id );

	// if(
	// 	'nav_menu_item' === $post->post_type ||
	// 	current_lang() === 'en'
	// )
	// 	return $title;

	$lang = ( isset( $_GET['lang'] ) ? $_GET['lang'] :  current_lang() );

	if( $translated = get_post_meta($id, 'title_'. $lang, true) )
		return $translated;

	return $title;
}

// TODO проверить очистку для переводов...
function the_content_i18n( $content ) {

	// if( current_lang() === 'en' )
	// 	return $content;

	$lang = ( isset( $_GET['lang'] ) ? $_GET['lang'] :  current_lang() );

	if( $translated = get_post_meta( get_the_ID(), 'content_' . $lang, true ) )
		return $translated;

	return $content;
}

// TODO проверить очистку для переводов...
function term_title_i18n( $term ) {

	// if( current_lang() === 'en' )
	// 	return $term;

	if( $name = get_term_meta( $term->term_id, 'name_' . current_lang(), true ) )
		$term->name = $name;

	return $term;
}

// TODO проверить очистку для переводов...
## функция для хука
function kama_excerpt_args_i18n( $rg ){
	global $post;

	// if( current_lang() === 'en' )
	// 	return $rg; // контент и так получает сама функция

	if( $translated = get_post_meta( $post->ID, 'content_' . current_lang(), true ) ){
		$rg->text = $translated;
		return $rg;
	}

	return $rg;
}


/*
## nav menu под текущий язык
function translate_main_menu(){

	$add_args = array(
		'fallback_cb' => '__return_empty_string',
		'container'   => '',
		'echo'        => 0,
	);

	$menu = wp_nav_menu( ['theme_location' => 'main_menu_' . current_lang() ] + $add_args );

	if ( !empty($menu) ) {
		return $menu;
	}
	else {
		$menu = wp_nav_menu( ['theme_location' => 'main_menu_en'] + $add_args );

		if ( !empty($menu) )
			return $menu;
		else
			return wp_nav_menu( ['theme_location' => 'main_menu_ru'] + $add_args );
	}

}
*/

