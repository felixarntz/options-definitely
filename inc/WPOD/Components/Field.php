<?php
/**
 * @package WPOD
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPOD\Components;

class Field extends ComponentBase
{
  public function register( $parent_member, $parent_section )
  {
    add_settings_field( $this->slug, $this->args['title'], array( $this, 'render' ), $parent_member->slug, $parent_section->slug, array(
      'label_for'     => $parent_member->slug . '-' . $this->slug,
      'member_slug'   => $parent_member->slug,
      'section_slug'  => $parent_section->slug,
    ) );
  }

  public function render( $args = array() )
  {
    extract( $args );

    if( in_array( $this->args['type'], $this->get_supported_types() ) )
    {
      if( $this->args['type'] == 'repeatable' )
      {
        if( is_array( $this->args['repeatable'] ) && count( $this->args['repeatable'] ) > 0 )
        {
          $this->render_repeatable( $args );
        }
        else
        {
          wpod_doing_it_wrong( __METHOD__, sprintf( __( 'The field %s has been declared as a repeatable, but it does not contain any fields.', 'wpod' ), $slug ), '1.0.0' );
        }
      }
      else
      {
        $atts = array();
        $atts['id'] = $label_for;
        $atts['name'] = $member_slug . '[' . $this->slug . ']';
        if( in_array( $this->args['type'], array( 'multiselect', 'multibox' ) ) )
        {
          $atts['name'] .= '[]';
        }
        if( !empty( $this->args['class'] ) )
        {
          $atts['class'] = $this->args['class'];
        }
        if( $this->args['type'] == 'multiselect' )
        {
          $atts['multiple'] = true;
        }
        else
        {
          $atts['multiple'] = false;
        }
        $atts = array_merge( $atts, $this->args['more_attributes'] );

        $option = wpod_get_option( $member_slug, $this->slug );
        switch( $this->args['type'] )
        {
          case 'checkbox':
            $atts = array_merge( $atts, array(
              'value'       => 1,
              'checked'     => $this->is_value_checked_or_selected( $option, true ),
            ) );
            echo '<input type="checkbox"' . wpod_make_html_attributes( $atts, false, false ) . ' />';
            break;
          case 'select':
          case 'multiselect':
            echo '<select' . wpod_make_html_attributes( $atts, false, false ) . '>';
            foreach( $this->args['options'] as $value => $data )
            {
              $option_atts = array(
                'value'       => $value,
                'selected'    => $this->is_value_checked_or_selected( $option, $value, $atts['multiple'] ),
              );
              if( !empty( $data['image'] ) )
              {
                $options_atts['data-image'] = esc_url( $data['image'] );
              }
              elseif( !empty( $data['color'] ) )
              {
                $option_atts['data-color'] = ltrim( $data['color'], '#' );
              }
              echo '<option' . wpod_make_html_attributes( $option_atts, false, false ) . '>' . $data['label'] . '</option>';
            }
            echo '</select>';
            break;
          case 'radio':
          case 'multibox':
            $single_class = 'radio';
            $multiple = false;
            if( $this->args['type'] == 'multibox' )
            {
              $single_class = 'checkbox';
              $multiple = true;
            }
            echo '<div class="' . $single_class . '-group group">';
            foreach( $this->args['options'] as $value => $data )
            {
              $atts['id'] = $label_for . '-' . $value;
              $atts['value'] = $value;
              $atts['checked'] = $this->is_value_checked_or_selected( $option, $value, $multiple );
              $additional_output = $additional_class = '';
              if( !empty( $data['image'] ) || !empty( $data['color'] ) )
              {
                $additional_output = '<div id="' . $atts['id'] . '-asset"';
                if( $atts['checked'] )
                {
                  $additional_output .= ' class="checked"';
                }
                if( !empty( $data['image'] ) )
                {
                  $additional_output .= ' style="background-image:url(\'' . esc_url( $data['image'] ) . '\');"';
                }
                else
                {
                  $additional_output .= ' style="background-color:#' . ltrim( $data['color'], '#' ) . ';"';
                }
                $additional_output .= '></div>';
                $additional_class .= ' box';
              }
              echo '<div class="' . $single_class . $additional_class . '">';
              echo '<input type="' . $single_class . '"' . wpod_make_html_attributes( $atts, false, false ) . ' />';
              echo $additional_output;
              if( !empty( $data['label'] ) )
              {
                echo ' <label for="' . $atts['id'] . '">' . $data['label'] . '</label>';
              }
              echo '</div>';
            }
            echo '</div>';
            break;
          case 'media':
            $atts = array_merge( $atts, array( 'value' => $option ) );
            echo '<input type="text"' . wpod_make_html_attributes( $atts, false, false ) . ' />';
            echo '<a href="#" id="' . $atts['id'] . '-media-button" class="button media-button">' . __( 'Choose / Upload a file', 'wpod' ) . '</a>';
            if( !empty( $option ) )
            {
              echo '<br/>';
              if( wpod_is_image( $option ) )
              {
                echo '<img id="' . $atts['id'] . '-media-image" class="media-image" src="' . $option . '" />';
              }
              else
              {
                echo '<a id="' . $atts['id'] . '-media-link" class="media-link" href="' . $option . '" target="_blank">' . __( 'Open file', 'wpod' ) . '</a>';
              }
            }
            break;
          case 'textarea':
            echo '<textarea' . wpod_make_html_attributes( $atts, false, false ) . '>' . esc_textarea( $option ) . '</textarea>';
            break;
          case 'wysiwyg':
            $wp_editor_args = array(
              'wpautop'       => true,
              'media_buttons' => false,
              'textarea_name' => $atts['name'],
              'textarea_rows' => ( isset( $atts['rows'] ) ? $atts['rows'] : 5 ),
              'tinymce'       => array( 'plugins' => 'wordpress' ),
            );
            $id = $atts['id'];
            wp_editor( $option, $id, $wp_editor_args );
            break;
          default:
            $atts = array_merge( $atts, array( 'value' => $option ) );
            $additional_output = '';
            if( in_array( $this->args['type'], array( 'range', 'color' ) ) )
            {
              $additional_output = '<div id="' . $atts['id'] . '-' . $this->args['type'] . '-viewer" class="' . $this->args['type'] . '-viewer">' . $option . '</div>';
            }
            echo '<input type="' . $this->args['type'] . '"' . wpod_make_html_attributes( $atts, false, false ) . ' />' . $additional_output;
        }
        if( !empty( $this->args['description'] ) )
        {
          if( $this->args['type'] != 'checkbox' )
          {
            echo '<br/>';
          }
          echo '<span class="description">' . $this->args['description'] . '</span>';
        }
      }
    }
    elseif( is_callable( $this->args['type'] ) )
    {
      call_user_func( $this->args['type'], $this );
    }
    else
    {
      wpod_doing_it_wrong( __METHOD__, sprintf( __( 'The type for field %s is not supported. Either specify a supported type or provide a valid callback function instead.', 'wpod' ), $this->slug ), '1.0.0' );
    }
  }

  public function render_repeatable( $args = array() )
  {
    //TODO: render repeatable field
  }

  public function validate_option( $option = null, $option_old = null )
  {
    if( $option == null )
    {
      switch( $this->args['type'] )
      {
        case 'checkbox':
          $option = false;
          break;
        case 'multiselect':
        case 'multibox':
          $option = array();
          break;
        default:
          $option = '';
      }
    }
    $option = \WPOD\Validator::is_valid_empty( $option, $this );
    if( !$this->is_validation_error( $option ) && $option != '' )
    {
      if( is_callable( $this->args['validate'] ) )
      {
        $option = call_user_func( $this->args['validate'], $option, $this );
      }
      else
      {
        $option = \WPOD\Validator::invalid_validation_function();
      }
    }
    $error = $this->get_validation_error( $option );
    if( $this->is_validation_error( $option ) )
    {
      if( $option_old == null )
      {
        $option_old = $this->args['default'];
      }
      $option = $option_old;
    }
    return array( $option, $error );
  }

  private function is_validation_error( $option )
  {
    return is_array( $option ) && isset( $option['errmsg'] );
  }

  private function get_validation_error( $option )
  {
    if( $this->is_validation_error( $option ) )
    {
      return '<em>' . $this->args['title'] . ':</em> ' . $option['errmsg'];
    }
    return '';
  }

  private function get_supported_types( $repeatable = false )
  {
    $types = array(
      'checkbox',
      'select',
      'multiselect',
      'media',
      'date',
      'color',
      'range',
      'number',
      'tel',
      'email',
      'url',
      'text',
    );
    if( !$repeatable )
    {
      $non_repeatable_types = array(
        'radio',
        'multibox',
        'textarea',
        'wysiwyg',
        'repeatable',
      );
      $types = array_merge( $types, $non_repeatable_types );
    }
    return $types;
  }

  private function is_value_checked_or_selected( $option, $value, $multiple = false )
  {
    if( $multiple )
    {
      if( !is_array( $option ) )
      {
        $option = array( $option );
      }
      return in_array( $value, $option );
    }
    else
    {
      return $option == $value;
    }
  }

  public function validate()
  {
    if( isset( $this->args['type'] ) )
    {
      if( !isset( $this->args['validate'] ) )
      {
        if( is_string( $this->args['type'] ) && method_exists( '\\WPOD\\Validator', $this->args['type'] ) )
        {
          $this->args['validate'] = array( '\\WPOD\\Validator', $this->args['type'] );
        }
      }
    }
    parent::validate();
    if( is_array( $this->args['class'] ) )
    {
      $this->args['class'] = implode( ' ', $this->args['class'] );
    }
    if( !is_array( $this->args['options'] ) )
    {
      $this->args['options'] = array();
    }
    foreach( $this->args['options'] as $value => &$data )
    {
      if( !is_array( $data ) )
      {
        $data = array( 'label' => (string) $data );
      }
      $data = wp_parse_args( $data, array(
        'label'       => '',
        'image'       => '',
        'color'       => '',
      ) );
    }
    if( !is_array( $this->args['more_attributes'] ) )
    {
      $this->args['more_attributes'] = array();
    }
    foreach( $this->args['more_attributes'] as $attr => &$value )
    {
      if( $attr == $value )
      {
        $value = true;
      }
    }
    if( $this->args['type'] == 'repeatable' && is_array( $repeatable ) && count( $repeatable ) > 0 )
    {
      $this->validate_repeatable();
    }
  }

  protected function validate_repeatable()
  {
    $this->args['repeatable'] = wp_parse_args( $this->args['repeatable'], array(
      'limit'           => 0,
      'fields'          => array(),
    ) );
    //TODO: validate repeatable fields array
  }

  protected function get_defaults()
  {
    $defaults = array(
      'title'           => __( 'Field title', 'wpod' ),
      'description'     => '',
      'type'            => 'text',
      'default'         => '',
      'options'         => array(),
      'validate'        => 'esc_html',
      'class'           => '',
      'more_attributes' => array(),
      'repeatable'      => array(),
    );
    return apply_filters( 'wpod_field_defaults', $defaults );
  }
}
