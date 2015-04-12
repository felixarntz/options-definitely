<?php
/*
Plugin Name: Options, Definitely
Plugin URI: http://wordpress.org/plugins/options-definitely/
Description: This plugin makes adding options to the WordPress admin area very simple, yet flexible. It all works using a filter and an array.
Version: 1.0.0
Author: Felix Arntz
Author URI: http://leaves-and-love.net
License: GNU General Public License v2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wpod
Domain Path: /languages/
Tags: wordpress, plugin, options, admin, backend, ui, framework
*/
/**
 * @package WPOD
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

define( 'WPOD_NAME', 'Options, Definitely' );
define( 'WPOD_VERSION', '1.0.0' );
define( 'WPOD_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'WPOD_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );

/**
 * Initializes the plugin.
 *
 * The functions.php file of the plugin is loaded.
 * Afterwards the WPOD\Framework class is instantiated to initialize the plugin.
 *
 * @internal
 * @since 1.0.0
 * @return void
 */
function wpod_init() {
	require_once WPOD_PATH . '/inc/functions.php';

	\WPOD\Framework::instance();
}

/**
 * Checks plugin requirements and initialized the plugin if possible.
 *
 * It is checked whether the Standard PHP Library function spl_autoload_register() is available.
 * If so, the autoload file is included and the plugin utility class is instantiated.
 * The utility class will then take care of checking PHP and WordPress version requirements of the plugin
 * and, if everything is alright, will hook the wpod_init() function into the 'plugins_loaded' action.
 *
 * If any requirement is not met, the utility class will deactivate the plugin and show an admin notice.
 *
 * @internal
 * @since 1.0.0
 * @return void
 */
function wpod_maybe_init() {
	$spl_available = function_exists( 'spl_autoload_register' );

	if ( $spl_available && file_exists( WPOD_PATH . '/vendor/autoload_52.php' ) ) {
		require_once WPOD_PATH . '/vendor/autoload_52.php';
	} elseif ( file_exists( WPOD_PATH . '/vendor/felixarntz/leavesandlove-wp-plugin-util/leavesandlove-wp-plugin-util.php' ) ) {
		require_once WPOD_PATH . '/vendor/felixarntz/leavesandlove-wp-plugin-util/leavesandlove-wp-plugin-util.php';
	}

	if ( class_exists( 'LaL_WP_Plugin_Util' ) ) {
		$plugin = LaL_WP_Plugin_Util::get( 'wpod', array(
			'name'			=> WPOD_NAME,
			'version'		=> WPOD_VERSION,
			'required_wp'	=> '4.0',
			'required_php'	=> '5.3.0',
			'main_file'		=> __FILE__,
			'textdomain'	=> 'wpod',
		) );

		$plugin->maybe_init( 'wpod_init', $spl_available );
	}
}
wpod_maybe_init();
