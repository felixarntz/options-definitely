<?php
/**
 * @package WPOD
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPOD\Components;

class Section extends ComponentBase
{
  public function register( $parent_member )
  {
    global $wp_settings_sections;

    add_settings_section( $this->slug, $this->args['title'], array( $this, 'render' ), $parent_member->slug );
    $wp_settings_sections[ $parent_member->slug ][ $this->slug ]['description'] = $this->args['description'];
    if( $parent_member->mode == 'draggable' )
    {
      add_meta_box( $this->slug, $this->args['title'], array( $this, 'render' ), $parent_member->slug, 'normal' );
    }
  }

  public function render()
  {
    if( !empty( $this->args['description'] ) )
    {
      echo '<p class="description">' . $this->args['description'] . '</p>';
    }
    $fields = \WPOD\Framework::instance()->query( array(
      'type'          => 'field',
      'parent_slug'   => $this->slug,
      'parent_type'   => 'section',
    ) );
    if( count( $fields ) > 0 )
    {
      $table_atts = array(
        'class'       => 'form-table',
      );
      $table_atts = apply_filters( 'wpod_table_atts', $table_atts, $this );
      echo '<table' . wpod_make_html_attributes( $table_atts, false, false ) . '>';
      do_settings_fields( $this->parent, $this->slug );
      echo '</table>';
    }
    elseif( $this->args['callback'] && is_callable( $this->args['callback'] ) )
    {
      global $wp_settings_sections;
      call_user_func( $this->args['callback'] );
    }
    else
    {
      wpod_doing_it_wrong( __METHOD__, sprintf( __( 'There are no fields to display for section %s. Either add some or provide a valid callback function instead.', 'wpod' ), $this->slug ), '1.0.0' );
    }
  }

  protected function get_defaults()
  {
    $defaults = array(
      'title'           => __( 'Section title', 'wpod' ),
      'description'     => '',
      'callback'        => false,
      'callback_args'   => array(), //TODO: pass automatic args and disable this parameter
    );
    return apply_filters( 'wpod_section_defaults', $defaults );
  }
}
