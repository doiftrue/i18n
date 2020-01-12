<?php

// дополнительные фильтры
if( ! is_admin() ) {

	add_filter( 'the_title',   'the_title_i18n', 0, 2 );
	add_filter( 'the_content', 'the_content_i18n', 0  ); // 6 чтобы видео обрабатывалось...
	add_filter( 'get_term',    'term_name_i18n', 0     );
	//add_filter( 'kama_excerpt_args', 'kama_excerpt_args_i18n', 0 );


	// TODO проверить очистку для переводов...
	## авто-перевод для элементов nav_menu
	function the_title_i18n( $title, $id = null ) {
		$post = get_post( $id );

		if ( $post->post_type === 'nav_menu_item' && is_current_lang_default() )
			return $title;

		$translated = get_post_meta( $id, 'title_' . current_lang(), true );

		if( $translated ){
			if( $post->post_type === 'city' )
				return explode( '/', $translated )[0];
			else
				return $translated;
		}

		// fallback
		return /*get_post_meta( get_the_ID(), 'title_en', true ) ?:*/ $title;
	}

	// TODO проверить очистку для переводов...
	## функция для хука
	function term_name_i18n( $term ) {

		$translated = get_term_meta( $term->term_id, 'name_' . current_lang(), true );

		$is_geo = in_array( $term->taxonomy, ['country','adm'] );

		if( $translated ){
			if( $is_geo )
				$term->name = explode( '/', $translated )[0];
			else
				$term->name = $translated;
		}

		return $term;
	}

	// TODO проверить очистку для переводов...
	## функция для хука
	function the_content_i18n( $content ) {
		//if ( is_current_lang_default() )
		//	return $content;

		if ( $translated = get_post_meta( get_the_ID(), 'content_' . current_lang(), true ) )
			return $translated;

		// translated_fallback
		return get_post_meta( get_the_ID(), 'content_en', true ) ?: $content;
	}

	// TODO проверить очистку для переводов...
	## функция для хука
	function kama_excerpt_args_i18n( $rg ) {
		global $post;

		if ( is_current_lang_default() )
			return $rg; // контент и так получает сама функция

		if ( $translated = get_post_meta( $post->ID, 'content_' . current_lang(), true ) ){
			$rg->text = $translated;
			return $rg;
		}

		// fallback
		if( $translated = get_post_meta( $post->ID, 'content_en', true ) )
			$rg->text = $translated ? : $rg;

		return $rg;
	}

	## Заменяет язык на текущий у всех ссылок в контенте
	add_filter( 'the_content', 'i18n_set_url_current_lang' );
	function i18n_set_url_current_lang( $content ){
		return preg_replace_callback( '~https?://[^\'"]+~', function($match){
			return uri_replace_lang_prefix( $match[0], current_lang() );
		}, $content );
	}

}

/*
add_action( 'after_setup_theme', 'langs_register_nav_menus' );
function langs_register_nav_menus(){

	$menus_array = array();
	foreach ( langs_data() as $key => $data ) {
		$menus_array[ "main_menu_$key" ] = 'Main menu (' . $data['lang_name'] . ')';
	}

	register_nav_menus( $menus_array );

}
*/


//	## получает переводы GEO объекта
//	function get_geo_transl( $geo_id ){
//		global $wpdb;
//
//		$wpdb->get_results( "SELECT * FROM $wpdb->geoname_transl" );
//	}
