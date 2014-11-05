<?php
/**
 * @package WPOD
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPOD\Components;

class Section extends ComponentBase
{
  protected function get_defaults()
  {
    $defaults = array(
      'title'           => __( 'Section title', 'wpod' ),
      'description'     => '',
      'callback'        => array( $this, 'default_callback' ),
      'callback_args'   => array(), //TODO: pass automatic args and disable this parameter
    );
    return apply_filters( 'wpod_section_defaults', $defaults );
  }
}
