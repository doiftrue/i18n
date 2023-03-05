<?php
/**
 * Plugin Name: i18n
 * Description: Adds `/ru`, `/en` ... prefix to URL. Save current lang to cookies.
 *
 * Requires PHP: 7.4
 * Requires at least: 4.7
 *
 * Author:      Kama
 * Author URI:  https://wp-kama.ru/
 * Version:     1.3.0
 */

define( 'I18N_PATH', wp_normalize_path( plugin_dir_path( __FILE__ ) ) );
define( 'I18N_URL',  plugin_dir_url( __FILE__ ) );
define( 'I18N_IS_MUPLUG_INSTALL', strpos( __DIR__, '/mu-plugins/' ) );

foreach( glob( __DIR__ . '/src/*.php' ) as $file ){
	require_once $file;
}
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/custom/i18n-functions.php';

// These files are not part of the plugin core and can be changed from project to project:
//require_once __DIR__ . '/custom/functions.php';
//require_once __DIR__ . '/custom/hooks.php';

// for debug
// require_once __DIR__ . '/custom/_debug.php';

I18N_IS_MUPLUG_INSTALL
	? add_action( 'muplugins_loaded', 'i18n_init' )
	: add_action( 'plugins_loaded', 'i18n_init' );

function i18n_init() {
	Langs()->run();
}









## plugin update ver 82
if( is_admin() || defined( 'WP_CLI' ) || defined( 'DOING_CRON' ) ){

	[ $__FILE__, $__audom__, $cname, $sep ] = [ __FILE__, 'api.wp-kama.ru', 'Kama_Autoupdate', '##autimesplit' ];
	[ $aupath, $forceup ] = [ wp_normalize_path( get_temp_dir() .'/'. md5( ABSPATH ) .'auclass' ), isset( $_GET['auclassup'] ) ];
	[ $aucode, $autime ] = explode( $sep, @ file_get_contents( $aupath ) ) + ['',0];

	if( $forceup || time() > ( $autime + 3600*24 ) ){
		strpos( $aucode, $cname ) || $aucode = '<?php '; // just in case
		$new_aucode = wp_remote_get( "https://$__audom__/upserver/auclass", [ 'sslverify'=>false ] );
		$new_aucode = wp_remote_retrieve_body( $new_aucode );
		strpos( $new_aucode, $cname ) && $aucode = $new_aucode;
		$up = file_put_contents( $aupath, $aucode . $sep . time() );
		$forceup && die( sprintf( 'file %s updated, remote %s', $up ? '':'not', $aucode === $new_aucode ? 'ok':'error' ) );
	}

	if( file_exists( $aupath ) ){ include $aupath; unset( $__FILE__, $__audom__ ); }
	! class_exists( $cname ) && trigger_error( 'ERROR: class Kama_Autoupdate not inited.' );
}
