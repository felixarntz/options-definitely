<?php
/*
Plugin Name: Options, Definitely
Plugin URI: http://wordpress.org/plugins/options-definitely/
Description: This plugin makes adding options to the WordPress admin area and customizer very simple, yet flexible. It all works using a filter and an array.
Version: 1.0.0
Author: Felix Arntz
Author URI: http://leaves-and-love.net
License: GNU General Public License v2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wpod
Domain Path: /languages/
Tags: wordpress, plugin, options, admin, backend, ui, customizer, framework
*/
/**
 * @package WPOD
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

define( 'WPOD_NAME', 'Options, Definitely' );
define( 'WPOD_VERSION', '1.0.0' );
define( 'WPOD_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'WPOD_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );

require_once WPOD_PATH . '/vendor/felixarntz/leavesandlove-wp-plugin-util/leavesandlove-wp-plugin-util.php';

function wpod_init() {
	$plugin = LaL_WP_Plugin_Util::get( 'options-definitely', array(
		'name'					=> WPOD_NAME,
		'version'				=> WPOD_VERSION,
		'required_wp'			=> '4.0',
		'required_php'			=> '5.3.0',
		'main_file'				=> __FILE__,
		'autoload_namespace'	=> 'WPOD',
		'autoload_path'			=> WPOD_PATH . '/inc/WPOD',
		'textdomain'			=> 'wpod',
	) );

	if ( $plugin->do_version_check() ) {
		$plugin->load_textdomain();

		require_once WPOD_PATH . '/inc/functions.php';

		\WPOD\Framework::instance();
	}
}
add_action( 'plugins_loaded', 'wpod_init' );
