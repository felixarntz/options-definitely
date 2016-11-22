<?php
/*
Plugin Name: Options Definitely
Plugin URI:  https://wordpress.org/plugins/options-definitely/
Description: This framework plugin makes adding options screens with sections and fields to WordPress very simple, yet flexible.
Version:     0.6.6
Author:      Felix Arntz
Author URI:  https://leaves-and-love.net
License:     GNU General Public License v3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Text Domain: options-definitely
Tags:        definitely, framework, admin, options, settings, settings screen, tabs, sections, metaboxes
*/
/**
 * Plugin initialization file
 *
 * @package WPOD
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 * @since 0.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( version_compare( phpversion(), '5.3.0' ) >= 0 && ! class_exists( 'WPOD\App' ) ) {
	if ( file_exists( dirname( __FILE__ ) . '/options-definitely/vendor/autoload.php' ) ) {
		require_once dirname( __FILE__ ) . '/options-definitely/vendor/autoload.php';
	} elseif ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
		require_once dirname( __FILE__ ) . '/vendor/autoload.php';
	}
} elseif ( ! class_exists( 'LaL_WP_Plugin_Loader' ) ) {
	if ( file_exists( dirname( __FILE__ ) . '/options-definitely/vendor/felixarntz/leavesandlove-wp-plugin-util/leavesandlove-wp-plugin-loader.php' ) ) {
		require_once dirname( __FILE__ ) . '/options-definitely/vendor/felixarntz/leavesandlove-wp-plugin-util/leavesandlove-wp-plugin-loader.php';
	} elseif ( file_exists( dirname( __FILE__ ) . '/vendor/felixarntz/leavesandlove-wp-plugin-util/leavesandlove-wp-plugin-loader.php' ) ) {
		require_once dirname( __FILE__ ) . '/vendor/felixarntz/leavesandlove-wp-plugin-util/leavesandlove-wp-plugin-loader.php';
	}
}

LaL_WP_Plugin_Loader::load_plugin( array(
	'slug'					=> 'options-definitely',
	'name'					=> 'Options Definitely',
	'version'				=> '0.6.6',
	'main_file'				=> __FILE__,
	'namespace'				=> 'WPOD',
	'textdomain'			=> 'options-definitely',
	'use_language_packs'	=> true,
	'is_library'			=> true,
), array(
	'phpversion'			=> '5.3.0',
	'wpversion'				=> '4.0',
) );
