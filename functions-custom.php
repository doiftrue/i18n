<?php

// HTML ---

## wp_nav_menu ( main_menu_ ) под текущий язык
function wp_nav_main_menu_i18n(){

	$add_args = [
		'fallback_cb' => '__return_empty_string',
		'echo'        => 0,
		'container'   => '',
		'menu_class'  => '',
	];

	$menu = wp_nav_menu( [ 'theme_location' => 'main_menu_' . current_lang() ] + $add_args );
	if( $menu )
		return $menu;

	return wp_nav_menu( [ 'theme_location' => 'main_menu_ru' ] + $add_args );
}

## переключение между языками
function switch_lang_links_html(){
	$items = [];

	foreach( langs_data() as $lang => $data ){
		$url = uri_replace_lang_prefix( $_SERVER['REQUEST_URI'], $lang );

		$current = current_lang() == $lang ? ' current' : '';

		$items[] = '
		<li class="langs__item'. $current .'">
			<a href="'. esc_url($url) .'" class="langs__link">
				<img src="'. $data['flag'] .'" alt="'. $lang .'" class="langs__flag">
			</a>
		</li>
		';
	}

	echo '
	<div class="langs">
		<ul class="langs__list">
		'. implode( "\n", $items ) .'
		</ul>
	</div>
	';

}
