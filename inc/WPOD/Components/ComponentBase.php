<?php
/**
 * @package WPOD
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPOD\Components;

abstract class ComponentBase
{
  protected $slug = '';
  protected $parent = '';
  protected $args = array();

  public function __construct( $slug, $args, $parent = '' )
  {
    $this->slug = $slug;
    $this->parent = $parent;
    $this->args = $args;
  }

  public function __set( $property, $value )
  {
    if( method_exists( $this, $method = 'set_' . $property ) )
    {
      $this->$method( $value );
    }
    elseif( property_exists( $this, $property ) )
    {
      $this->$property = $value;
    }
    elseif( isset( $this->args[ $property ] ) )
    {
      $this->args[ $property ] = $value;
    }
  }

  public function __get( $property )
  {
    if( method_exists( $this, $method = 'get_' . $property ) )
    {
      return $this->$method();
    }
    elseif( property_exists( $this, $property ) )
    {
      return $this->$property;
    }
    elseif( isset( $this->args[ $property ] ) )
    {
      return $this->args[ $property ];
    }
    return null;
  }

  public function validate()
  {
    $this->args = wp_parse_args( $this->args, $this->get_defaults() );
    $types = \WPOD\Framework::instance()->get_type_whitelist();
    foreach( $types as $type )
    {
      $t = $type . 's';
      if( isset( $this->args[ $t ] ) )
      {
        unset( $this->args[ $t ] );
      }
    }
  }

  protected abstract function get_defaults();
}
