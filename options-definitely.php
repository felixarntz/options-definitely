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

require_once WPOD_PATH . '/vendor/autoload.php';
require_once WPOD_PATH . '/functions.php';

define( 'WPOD_NAME', __( 'Options, Definitely', 'wpod' ) );
define( 'WPOD_VERSION', '1.0.0' );
define( 'WPOD_REQUIRED_WP', '4.0.0' );

define( 'WPOD_MAINFILE', __FILE__ );
define( 'WPOD_PATH', untrailingslashit( plugin_dir_path( WPOD_MAINFILE ) ) );
define( 'WPOD_URL', untrailingslashit( plugin_dir_url( WPOD_MAINFILE ) ) );
define( 'WPOD_BASENAME', plugin_basename( WPOD_MAINFILE ) );

define( 'WPOD_RUNNING', wpod_version_check() );

function wpod_init()
{
  if( WPOD_RUNNING )
  {
    WPOD\Framework::instance();
  }
  else
  {
    add_action( 'admin_notices', 'wpod_display_version_error_notice' );
  }
}
add_action( 'plugins_loaded', 'wpod_init' );
