<?php
/**
 * @package WPOD
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPOD\Components;

class Group extends ComponentBase
{
  protected function get_defaults()
  {
    $defaults = array(
      'label'           => __( 'Group label', 'wpod' ),
      'icon'            => '', // either a URL to an image (16x16) or a dashicon slug
      'position'        => null,
    );
    return apply_filters( 'wpod_group_defaults', $defaults );
  }
}
