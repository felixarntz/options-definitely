<?php
/**
 * @package WPOD
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPOD\Components;

class Set extends ComponentBase
{
  protected $page_hook = '';
  protected $help = array();

  public function add_to_menu()
  {
    $group = \WPOD\Framework::instance()->query( array(
      'slug'        => $this->parent,
      'type'        => 'group',
    ), true );
    $function_name = 'add_' . $group->slug . '_page';
    if( function_exists( $function_name ) && !in_array( $group->slug, array( 'menu', 'submenu' ) ) )
    {
      $this->page_hook = call_user_func( $function_name, $this->args['title'], $this->args['label'], $this->args['capability'], $this->slug, array( $this, 'render' ) );
    }
    else
    {
      if( $group->added )
      {
        $this->page_hook = add_menu_page( $this->args['title'], $group->label, $this->args['capability'], $this->slug, array( $this, 'render' ), $group->icon, $group->position );
        \WPOD\Framework::instance()->update( $group->slug, 'group', array(
          'slug'        => $this->slug,
          'added'       => true,
          'sublabel'    => $this->args['label'],
        ) );
      }
      else
      {
        $this->page_hook = add_submenu_page( $group->slug, $this->args['title'], $this->args['label'], $this->args['capability'], $this->slug, array( $this, 'render' ) );
        if( $group->sublabel !== true )
        {
          global $submenu;
          if( isset( $submenu[ $group->slug ] ) )
          {
            $submenu[ $group->slug ][0][0] = $group->sublabel;
            \WPOD\Framework::instance()->update( $group->slug, 'group', array(
              'sublabel'    => true,
            ) );
          }
        }
      }
    }
    return $this->page_hook;
  }

  public function render()
  {

  }

  public function render_help()
  {
    foreach( $this->args['help']['tabs'] as $slug => $tab )
    {

    }
    if( !empty( $this->args['help']['sidebar'] ) )
    {

    }
  }

  public function validate()
  {
    parent::validate();
    if( !is_array( $this->args['help'] ) )
    {
      $this->args['help'] = array();
    }
    if( !isset( $this->args['help']['tabs'] ) || !is_array( $this->args['help']['tabs'] ) )
    {
      $this->args['help']['tabs'] = array();
    }
  }

  protected function get_defaults()
  {
    $defaults = array(
      'title'           => __( 'Set title', 'wpod' ),
      'label'           => __( 'Set label', 'wpod' ),
      'description'     => '',
      'capability'      => 'manage_options',
      'help'            => array(
        'tabs'            => array(),
        'sidebar'         => '',
      ),
    );
    return apply_filters( 'wpod_set_defaults', $defaults );
  }
}
