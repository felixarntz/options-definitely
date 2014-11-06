<?php
/**
 * @package WPOD
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPOD\Components;

class Field extends ComponentBase
{
  public function register( $parent_member, $parent_section )
  {
    add_settings_field( $this->slug, $this->args['title'], array( $this, 'render' ), $parent_member->slug, $parent_section->slug );
  }

  public function render()
  {
    //TODO: render
    echo '<p>' . $this->slug . '</p>';
  }

  public function validate()
  {
    if( isset( $this->args['type'] ) )
    {
      if( !isset( $this->args['validate'] ) )
      {
        if( method_exists( '\\WPOD\\Validator', $this->args['type'] ) )
        {
          $this->args['validate'] = array( '\\WPOD\\Validator', $this->args['type'] );
        }
      }
    }
    //TODO: further validate
    parent::validate();
  }

  protected function get_defaults()
  {
    $defaults = array(
      'title'           => __( 'Field title', 'wpod' ),
      'description'     => '',
      'type'            => 'text',
      'default'         => '',
      'options'         => array(),
      'validate'        => 'esc_html',
      'class'           => '',
      'more_attributes' => array(),
    );
    return apply_filters( 'wpod_field_defaults', $defaults );
  }
}
