<?php
/**
 * @package WPOD
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPOD;

class Validator
{
  public static function checkbox( $value, $field )
  {
    $value = absint( $value );
    
    if( $value > 0 )
    {
      return true;
    }
    return false;
  }

  public static function select( $value, $field )
  {
    if( isset( $field->options[ $value ] ) )
    {
      return $value;
    }
    return self::error_handler( sprintf( __( '%s is no valid choice for this field.', 'wpod' ), esc_attr( $value ) ) );
  }

  public static function radio( $value, $field )
  {
    return self::select( $value, $field );
  }

  public static function multiselect( $value, $field )
  {
    $validated = array();
    $invalid = array();
    if( !is_array( $value ) )
    {
      $value = array( $value );
    }
    foreach( $value as $val )
    {
      if( isset( $field['options'][ $val ] ) )
      {
        $validated[] = $val;
      }
      else
      {
        $invalid[] = $val;
      }
    }
    if( count( $invalid ) == 0 )
    {
      return $validated;
    }
    return self::error_handler( sprintf( __( 'The values %s are not valid options for this field.', 'wpod' ), implode( ', ', $invalid ) ) );
  }

  public static function multibox( $value, $field )
  {
    return self::multiselect( $value, $field );
  }

  public static function number( $value, $field )
  {
    if( isset( $field->more_attributes['step'] ) && is_int( $field->more_attributes['step'] ) )
    {
      $value = intval( $value );
    }
    else
    {
      $value = floatval( $value );
    }
    if( !isset( $field->more_attributes['step'] ) || $value % $field->more_attributes['step'] == 0 )
    {
      if( !isset( $field->more_attributes['min'] ) || $value >= $field->more_attributes['min'] )
      {
        if( !isset( $field->more_attributes['max'] ) || $value <= $field->more_attributes['max'] )
        {
          return $value;
        }
        return self::error_handler( sprintf( __( 'The number %1$s is invalid. It must be lower than or equal to %2$s.', 'wpod' ), $value, $field->more_attributes['max'] ) );
      }
      return self::error_handler( sprintf( __( 'The number %1$s is invalid. It must be greater than or equal to %2$s.', 'wpod' ), $value, $field->more_attributes['min'] ) );
    }
    return self::error_handler( sprintf( __( 'The number %1$s is invalid since it is not divisible by %2$s.', 'wpod' ), $value, $field->more_attributes['step'] ) );
  }

  public static function range( $value, $field )
  {
    return self::number( $value, $field );
  }

  public static function text( $value, $field )
  {
    return wp_kses_post( $value );
  }

  public static function textarea( $value, $field )
  {
    return self::text( $value, $field );
  }

  public static function email( $value, $field )
  {
    $old_mail = $value;
    $value = is_email( sanitize_email( $value ) );
    if( $value )
    {
      return $value;
    }
    return self::error_handler( sprintf( __( '%s is not a valid email address.', 'wpod' ), esc_attr( $old_mail ) ) );
  }

  public static function url( $value, $field )
  {
    if( preg_match( '#http(s?)://(.+)#i', $value ) )
    {
      return esc_url_raw( $value );
    }
    return self::error_handler( sprintf( __( '%s is not a valid URL.', 'wpof' ), esc_attr( $value ) ) );
  }

  public static function date( $value, $field )
  {
    $timestamp = mysql2date( 'U', $value );
    if( !isset( $field->more_attributes['min'] ) || $timestamp >= ( $timestamp_min = mysql2date( 'U', $field->more_attributes['min'] ) ) )
    {
      if( !isset( $field->more_attributes['max'] ) || $timestamp <= ( $timestamp_max = mysql2date( 'U', $field->more_attributes['max'] ) ) )
      {
        return $value;
      }
      return self::error_handler( sprintf( __( 'The date %1$s is invalid. It must not occur later than %2$s.', 'wpod' ), wpod_format_date( $timestamp ), wpod_format_date( $timestamp_max ) ) );
    }
    return self::error_handler( sprintf( __( 'The date %1$s is invalid. It must not occur earlier than %2$s.', 'wpod' ), wpod_format_date( $timestamp ), wpod_format_date( $timestamp_min ) ) );
  }

  public static function datetime( $value, $field )
  {
    $timestamp = mysql2date( 'U', $value );
    if( !isset( $field->more_attributes['min'] ) || $timestamp >= ( $timestamp_min = mysql2date( 'U', $field->more_attributes['min'] ) ) )
    {
      if( !isset( $field->more_attributes['max'] ) || $timestamp <= ( $timestamp_max = mysql2date( 'U', $field->more_attributes['max'] ) ) )
      {
        return $value;
      }
      return self::error_handler( sprintf( __( 'The date %1$s is invalid. It must not occur later than %2$s.', 'wpod' ), wpod_format_datetime( $timestamp ), wpod_format_datetime( $timestamp_max ) ) );
    }
    return self::error_handler( sprintf( __( 'The date %1$s is invalid. It must not occur earlier than %2$s.', 'wpod' ), wpod_format_datetime( $timestamp ), wpod_format_datetime( $timestamp_min ) ) );
  }

  public static function color( $value, $field )
  {
    if( preg_match( '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/i', $value ) )
    {
      return $value;
    }
    return self::error_handler( sprintf( __( '%s is not a valid hexadecimal color.', 'wpod' ), esc_attr( $value ) ) );
  }

  public static function media( $value, $field, $desired_types = 'all', $errmsg_append = '' )
  {
    $value = esc_url( $value );
    $id = wpod_get_attachment_id( $value );
    if( $id )
    {
      $mime = get_post_mime_type( $id );

      $mime_types = get_allowed_mime_types();
      
      if( $desired_types != 'all' )
      {
        if( is_string( $desired_types ) && isset( $mime_types[ $desired_types ] ) )
        {
          $mime_value = $mime_types[ $desired_types ];
          $mime_types = array( $desired_types => $mime_value );
        }
        elseif( is_array( $desired_types ) )
        {
          $mime_types = array_intersect_key( $mime_types, array_flip( $desired_types ) );
        }
      }

      if( in_array( $mime, $mime_types ) )
      {
        return $value;
      }
      if( !empty( $errmsg_append ) )
      {
        $errmsg_append = ' ' . $errmsg_append;
      }
      return self::error_handler( sprintf( __( 'The URL %s does not contain media content in any valid format.', 'wpod' ), esc_url( $value ) ) . $errmsg_append );
    }
    return self::error_handler( sprintf( __( 'The URL %s does not link to a WordPress media file.', 'wpod' ), esc_url( $value ) ) );
  }

  public static function image( $value, $field )
  {
    return self::media( $value, $field, array(
      'jpg|jpeg|jpe',
      'gif',
      'png',
      'bmp',
      'tif|tiff',
      'ico',
    ), __( 'It has to be an image file.', 'wpod' ) );
  }

  public static function video( $value, $field )
  {
    return self::media( $value, $field, array(
      'asf|asx',
      'wmv',
      'wmx',
      'wm',
      'avi',
      'divx',
      'flv',
      'mov|qt',
      'mpeg|mpg|mpe',
      'mp4|m4v',
      'ogv',
      'webm',
      'mkv',
    ), __( 'It has to be a video file.', 'wpod' ) );
  }

  public static function audio( $value, $field )
  {
    return self::media( $value, $field, array(
      'mp3|m4a|m4b',
      'ra|ram',
      'wav',
      'ogg|oga',
      'mid|midi',
      'wma',
      'wax',
      'mka',
    ), __( 'It has to be an audio file.', 'wpod' ) );
  }

  public static function archive( $value, $field )
  {
    return self::media( $value, $field, array(
      'tar',
      'zip',
      'gz|gzip',
      'rar',
      '7z',
    ), __( 'It has to be a file archive.', 'wpod' ) );
  }

  public static function favicon( $value, $field )
  {
    return self::media( $value, $field, 'ico', __( 'It has to be a favicon.', 'wpod' ) );
  }

  public static function pdf( $value, $field )
  {
    return self::media( $value, $field, 'pdf', __( 'It has to be a PDF file.', 'wpod' ) );
  }

  public static function is_valid_empty( $value, $field )
  {
    $empty = false;
    if( $field->type == 'checkbox' )
    {
      return $value;
    }
    switch( $field->type )
    {
      case 'multiselect':
      case 'multibox':
        $empty = count( $value );
        $empty = (bool) $empty;
        break;
      default:
        $empty = empty( $value );
    }
    if( !$empty || $empty && ( !isset( $field->more_attributes['required'] ) || !$field->more_attributes['required'] ) )
    {
      return $value;
    }
    return self::error_handler( __( 'The value must not be empty.', 'wpod' ) );
  }

  public static function invalid_validation_function()
  {
    return self::error_handler( __( 'The validation function specified is invalid. It does not exist.', 'wpod' ) );
  }

  private static function error_handler( $message )
  {
    return array( 'errmsg' => $message );
  }
}
