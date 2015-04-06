<?php
/*
Plugin Name: WP Affiliate Manager
Plugin URI: https://wpaffiliatemanager.com
Description: Plugin to recruit, manage, track and pay your affiliates.
Version: 1.9.8
Author: wp.insider, wpaffiliatemgr
Author URI: https://wpaffiliatemanager.com
*/

if (!defined('ABSPATH')){
    exit; //Exit if accessed directly
}

global $wp_version;
$uploadDirInfo = wp_upload_dir();

define( 'WPAM_VERSION', '1.9.8' );
define( 'WPAM_DB_VERSION', '1.1' );
define( 'WPAM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'WPAM_PLUGIN_FILE', __FILE__ );
define( 'WPAM_BASE_DIRECTORY', dirname( __FILE__ ) );
define( 'WPAM_URL', plugins_url( '', __FILE__ ) );
define( 'WPAM_PATH', untrailingslashit(plugin_dir_path(__FILE__)));
define( 'WPAM_RESOURCES_DIR', WPAM_BASE_DIRECTORY . "/resources/" );
define( 'WPAM_DEBUG', false );
define( 'WPAM_LOCALE_OVERRIDE', false );
define( 'WPAM_CREATIVE_IMAGES_DIR', $uploadDirInfo['basedir'] . "/wpam/creatives/" );

load_plugin_textdomain( 'wpam', false, dirname( WPAM_PLUGIN_BASENAME ) . '/languages/' );

if ( version_compare( PHP_VERSION, '5.3.0' ) >= 0 ) {
	define( 'WPAM_PHP53', true );
} else if ( version_compare( PHP_VERSION, '5.1.0') >= 0 ) {
	define( 'WPAM_PHP51', true );
} else {
	wp_die( __( 'WordPress Affiliate Manager requires PHP 5.1 or higher.', 'wpam' ) );
}

if ( version_compare( $wp_version, '3.5.0' ) < 0 ) {
	wp_die( __( 'WordPress Affiliate Manager requires WordPress 3.5 or higher.', 'wpam' ) );
}

require_once WPAM_BASE_DIRECTORY . "/source/Plugin.php";
require_once WPAM_BASE_DIRECTORY . "/config.php";

$wpam_plugin = new WPAM_Plugin();
register_activation_hook( __FILE__, array( $wpam_plugin, 'onActivation' ) );
