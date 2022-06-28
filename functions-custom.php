<?php

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

	$langs_data = langs_data();

	// aliases: ru >>> ru_RU
	if( isset( $langs_data[ $set_locale ] ) ){
		$set_locale = $langs_data[ $set_locale ]['locale'];
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

## получает URL флага по ID страниы
function flag_url_by_country_id( $country_id ){

	/* $data = array(
		1   => 'ru',
		2   => 'ua',
		3   => 'by',
		4   => 'kz',
		5   => 'az',
		6   => 'am',
		7   => 'ge',
		9   => 'us',
		11  => 'kg',
		12  => 'lv',
		13  => 'lt',
		14  => 'ee',
		15  => 'md',
		16  => 'tj',
		17  => 'tm',
		18  => 'uz',
		43  => 'br',
		65  => 'de',
		209 => 'fr',
		217 => 'ch',
		218 => 'se',
		71 	=> 'gr',
		215 => 'cz',
		87  => 'es',
		19  => 'au',
		39  => 'bg',
		200 => 'tr',
		81  => 'id',
	);

	$country_id = (int) $country_id;
	if( isset( $data[ $country_id ] ) )
		return I18N_URL . "img/flags/4x3/{$data[$country_id]}.svg";
	 */

	$country_id = (int) $country_id;

	$code = wp_list_filter( get_countries(), [ 'vk_id' => $country_id ] );

	if( isset( $code ) ){
		return I18N_URL . "img/flags/4x3/" . key( $code ) . ".svg";
	}

	return '';
}

## nav menu под текущий язык
function translate_menu(){

	$add_args = [
		'fallback_cb' => '__return_empty_string',
		'echo'        => 0,
		'container'   => '',
		'menu_class'  => ''
	];

	$menu = wp_nav_menu( [ 'theme_location' => 'main_menu_' . current_lang() ] + $add_args );

	if( ! empty( $menu ) ){
		return $menu;
	}

	return wp_nav_menu( [ 'theme_location' => 'main_menu_ru' ] + $add_args );
}

// HTML ----

## переключение между языками
function switch_lang_links_html(){
	$items = [];

	foreach( langs_data() as $lang => $data ){
		$url = "/$lang/". preg_replace('~^/(?:'. Langs()->langs_regex .')/~', '', $_SERVER['REQUEST_URI'] );

		$items[] = '
		<li class="langs__item">
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

