<?php
/**
 * @package WPOD
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

function wpod_get_options( $member_slug )
{
  $options = get_option( $member_slug );
  if( !$options )
  {
    $options = array();
  }
  $fields = WPOD\Framework::instance()->query( array(
    'type'          => 'field',
    'parent_slug'   => $member_slug,
    'parent_type'   => 'member',
  ) );
  foreach( $fields as $field )
  {
    if( !isset( $options[ $field->slug ] ) )
    {
      $options[ $field->slug ] = $field->default;
    }
  }
  return $options;
}

function wpod_get_option( $member_slug, $field_slug )
{
  $options = get_option( $member_slug );
  if( isset( $options[ $field_slug ] ) )
  {
    return $options[ $field_slug ];
  }
  $field = WPOD\Framework::instance()->query( array(
    'slug'          => $field_slug,
    'type'          => 'field',
    'parent_slug'   => $member_slug,
    'parent_type'   => 'member',
  ), true );
  if( $field )
  {
    return $field->default;
  }
  return false;
}

function wpod_version_check()
{
  global $wp_version;

  $ret = 1;
  if( version_compare( $wp_version, WPOD_REQUIRED_WP ) < 0 )
  {
    $ret -= 1;
  }
  if( version_compare( phpversion(), WPOD_REQUIRED_PHP ) < 0 )
  {
    $ret -= 2;
  }
  return $ret;
}

function wpod_display_version_error_notice()
{
  global $wp_version;

  echo '<div class="error">';
  echo '<p>' . sprintf( __( 'Fatal problem with %s', 'wpod' ), '<strong>' . WPOD_NAME . ':</strong>' ) . '</p>';
  if( WPOD_RUNNING != -1 )
  {
    echo '<p>';
    printf( __( 'The plugin requires WordPress version %1$s. However, you are currently using version %2$s.', 'wpod' ), WPOD_REQUIRED_WP, $wp_version );
    echo '</p>';
  }
  if( WPOD_RUNNING != 0 )
  {
    echo '<p>';
    printf( __( 'The plugin requires PHP version %1$s. However, you are currently using version %2$s.', 'wpod' ), WPOD_REQUIRED_PHP, phpversion() );
    echo '</p>';
  }
  echo '<p>' . __( 'Please update the above resources to run it.', 'wpod' ) . '</p>';
  echo '</div>';
}

function wpod_format_int( $number )
{
  return number_format_i18n( $number, 0 );
}

function wpod_format_float( $number, $decimals = 2 )
{
  return number_format_i18n( $number, $decimals );
}

function wpod_format_date( $formatstring_or_timestamp )
{
  if( !is_int( $formatstring_or_timestamp ) )
  {
    $formatstring_or_timestamp = mysql2date( 'U', $formatstring_or_timestamp );
  }
  return date_i18n( get_option( 'date_format' ), $formatstring_or_timestamp );
}

function wpod_format_datetime( $formatstring_or_timestamp )
{
  if( !is_int( $formatstring_or_timestamp ) )
  {
    $formatstring_or_timestamp = mysql2date( 'U', $formatstring_or_timestamp );
  }
  return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $formatstring_or_timestamp );
}

function wpod_get_attachment_id( $attachment_url )
{
  global $wpdb;
  $attachment_id = false;
  
  if( !empty( $attachment_url ) )
  {
    $upload_dir_paths = wp_upload_dir();
    
    if( false !== strpos( $attachment_url, $upload_dir_paths['baseurl'] ) )
    {
      $attachment_url = preg_replace( '/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '', $attachment_url );
   
      $attachment_url = str_replace( $upload_dir_paths['baseurl'] . '/', '', $attachment_url );
   
      $attachment_id = $wpdb->get_var( $wpdb->prepare( "SELECT wposts.ID FROM $wpdb->posts wposts, $wpdb->postmeta wpostmeta WHERE wposts.ID = wpostmeta.post_id AND wpostmeta.meta_key = '_wp_attached_file' AND wpostmeta.meta_value = '%s' AND wposts.post_type = 'attachment'", $attachment_url ) );
    }
  }
  return $attachment_id;
}

function wpod_is_image( $attachment_url )
{
  $filename = explode( '.', $attachment_url );
  $last = count( $filename ) - 1;
  if( $last > 0 )
  {
    $extension = $filename[ $last ];
    if( in_array( $extension, array( 'bmp', 'jpg', 'jpeg', 'png', 'gif' ) ) )
    {
      return true;
    }
  }
  return false;
}

function wpod_make_html_attributes( $atts, $html5 = true, $echo = true )
{
  $output = '';
  uasort( $atts, 'wpod_sort_attributes' );
  foreach( $atts as $key => $value )
  {
    if( is_bool( $value ) )
    {
      if( $value )
      {
        if( $html5 )
        {
          $output .= ' ' . $key;
        }
        else
        {
          $output .= ' ' . $key . '="' . esc_attr( $key ) . '"';
        }
      }
    }
    else
    {
      $output .= ' ' . $key . '="' . esc_attr( $value ) . '"';
    }
  }
  if( $echo )
  {
    echo $output;
  }
  return $output;
}

/* CALLBACK HELPER FUNCTIONS */

function wpod_component_to_slug( $component )
{
  return $component->slug;
}

function wpod_current_user_can( $component )
{
  $cap = $component->capability;
  if( $cap === null || current_user_can( $cap ) )
  {
    return true;
  }
  return false;
}

function wpod_sort_attributes( $a, $b )
{
  if( is_bool( $a ) && !is_bool( $b ) )
  {
    return 1;
  }
  elseif( !is_bool( $a ) && is_bool( $b ) )
  {
    return -1;
  }
  return 0;
}

/* ERROR HANDLING FUNCTIONS */

function wpod_doing_it_wrong( $function, $message, $version )
{
  if( WP_DEBUG && apply_filters( 'doing_it_wrong_trigger_error', true ) )
  {
    $version = !empty( $version ) ? sprintf( __( 'This message was added in %1$s version %2$s', 'wpod' ), '&quot;' . WPOD_NAME . '&quot;', $version ) : '';
    trigger_error( sprintf( __( '%1$s was called <strong>incorrectly</strong>: %2$s %3$s', 'wpod' ), $function, $message, $version ) );
  }
}

function wpod_deprecated_function( $function, $version, $replacement = null )
{
  if( WP_DEBUG && apply_filters( 'deprecated_function_trigger_error', true ) )
  {
    if( $replacement === null )
    {
      trigger_error( sprintf( __( '%1$s is <strong>deprecated</strong> as of %4$s version %2$s with no alternative available.', 'wpod' ), $function, $version, '', '&quot;' . WPOD_NAME . '&quot;' ) );
    }
    else
    {
      trigger_error( sprintf( __( '%1$s is <strong>deprecated</strong> as of %4$s version %2$s. Use %3$s instead!', 'wpod' ), $function, $version, $replacement, '&quot;' . WPOD_NAME . '&quot;' ) );
    }
  }
}
