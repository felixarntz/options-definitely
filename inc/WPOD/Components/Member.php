<?php
/**
 * @package WPOD
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPOD\Components;

class Member extends ComponentBase
{
  public function register()
  {
    $sections = \WPOD\Framework::instance()->query( array(
      'type'          => 'section',
      'parent_slug'   => $this->slug,
      'parent_type'   => 'member',
    ) );
    if( count( $sections ) > 0 )
    {
      register_setting( $this->slug, $this->slug, array( $this, 'validate_options' ) );
    }
  }

  public function render()
  {
    if( !empty( $this->args['description'] ) )
    {
      echo '<p class="description">' . $this->args['description'] . '</p>';
    }
    $sections = \WPOD\Framework::instance()->query( array(
      'type'          => 'section',
      'parent_slug'   => $this->slug,
      'parent_type'   => 'member',
    ) );
    if( count( $sections ) > 0 )
    {
      $form_atts = array(
        'id'          => $this->slug,
        'action'      => admin_url( 'options.php' ),
        'method'      => 'post',
        'novalidate'  => true,
      );
      $form_atts = apply_filters( 'wpod_form_atts', $form_atts, $this );
      echo '<form' . wpod_make_html_attributes( $form_atts, false, false ) . '>';
      if( $this->args['mode'] == 'draggable' )
      {
        wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
        wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
        
        echo '<div class="metabox-holder">';
        echo '<div class="postbox-container">';
        do_meta_boxes( $this->slug, 'normal', null );
        echo '</div>';
        echo '</div>';
      }
      else
      {
        do_settings_sections( $this->slug );
      }
      settings_fields( $this->slug );
      submit_button();
      echo '</form>';
    }
    elseif( $this->args['callback'] && is_callable( $this->args['callback'] ) )
    {
      call_user_func( $this->args['callback'] );
    }
    else
    {
      wpod_doing_it_wrong( __METHOD__, sprintf( __( 'There are no sections to display for member %s. Either add some or provide a valid callback function instead.', 'wpod' ), $this->slug ), '1.0.0' );
    }

    if( $this->args['mode'] == 'draggable' )
    {
      ?>
      <script type="text/javascript">
        //<![CDATA[
        jQuery(document).ready( function ($) {
          // close postboxes that should be closed
          $('.if-js-closed').removeClass('if-js-closed').addClass('closed');
          // postboxes setup
          postboxes.add_postbox_toggles('<?php echo $this->slug; ?>');
        });
        //]]>
      </script>
      <?php
    }
  }

  public function validate_options( $options )
  {
    //TODO: validation
  }

  public function update_option_defaults()
  {
    //TODO: update_defaults
  }

  protected function get_defaults()
  {
    $defaults = array(
      'title'           => __( 'Member title', 'wpod' ),
      'description'     => '',
      'capability'      => 'manage_options',
      'mode'            => 'default',
      'callback'        => false, //only used if no sections are attached to this member
    );
    return apply_filters( 'wpod_member_defaults', $defaults );
  }
}
