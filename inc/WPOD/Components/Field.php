<?php
/**
 * @package WPOD
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPOD\Components;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class for a field component.
 *
 * A field denotes a settings field, i.e. both the field option and the visual input in the WordPress admin.
 * Since WPOD stores all options inside an array (where the option name is the tab slug), the field slugs are used as array keys in that options array.
 *
 * @internal
 * @since 0.5.0
 */
class Field extends ComponentBase {

	/**
	 * Registers the settings fields.
	 *
	 * @since 0.5.0
	 * @param WPOD\Components\Tab $parent_tab the parent tab component of this field
	 * @param WPOD\Components\Section $parent_section the parent section component of this field
	 */
	public function register( $parent_tab, $parent_section ) {
		add_settings_field( $this->slug, $this->args['title'], array( $this, 'render' ), $parent_tab->slug, $parent_section->slug, array(
			'label_for'		=> $parent_tab->slug . '-' . $this->slug,
			'tab_slug'	=> $parent_tab->slug,
			'section_slug'	=> $parent_section->slug,
		) );
	}

	/**
	 * Renders the field.
	 *
	 * This function will show the input field(s) in the WordPress admin.
	 * If the field is a repeatable field, the more specific function to render this type of field will be called.
	 * Otherwise the field is rendered according to the type of the field.
	 * The type can also be specified as a callback in which case this callback function will be used to render the input.
	 *
	 * @since 0.5.0
	 * @param array $args additional field arguments passed by the add_settings_field function (array has the keys 'label_for', 'tab_slug' and 'section_slug')
	 */
	public function render( $args = array() ) {
		/**
		 * This action can be used to display additional content on top of this field.
		 *
		 * @since 0.5.0
		 * @param string the slug of the current field
		 * @param array the arguments array for the current field
		 * @param string the slug of the current section
		 * @param string the slug of the current tab
		 */
		do_action( 'wpod_field_before', $this->slug, $this->args, $this->parent, $args['tab_slug'] );

		if ( in_array( $this->args['type'], $this->get_supported_types() ) ) {
			if ( 'repeatable' == $this->args['type'] ) {
				if ( isset( $this->args['repeatable'] ) ) {
					$this->render_repeatable( $args );
				} else {
					\LaL_WP_Plugin_Util::get( 'options-definitely' )->doing_it_wrong( __METHOD__, sprintf( __( 'The field %s has been declared as a repeatable, but it does not contain any fields.', 'wpod' ), $slug ), '0.5.0' );
				}
			} else {
				extract( $args );

				$atts = array();

				$atts['id'] = $label_for;
				$atts['name'] = $tab_slug . '[' . $this->slug . ']';

				if ( in_array( $this->args['type'], array( 'multiselect', 'multibox' ) ) ) {
					$atts['name'] .= '[]';
				}

				if ( ! empty( $this->args['class'] ) ) {
					$atts['class'] = $this->args['class'];
				}

				if ( 'multiselect' == $this->args['type'] ) {
					$atts['multiple'] = true;
				} else {
					$atts['multiple'] = false;
				}

				$atts = array_merge( $atts, $this->args['more_attributes'] );

				$option = wpod_get_option( $tab_slug, $this->slug );

				switch ( $this->args['type'] ) {
					case 'checkbox':
						$atts = array_merge( $atts, array(
							'value'		=> 1,
							'checked'	=> $this->is_value_checked_or_selected( $option, true ),
						) );

						echo '<input type="checkbox"' . \LaL_WP_Plugin_Util::make_html_attributes( $atts, false, false ) . ' />';

						break;
					case 'select':
					case 'multiselect':
						echo '<select' . \LaL_WP_Plugin_Util::make_html_attributes( $atts, false, false ) . '>';

						foreach ( $this->args['options'] as $value => $data ) {
							$option_atts = array(
								'value'		=> $value,
								'selected'	=> $this->is_value_checked_or_selected( $option, $value, $atts['multiple'] ),
							);

							if ( ! empty( $data['image'] ) )
							{
								$option_atts['data-image'] = esc_url( $data['image'] );
							} elseif ( ! empty( $data['color'] ) ) {
								$option_atts['data-color'] = ltrim( $data['color'], '#' );
							}

							echo '<option' . \LaL_WP_Plugin_Util::make_html_attributes( $option_atts, false, false ) . '>' . $data['label'] . '</option>';
						}

						echo '</select>';

						break;
					case 'radio':
					case 'multibox':
						$single_class = 'radio';

						$multiple = false;

						if ( 'multibox' == $this->args['type'] ) {
							$single_class = 'checkbox';
							$multiple = true;
						}

						echo '<div class="' . $single_class . '-group group">';

						foreach ( $this->args['options'] as $value => $data ) {
							$atts['id'] = $label_for . '-' . $value;
							$atts['value'] = $value;
							$atts['checked'] = $this->is_value_checked_or_selected( $option, $value, $multiple );

							$additional_output = $additional_class = '';

							if ( ! empty( $data['image'] ) || ! empty( $data['color'] ) ) {
								$additional_output = '<div id="' . $atts['id'] . '-asset"';

								if ( $atts['checked'] ) {
									$additional_output .= ' class="checked"';
								}

								if ( ! empty( $data['image'] ) ) {
									$additional_output .= ' style="background-image:url(\'' . esc_url( $data['image'] ) . '\');"';
								} else {
									$additional_output .= ' style="background-color:#' . ltrim( $data['color'], '#' ) . ';"';
								}

								$additional_output .= '></div>';

								$additional_class .= ' box';
							}

							echo '<div class="' . $single_class . $additional_class . '">';

							echo '<input type="' . $single_class . '"' . \LaL_WP_Plugin_Util::make_html_attributes( $atts, false, false ) . ' />';

							echo $additional_output;

							if ( ! empty( $data['label'] ) ) {
								echo ' <label for="' . $atts['id'] . '">' . $data['label'] . '</label>';
							}

							echo '</div>';
						}

						echo '</div>';

						break;
					case 'media':
						$atts = array_merge( $atts, array(
							'value'		=> $option,
						) );

						echo '<input type="hidden"' . \LaL_WP_Plugin_Util::make_html_attributes( $atts, false, false ) . ' />';

						echo '<input type="text" id="' . $atts['id'] . '-media-title" value="' . ( $option ? get_the_title( $option ) : '' ) . '" />';

						echo '<a href="#" id="' . $atts['id'] . '-media-button" class="button media-button">' . __( 'Choose / Upload a file', 'wpod' ) . '</a>';

						if ( $option ) {
							if ( wpod_is_image( $option ) ) {
								echo '<img id="' . $atts['id'] . '-media-image" class="media-image" src="' . wp_get_attachment_url( $option ) . '" />';
							} else {
								echo '<a id="' . $atts['id'] . '-media-link" class="media-link" href="' . wp_get_attachment_url( $option ) . '" target="_blank">' . __( 'Open file', 'wpod' ) . '</a>';
							}
						}

						break;
					case 'textarea':
						echo '<textarea' . \LaL_WP_Plugin_Util::make_html_attributes( $atts, false, false ) . '>' . esc_textarea( $option ) . '</textarea>';

						break;
					case 'wysiwyg':
						$wp_editor_args = array(
							'wpautop'		=> true,
							'media_buttons'	=> false,
							'textarea_name'	=> $atts['name'],
							'textarea_rows'	=> ( isset( $atts['rows'] ) ? $atts['rows'] : 5 ),
							'tinymce'		=> array( 'plugins' => 'wordpress' ),
						);

						$wp_editor_args = apply_filters( 'wpod_wp_editor_args', $wp_editor_args, $this->slug, $this->args, $this->parent, $args['parent_tab'] );

						$id = $atts['id'];

						wp_editor( $option, $id, $wp_editor_args );

						break;
					default:
						$atts = array_merge( $atts, array( 'value' => $option ) );

						$type = $this->args['type'];
						if ( in_array( $type, array( 'datetime', 'date', 'time' ) ) ) {
							if ( ! isset( $atts['class'] ) ) {
								$atts['class'] = '';
							} else {
								$atts['class'] .= ' ';
							}
							$atts['class'] .= 'dtp-' . $type;

							$type = 'text';
						}

						$additional_output = '';

						if ( in_array( $this->args['type'], array( 'range', 'color' ) ) ) {
							$additional_output = '<input type="text" id="' . $atts['id'] . '-' . $this->args['type'] . '-viewer" class="' . $this->args['type'] . '-viewer" value="' . $option . '" />';
						}

						echo $additional_output . '<input type="' . $type . '"' . \LaL_WP_Plugin_Util::make_html_attributes( $atts, false, false ) . ' />';
				}

				if ( ! empty( $this->args['description'] ) ) {
					if ( 'checkbox' != $this->args['type'] ) {
						echo '<br/>';
					}

					echo '<span class="description">' . $this->args['description'] . '</span>';
				}
			}
		} elseif ( is_callable( $this->args['type'] ) ) {
			call_user_func( $this->args['type'], $this, $args );
		} else {
			\LaL_WP_Plugin_Util::get( 'options-definitely' )->doing_it_wrong( __METHOD__, sprintf( __( 'The type for field %s is not supported. Either specify a supported type or provide a valid callback function instead.', 'wpod' ), $this->slug ), '0.5.0' );
		}

		/**
		 * This action can be used to display additional content at the bottom of this field.
		 *
		 * @since 0.5.0
		 * @param string the slug of the current field
		 * @param array the arguments array for the current field
		 * @param string the slug of the current section
		 * @param string the slug of the current tab
		 */
		do_action( 'wpod_field_after', $this->slug, $this->args, $this->parent, $args['tab_slug'] );
	}

	/**
	 * Renders a repeatable field.
	 *
	 * A repeatable field is a special field type that actually bundles multiple other input fields into one.
	 * This group of fields can the be repeated numerous times.
	 * However, the values are all stored in the one main field option as an array.
	 * The actual contents of a repeatable field are specified in the 'repeatable' key of the arguments array.
	 *
	 * @since 0.5.0
	 * @param array $args additional field arguments passed by the add_settings_field function (array has the keys 'label_for', 'tab_slug' and 'section_slug')
	 */
	public function render_repeatable( $args = array() ) {
		extract( $args );

		$atts = array();

		$atts['id'] = $label_for;
		$atts['class'] = 'repeatable';

		if ( ! empty( $this->args['class'] ) ) {
			$atts['class'] .= ' ' . $this->args['class'];
		}

		$atts = array_merge( $atts, $this->args['more_attributes'] );

		$name_prefix = $tab_slug . '[' . $this->slug . ']';

		$atts['data-slug'] = $this->slug;
		$atts['data-parent-slug'] = $tab_slug;
		$atts['data-limit'] = $this->args['repeatable']['limit'];

		$option = wpod_get_option( $tab_slug, $this->slug );

		echo '<div' . \LaL_WP_Plugin_Util::make_html_attributes( $atts, false, false ) . '>';

		echo '<p><a class="new-repeatable-button button" href="#"' . ( $this->args['repeatable']['limit'] > 0 && count( $option ) == $this->args['repeatable']['limit'] ? ' style="display:none;"' : '' ) . '>' . __( 'Add new', 'wpod' ) . '</a></p>';

		if ( is_array( $option ) ) {
			foreach ( $option as $key => $options ) {
				$this->render_repeatable_row( $key, $label_for, $name_prefix, $options );
			}
		}

		echo '</div>';

		if ( ! empty( $this->args['description'] ) ) {
			echo '<p class="description">' . $this->args['description'] . '</p>';
		}
	}

	/**
	 * Renders a repeatable row.
	 *
	 * A repeatable row is one row of a repeatable field. It consists of the bundled fields.
	 * However, since they all belong together, they are displayed in one row, not in a list like normal fields.
	 * These rows are repeatable, so this function is executed for every item in the repeatable field.
	 *
	 * The basic output of this function is also used as a template in JavaScript so that additional rows can be added without making an AJAX call.
	 *
	 * @see WPOD\Components\Field::render_repeatable()
	 * @since 0.5.0
	 * @param integer $key numeric index of this row (starts with 0)
	 * @param string $id_prefix the selector ID prefix for this row (same like the 'label_for' value)
	 * @param string $name_prefix the name prefix for this row (it has the format `TABSLUG[FIELDSLUG]`)
	 * @param array $options the stored option for this row
	 */
	public function render_repeatable_row( $key, $id_prefix, $name_prefix, $options = array() ) {
		echo '<p class="repeatable-row">';

		if ( '{{' . 'KEY' . '}}' === $key ) {
			echo '<span>' . sprintf( __( '%s.', 'wpod' ), '{{' . 'KEY_PLUSONE' . '}}' ) . '</span>';
		} else {
			$key = absint( $key );
			echo '<span>' . sprintf( __( '%s.', 'wpod' ), $key + 1 ) . '</span>';
		}

		foreach ( $this->args['repeatable']['fields'] as $slug => $field ) {
			if ( in_array( $field['type'], $this->get_supported_types( true ) ) ) {
				echo '<span class="repeatable-col">';

				$atts = array();

				$atts['id'] = $id_prefix . '-' . $key . '-' . $slug;
				$atts['name'] = $name_prefix . '[' . $key . '][' . $slug . ']';

				if ( ! empty( $field['class'] ) ) {
					$atts['class'] = $field['class'];
				}

				if ( 'multiselect' == $field['type'] ) {
					$atts['multiple'] = true;
				} else {
					$atts['multiple'] = false;
				}

				$atts = array_merge( $atts, $field['more_attributes'] );

				if ( ! isset( $options[ $slug ] ) ) {
					$options[ $slug ] = $field['default'];
				}

				switch ( $field['type'] ) {
					case 'checkbox':
						$atts = array_merge( $atts, array(
							'value'		=> 1,
							'checked'	=> $this->is_value_checked_or_selected( $options[ $slug ], true ),
						) );

						echo '<input type="checkbox"' . \LaL_WP_Plugin_Util::make_html_attributes( $atts, false, false ) . ' />';

						echo '<span class="description">' . $field['title'] . '</span>';

						break;
					case 'select':
					case 'multiselect':
						echo '<select' . \LaL_WP_Plugin_Util::make_html_attributes( $atts, false, false ) . '>';

						echo '<option value="">-- ' . $field['title'] . '</option>';

						foreach ( $field['options'] as $value => $data ) {
							$option_atts = array(
								'value'		=> $value,
								'selected'	=> $this->is_value_checked_or_selected( $options[ $slug ], $value, $atts['multiple'] ),
							);

							if ( ! empty( $data['image'] ) ) {
								$option_atts['data-image'] = esc_url( $data['image'] );
							} elseif ( ! empty( $data['color'] ) ) {
								$option_atts['data-color'] = ltrim( $data['color'], '#' );
							}

							echo '<option' . \LaL_WP_Plugin_Util::make_html_attributes( $option_atts, false, false ) . '>' . $data['label'] . '</option>';
						}

						echo '</select>';

						break;
					case 'media':
						$atts = array_merge( $atts, array(
							'value'		=> $options[ $slug ],
						) );

						echo '<input type="hidden"' . \LaL_WP_Plugin_Util::make_html_attributes( $atts, false, false ) . ' />';

						echo '<input type="text" id="' . $atts['id'] . '-media-title" value="' . ( $options[ $slug ] ? get_the_title( $options[ $slug ] ) : '' ) . '" placeholder="' . $field['title'] . '" />';

						echo '<a href="#" id="' . $atts['id'] . '-media-button" class="button media-button">' . __( 'Choose / Upload a file', 'wpod' ) . '</a>';

						if ( $options[ $slug ] ) {
							if ( wpod_is_image( $options[ $slug ] ) ) {
								echo '<img id="' . $atts['id'] . '-media-image" class="media-image" src="' . wp_get_attachment_url( $options[ $slug ] ) . '" />';
							} else {
								echo '<a id="' . $atts['id'] . '-media-link" class="media-link" href="' . wp_get_attachment_url( $options[ $slug ] ) . '" target="_blank">' . __( 'Open file', 'wpod' ) . '</a>';
							}
						}

						break;
					default:
						$atts = array_merge( $atts, array(
							'value'			=> $options[ $slug ],
							'placeholder'	=> $field['title'],
						) );

						$type = $field['type'];
						if ( in_array( $type, array( 'datetime', 'date', 'time' ) ) ) {
							if ( ! isset( $atts['class'] ) ) {
								$atts['class'] = '';
							} else {
								$atts['class'] .= ' ';
							}
							$atts['class'] .= 'dtp-' . $type;

							$type = 'text';
						}

						$additional_output = '';

						if ( in_array( $field['type'], array( 'range', 'color' ) ) ) {
							$additional_output = '<input type="text" id="' . $atts['id'] . '-' . $field['type'] . '-viewer" class="' . $field['type'] . '-viewer" value="' . $options[ $slug ] . '" placeholder="' . $field['title'] . '" />';
						}

						echo $additional_output . '<input type="' . $type . '"' . \LaL_WP_Plugin_Util::make_html_attributes( $atts, false, false ) . ' />';
				}
				echo '</span>';
			} elseif ( is_callable( $field['type'] ) ) {
				call_user_func( $field['type'], $slug, $field, $key, $id_prefix, $name_prefix, $options );
			} else {
				\LaL_WP_Plugin_Util::get( 'options-definitely' )->doing_it_wrong( __METHOD__, sprintf( __( 'The type for field %1$s (part of repeatable %2$s) is not supported. Either specify a supported type or provide a valid callback function instead.', 'wpod' ), $slug, $this->slug ), '0.5.0' );
			}
		}

		echo '<a data-number="' . $key . '" class="remove-repeatable-button button" href="#">' . __( 'Remove', 'wpod' ) . '</a>';

		echo '</p>';
	}

	/**
	 * Validates the option for this field.
	 *
	 * @see WPOD\Components\Tab::validate_options()
	 * @since 0.5.0
	 * @param mixed $option the new option value to validate
	 * @param mixed $option_old the previous option value
	 * @return array numeric array where the first item is the validated option (or the old option if an error occurred) and the second item is an error message of an empty string if everything is fine
	 */
	public function validate_option( $option = null, $option_old = null ) {
		if ( $option == null ) {
			switch ( $this->args['type'] ) {
				case 'checkbox':
					$option = false;
					break;
				case 'multiselect':
				case 'multibox':
				case 'repeatable':
					$option = array();
					break;
				case 'number':
				case 'range':
					$option = isset( $this->args['more_attributes']['min'] ) ? $this->args['more_attributes']['min'] : 0;
					break;
				default:
					$option = '';
			}
		}

		$option = \WPOD\Validator::is_valid_empty( $option, $this );

		if ( ! $this->is_validation_error( $option ) && '' != $option ) {
			if ( is_callable( $this->args['validate'] ) ) {
				$option = call_user_func( $this->args['validate'], $option, $this );
			} else {
				$option = \WPOD\Validator::invalid_validation_function();
			}
		}

		$error = $this->get_validation_error( $option );

		if ( $this->is_validation_error( $option ) ) {
			if ( isset( $option['value'] ) ) {
				$option = $option['value'];
			} else {
				if ( $option_old == null ) {
					$option_old = $this->args['default'];
				}

				$option = $option_old;
			}
		}

		return array( $option, $error );
	}

	/**
	 * Checks if a variable is a validation error.
	 *
	 * This function is used to check return values of validation functions.
	 *
	 * @since 0.5.0
	 * @param mixed $option the option value to check
	 * @return boolean true if the value is a validation error, otherwise false
	 */
	private function is_validation_error( $option ) {
		return is_array( $option ) && isset( $option['errmsg'] ) && is_string( $option['errmsg'] );
	}

	/**
	 * Returns the validation error message for an option, prefixed with the title of the field.
	 *
	 * @since 0.5.0
	 * @param mixed $option the option value to get the validation error for
	 * @return string the validation error message or an empty string if the option is not a validation error
	 */
	private function get_validation_error( $option ) {
		if ( $this->is_validation_error( $option ) ) {
			return '<em>' . $this->args['title'] . ':</em> ' . $option['errmsg'];
		}

		return '';
	}

	/**
	 * Returns an array of available types.
	 *
	 * Note that the supported types for a repeatable field are different from those of a normal field.
	 *
	 * @since 0.5.0
	 * @param boolean $repeatable whether to get the supported types for a repeatable field or not
	 * @return array numeric array of supported types
	 */
	private function get_supported_types( $repeatable = false ) {
		$types = array(
			'checkbox',
			'select',
			'multiselect',
			'media',
			'datetime',
			'date',
			'time',
			'color',
			'range',
			'number',
			'tel',
			'email',
			'url',
			'text',
		);

		if ( ! $repeatable ) {
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

	/**
	 * Helper function to check if a value is the checked or selected value in a dropdown/radio group.
	 *
	 * @since 0.5.0
	 * @param string|integer|array $option the option value
	 * @param string|integer $value the current value to compare
	 * @param boolean $multiple whether to perform the check for an input where multiple values can be selected
	 * @return boolean true if the value is checked/selected, otherwise false
	 */
	private function is_value_checked_or_selected( $option, $value, $multiple = false ) {
		if ( $multiple ) {
			if ( ! is_array( $option ) ) {
				$option = array( $option );
			}

			return in_array( $value, $option );
		}

		return $option == $value;
	}

	/**
	 * Validates the arguments array.
	 *
	 * @since 0.5.0
	 */
	public function validate() {
		if ( isset( $this->args['type'] ) ) {
			if ( ! isset( $this->args['default'] ) ) {
				switch( $this->args['type'] ) {
					case 'checkbox':
						$this->args['default'] = false;

						break;
					case 'multiselect':
					case 'multibox':
					case 'repeatable':
						$this->args['default'] = array();

						break;
					case 'number':
					case 'range':
						$this->args['default'] = isset( $this->args['more_attributes']['min'] ) ? $this->args['more_attributes']['min'] : 0;

						break;
					default:
						$this->args['default'] = '';
				}
			}

			if ( ! isset( $this->args['validate'] ) ) {
				if ( is_string( $this->args['type'] ) && method_exists( '\\WPOD\\Validator', $this->args['type'] ) ) {
					$this->args['validate'] = array( '\\WPOD\\Validator', $this->args['type'] );
				}
			}
		}

		parent::validate();

		if ( is_array( $this->args['class'] ) ) {
			$this->args['class'] = implode( ' ', $this->args['class'] );
		}

		if ( ! is_array( $this->args['options'] ) ) {
			$this->args['options'] = array();
		}

		foreach ( $this->args['options'] as $value => &$data ) {
			if ( ! is_array( $data ) ) {
				$data = array( 'label' => (string) $data );
			}

			$data = \LaL_WP_Plugin_Util::parse_args( $data, array(
				'label'		=> '',
				'image'		=> '',
				'color'		=> '',
			), true );
		}

		if ( ! is_array( $this->args['more_attributes'] ) ) {
			$this->args['more_attributes'] = array();
		}

		if( 'repeatable' == $this->args['type'] && is_array( $this->args['repeatable'] ) && isset( $this->args['repeatable']['fields'] ) && is_array( $this->args['repeatable']['fields'] ) && count( $this->args['repeatable']['fields'] ) > 0 ) {
			$this->validate_repeatable();
		} else {
			unset( $this->args['repeatable'] );
		}
	}

	/**
	 * Validates a 'repeatable' key of the arguments array.
	 *
	 * @since 0.5.0
	 */
	protected function validate_repeatable() {
		$this->args['repeatable'] = \LaL_WP_Plugin_Util::parse_args( $this->args['repeatable'], array(
			'limit'           => 0,
			'fields'          => array(),
		), true );

		foreach ( $this->args['repeatable']['fields'] as $slug => &$field ) {
			if ( isset( $field['type'] ) ) {
				if ( 'radio' == $field['type'] ) {
					$field['type'] = 'select';
				} elseif ( 'multibox' == $field['type'] ) {
					$field['type'] = 'multiselect';
				}

				if ( ! isset( $field['default'] ) ) {
					switch( $field['type'] ) {
						case 'checkbox':
							$field['default'] = false;

							break;
						case 'multiselect':
							$field['default'] = array();

							break;
						case 'number':
						case 'range':
							$field['default'] = isset( $field['more_attributes']['min'] ) ? $field['more_attributes']['min'] : 0;

							break;
						default:
							$field['default'] = '';
					}
				}

				if ( ! isset( $field['validate'] ) ) {
					if ( is_string( $field['type'] ) && method_exists( '\\WPOD\\Validator', $field['type'] ) ) {
						$field['validate'] = array( '\\WPOD\\Validator', $field['type'] );
					}
				}
			}

			$field = \LaL_WP_Plugin_Util::parse_args( $field, array(
				'title'				=> __( 'Field placeholder', 'wpod' ),
				'type'				=> 'text',
				'default'			=> '',
				'options'			=> array(),
				'validate'			=> 'esc_html',
				'class'				=> '',
				'more_attributes'	=> array(),
			), true );

			if ( is_array( $field['class'] ) ) {
				$field['class'] = implode( ' ', $field['class'] );
			}

			if ( ! is_array( $field['options'] ) ) {
				$field['options'] = array();
			}

			foreach ( $field['options'] as $value => &$data ) {
				if ( ! is_array( $data ) ) {
					$data = array( 'label' => (string) $data );
				}

				$data = \LaL_WP_Plugin_Util::parse_args( $data, array(
					'label'		=> '',
					'image'		=> '',
					'color'		=> '',
				), true );
			}

			if ( ! is_array( $field['more_attributes'] ) ) {
				$field['more_attributes'] = array();
			}
		}
	}

	/**
	 * Returns the keys of the arguments array and their default values.
	 *
	 * Read the plugin guide for more information about the field arguments.
	 *
	 * @since 0.5.0
	 * @return array
	 */
	protected function get_defaults() {
		$defaults = array(
			'title'				=> __( 'Field title', 'wpod' ),
			'description'		=> '',
			'type'				=> 'text',
			'default'			=> '',
			'options'			=> array(),
			'validate'			=> 'esc_html',
			'class'				=> '',
			'more_attributes'	=> array(),
			'repeatable'		=> array(),
		);

		/**
		 * This filter can be used by the developer to modify the default values for each field component.
		 *
		 * @since 0.5.0
		 * @param array the associative array of default values
		 */
		return apply_filters( 'wpod_field_defaults', $defaults );
	}
}
