<?php
/**
 * @package WPOD
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPOD\Components;

class Group extends ComponentBase
{
  public function validate()
  {
    parent::validate();
    $this->args['slug'] = null;
    $this->args['added'] = false;
    $this->args['sublabel'] = false;
  }

  protected function get_defaults()
  {
    $defaults = array(
      'label'           => __( 'Group label', 'wpod' ),
      'icon'            => '',
      'position'        => null,
    );
    return apply_filters( 'wpod_group_defaults', $defaults );
  }
}
