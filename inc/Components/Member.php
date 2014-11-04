<?php
/**
 * @package WPOD
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPOD\Components;

class Member extends Component_Base
{
  protected function get_defaults()
  {
    $defaults = array(
      'title'           => __( 'Member title', 'wpod' ),
      'description'     => '',
      'capability'      => 'manage_options',
      'type'            => 'default',
      'callback'        => array( $this, 'default_callback' ),
      'callback_args'   => array(), //TODO: pass automatic args and disable this parameter
    );
    return apply_filters( 'wpod_member_defaults', $defaults );
  }
}
