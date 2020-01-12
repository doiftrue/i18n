<?php

/**
 * Plugin Name: i18n
 * Description: Adds `/ru`, `/en` ... prefix to URL. Save current lang to cookies.
 * Author:      Kama
 * Author URI:  https://wp-kama.ru/
 * Version:     1.1
 */

// TODO добавить и проверять на автомате исключение в ЧПУ или htaccess для hybridauth - RewriteRule ^(content/.*/hybridauth/.*) $1 [L]

define( 'I18N_PATH', plugin_dir_path( __FILE__ ) );
define( 'I18N_URL',  plugin_dir_url( __FILE__ ) );
define( 'I18N_MUPLUG_INSTALL', strpos(__DIR__, '/mu-plugins/') );

require_once I18N_PATH .'class-I18n_Rewrite_Rules.php';
require_once I18N_PATH .'class-Langs.php';

require_once I18N_PATH .'functions.php';

// Эти файлы не относятся к ядру плагина и могут меняться от проекта к проекту:
//require_once I18N_PATH .'example_functions.php';
//require_once I18N_PATH .'example_hooks.php';

I18N_MUPLUG_INSTALL ?
	add_action( 'muplugins_loaded', 'i18n_init' ) :
	add_action( 'plugins_loaded', 'i18n_init' );
function i18n_init() {
	Langs::init();
}

