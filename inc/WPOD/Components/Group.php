<?php
/**
 * @package WPOD
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPOD\Components;

class Group extends Component_Base
{
  public function is_already_added()
  {
    global $admin_page_hooks;

    if( isset( $admin_page_hooks[ $this->slug ] ) )
    {
      return true;
    }
    return false;
  }

  public function validate()
  {
    parent::validate();
    $this->args['added'] = false;
    $this->args['subslug'] = $this->slug;
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
