<?php
/*
Plugin Name: Options Definitely
Plugin URI: https://wordpress.org/plugins/options-definitely/
Description: This framework plugin makes adding options screens with sections and fields to WordPress very simple, yet flexible.
Version: 0.5.1
Author: Felix Arntz
Author URI: http://leaves-and-love.net
License: GNU General Public License v3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Text Domain: options-definitely
Domain Path: /languages/
Tags: wordpress, plugin, definitely, framework, library, developer, admin, backend, structured data, ui, api, cms, options, settings, settings screen, tabs, sections, metaboxes, fields, help tabs
*/
/**
 * @package WPOD
 * @version 0.5.1
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPOD\App' ) && file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
	if ( version_compare( phpversion(), '5.3.0' ) >= 0 ) {
		require_once dirname( __FILE__ ) . '/vendor/autoload.php';
	} else {
		require_once dirname( __FILE__ ) . '/vendor/felixarntz/leavesandlove-wp-plugin-util/leavesandlove-wp-plugin-loader.php';
	}
}

LaL_WP_Plugin_Loader::load_plugin( array(
	'slug'				=> 'options-definitely',
	'name'				=> 'Options Definitely',
	'version'			=> '0.5.1',
	'main_file'			=> __FILE__,
	'namespace'			=> 'WPOD',
	'textdomain'		=> 'options-definitely',
), array(
	'phpversion'		=> '5.3.0',
	'wpversion'			=> '4.0',
) );
