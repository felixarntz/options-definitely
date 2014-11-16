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

define( 'WPOD_VERSION', '1.0.0' );
define( 'WPOD_REQUIRED_WP', '4.0' );
define( 'WPOD_REQUIRED_PHP', '5.3.0' );

define( 'WPOD_NAME', 'Options, Definitely' );
define( 'WPOD_MAINFILE', __FILE__ );
define( 'WPOD_BASENAME', plugin_basename( WPOD_MAINFILE ) );
define( 'WPOD_PATH', untrailingslashit( plugin_dir_path( WPOD_MAINFILE ) ) );
define( 'WPOD_URL', untrailingslashit( plugin_dir_url( WPOD_MAINFILE ) ) );

require_once WPOD_PATH . '/inc/functions.php';

define( 'WPOD_RUNNING', wpod_version_check() );

function wpod_init()
{
  load_plugin_textdomain( 'wpod', false, dirname( WPOD_BASENAME ) . '/languages/' );

  if( WPOD_RUNNING > 0 )
  {
    require_once WPOD_PATH . '/vendor/autoload.php';
    \WPOD\Framework::instance();
  }
  else
  {
    add_action( 'admin_notices', 'wpod_display_version_error_notice' );
  }
}
add_action( 'plugins_loaded', 'wpod_init' );

function wpod_test( $options )
{
  $options['theme']['sets'] = array(
    'wpod_theme'          => array(
      'title'               => 'Theme Options (WPOD)',
      'label'               => 'Theme Options (WPOD)',
      'description'         => 'These are some theme options.',
      'capability'          => 'edit_theme_options',
      'members'             => array(
        'wpod_design'         => array(
          'title'               => 'Design',
          'description'         => 'Here you can modify options concerning the design of your web site.',
          'capability'          => 'edit_theme_options',
          'mode'                => 'draggable',
          'sections'            => array(),
        ),
        'wpod_layout'         => array(
          'title'               => 'Layout',
          'description'         => 'Here you can choose between some predefined layouts.',
          'capability'          => 'edit_theme_options',
          'mode'                => 'draggable',
          'sections'            => array(
            'sidebar_layout'      => array(
              'title'               => 'Sidebar',
              'description'         => 'Adjust parameters for the sidebar/s.',
              'fields'              => array(
                'sidebars'            => array(
                  'title'               => 'Sidebars to show',
                  'description'         => 'Select which sidebars to show.',
                  'type'                => 'select',
                  'default'             => 'right',
                  'options'             => array(
                    'none'                => 'No sidebars',
                    'left'                => 'Only left sidebar',
                    'right'               => 'Only right sidebar',
                    'both'                => 'Both sidebars',
                  ),
                ),
                'sidebar_width'       => array(
                  'title'               => 'Sidebar Width',
                  'description'         => 'Choose a sidebar width (in pixels).',
                  'type'                => 'number',
                  'default'             => 200,
                  'more_attributes'     => array(
                    'min'                 => 100,
                    'max'                 => 300,
                    'step'                => 2,
                  ),
                ),
                'sidebar_file'        => array(
                  'title'               => 'Sidebar File',
                  'description'         => 'Upload some test file.',
                  'type'                => 'media',
                  'default'             => '',
                ),
                'sidebar_text'        => array(
                  'title'               => 'Sidebar Text',
                  'description'         => 'Write a text in the WP Editor.',
                  'type'                => 'wysiwyg',
                  'default'             => '',
                  'more_attributes'     => array(
                    'rows'                => 7,
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    ),
  );
  
  $areas = array( 'header', 'content', 'sidebar', 'footer' );
  foreach( $areas as $area )
  {
    $fields = array();
    $fields[ $area . '_font' ] = array(
      'title'             => 'Font',
      'description'       => 'Here you can choose the font for the ' . $area . '.',
      'type'              => 'radio',
      'default'           => 'verdana',
      'options'           => array(
        'arial'             => array( 'label' => 'Arial', 'color' => '#ff0000' ),
        'calibri'           => array( 'label' => 'Calibri', 'color' => '#ff00ff' ),
        'comicsans'         => 'Comic Sans',
        'timesnewroman'     => 'Times New Roman',
        'trebuchet'         => 'Trebuchet',
        'verdana'           => 'Verdana',
      ),
    );
    $fields[ $area . '_font_size'] = array(
      'title'             => 'Font Size',
      'description'       => 'Here you can adjust the font size for the ' . $area . '.',
      'type'              => 'range',
      'default'           => 14,
      'more_attributes'   => array(
        'min'               => 8,
        'max'               => 36,
        'step'              => 2,
      ),
    );
    $fields[ $area . '_font_color'] = array(
      'title'             => 'Color',
      'description'       => 'Here you can adjust the font color for the ' . $area . '.',
      'type'              => 'color',
      'default'           => '#333333',
    );
    $options['theme']['sets']['wpod_theme']['members']['wpod_design']['sections'][ $area . '_design' ] = array(
      'title'             => ucfirst( $area ) . ' Design',
      'description'       => 'Here you can modify the looks of the ' . $area . '.',
      'fields'            => $fields,
    );
  }

  return $options;
}
add_filter( 'wpod', 'wpod_test' );

function wpod_test_query()
{
  $results = \WPOD\Framework::instance()->query( array(
    'slug'        => 'header_font_color',
    'type'        => 'field',
    'parent_slug' => 'wpod_design',
    'parent_type' => 'member',
  ) );
  $count = count( $results );
  echo $count . '<br><br>';
  print_r( $results );
}
//add_action( 'admin_notices', 'wpod_test_query' );
