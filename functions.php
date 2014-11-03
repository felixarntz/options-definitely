<?php
/**
 * @package WPOD
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

function wpod_version_check()
{
  global $wp_version;

  if( version_compare( $wp_version, WPOD_REQUIRED_WP ) < 0 )
  {
    return false;
  }
  return true;
}

function wpod_display_version_error_notice()
{
  global $wp_version;

  echo '<div class="error">';
  echo '<p>';
  echo '<strong>' . WPOD_NAME . '</strong> ';
  printf( __( 'The plugin requires WordPress version %1$s. However, you are currently using version %2$s.', 'wpod' ), WPOD_REQUIRED_WP, $wp_version );
  echo '</p>';
  echo '<p>' . __( 'Please update WordPress to run it.', 'wpod' ) . '</p>';
  echo '</div>';
}