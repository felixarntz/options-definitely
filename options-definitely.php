<?php
/*
Plugin Name: Options, Definitely
Plugin URI: http://wordpress.org/plugins/options-definitely/
Description: This framework plugin makes adding options to the WordPress admin area very simple, yet flexible. It all works using a filter and an array.
Version: 0.5.0
Author: Felix Arntz
Author URI: http://leaves-and-love.net
License: GNU General Public License v2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wpod
Domain Path: /languages/
Tags: wordpress, plugin, framework, library, developer, options, admin, backend, ui
*/
/**
 * @package WPOD
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {

	require_once dirname( __FILE__ ) . '/vendor/autoload.php';

	\LaL_WP_Plugin_Loader::load_plugin( array(
		'slug'				=> 'options-definitely',
		'name'				=> 'Options, Definitely',
		'version'			=> '0.5.0',
		'main_file'			=> __FILE__,
		'namespace'			=> 'WPOD',
		'textdomain'		=> 'wpod',
		'autoload_files'	=> array( 'inc/functions.php' ),
	), array(
		'phpversion'		=> '5.3.0',
		'wpversion'			=> '4.0',
	) );

}
