<?php
/**
 * @package WPOD
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPOD\Components;

class Field extends Component_Base
{
  protected function validate()
  {
    if( isset( $this->args['type'] ) )
    {
      if( !isset( $this->args['validate'] ) )
      {
        if( method_exists( 'Validator', $this->args['type'] ) )
        {
          $this->args['validate'] = array( 'Validator', $this->args['type'] );
        }
      }
    }
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
