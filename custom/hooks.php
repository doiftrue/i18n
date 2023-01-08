<?php

if( is_admin() ){
	return;
}

add_filter( 'the_title',         'the_title_i18n', 0, 2 );
add_filter( 'the_content',       'the_content_i18n', 0  );
add_filter( 'get_term',          'term_title_i18n', 0   );
add_filter( 'kama_excerpt_args', 'kama_excerpt_args_i18n', 0 );


// TODO check the cleanup for translations...
function the_title_i18n( $title, $id = null ) {

	// $post = get_post( $id );
	// if( 'nav_menu_item' === $post->post_type || 'en' === current_lang() ){
	// 	return $title;
	// }

	$lang = $_GET['lang'] ?? current_lang();
	$translated = get_post_meta( $id, "title_$lang", true );
	if( $translated ){
		return $translated;
	}

	return $title;
}

// TODO check the cleanup for translations...
function the_content_i18n( $content ) {

	// if( current_lang() === 'en' )
	// 	return $content;

	$lang = $_GET['lang'] ?? current_lang();

	$translated = get_post_meta( get_the_ID(), "content_$lang", true );
	if( $translated ){
		return $translated;
	}

	return $content;
}

// TODO check the cleanup for translations...
function term_title_i18n( $term ) {

	// if( current_lang() === 'en' )
	// 	return $term;

	$name = get_term_meta( $term->term_id, 'name_' . current_lang(), true );
	if( $name ){
		$term->name = $name;
	}

	return $term;
}

// TODO check the cleanup for translations...
function kama_excerpt_args_i18n( $rg ) {
	global $post;

	$translated = get_post_meta( $post->ID, 'content_' . current_lang(), true );
	if( $translated ){
		$rg->text = $translated;

		return $rg;
	}

	return $rg;
}


