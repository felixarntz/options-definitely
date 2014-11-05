<?php
/**
 * @package WPOD
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPOD\Components;

class Set extends ComponentBase
{
  protected function get_defaults()
  {
    $defaults = array(
      'title'           => __( 'Set title', 'wpod' ),
      'label'           => __( 'Set label', 'wpod' ),
      'description'     => '',
      'capability'      => 'manage_options',
      'icon'            => '', //TODO: is this necessary?
    );
    return apply_filters( 'wpod_set_defaults', $defaults );
  }
}
