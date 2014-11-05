<?php
/**
 * @package WPOD
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPOD;

class Admin
{
  private static $instance = null;

  public static function instance()
  {
    if( self::$instance == null )
    {
      self::$instance = new self;
    }
    return self::$instance;
  }

  private function __construct()
  {
    add_action( 'admin_menu', array( $this, 'create_admin_menu' ) );
  }

  public function create_admin_menu()
  {
    $sets = \WPOD\Framework::instance()->query( array(
      'type'          => 'set',
    ) );
    foreach( $sets as $set )
    {
      $page_hook = $set->add_to_menu();
      if( !empty( $page_hook ) )
      {
        add_action( 'load-' . $page_hook, array( $set, 'render_help' ) );
      }
    }
  }
}
