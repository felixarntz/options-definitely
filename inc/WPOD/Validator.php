<?php
/**
 * @package WPOD
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPOD;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * This class contains static functions that should be used for field validation.
 *
 * @since 1.0.0
 */
class Validator {

	/**
	 * Validates a checkbox value.
	 *
	 * The function uses the plugin utility class to format the value.
	 *
	 * @since 1.0.0
	 * @param mixed $value the field value to validate
	 * @param WPOD\Components\ComponentBase $field the field component `$value` belongs to
	 * @return bool either true or false
	 */
	public static function checkbox( $value, $field ) {
		return \LaL_WP_Plugin_Util::format( $value, 'bool', 'input' );
	}

	/**
	 * Validates a select value.
	 *
	 * @since 1.0.0
	 * @param string $value the field value to validate
	 * @param WPOD\Components\ComponentBase $field the field component `$value` belongs to
	 * @return string|array the validated value or an error array
	 */
	public static function select( $value, $field ) {
		if ( isset( $field->options[ $value ] ) ) {
			return $value;
		}

		return self::error_handler( sprintf( __( '%s is no valid choice for this field.', 'wpod' ), \LaL_WP_Plugin_Util::format( $value, 'string', 'output' ) ) );
	}

	/**
	 * Validates a radio value.
	 *
	 * Alias for `WPOD\Validator::select()`.
	 *
	 * @since 1.0.0
	 * @see WPOD\Validator::select()
	 * @param string $value the field value to validate
	 * @param WPOD\Components\ComponentBase $field the field component `$value` belongs to
	 * @return string|array the validated value or an error array
	 */
	public static function radio( $value, $field ) {
		return self::select( $value, $field );
	}

	/**
	 * Validates the values of a multiselect field.
	 *
	 * @since 1.0.0
	 * @param string|array $value the field value to validate
	 * @param WPOD\Components\ComponentBase $field the field component `$value` belongs to
	 * @return array the validated value or an error array
	 */
	public static function multiselect( $value, $field ) {
		$validated = array();
		$invalid = array();

		if ( ! is_array( $value ) ) {
			$value = array( $value );
		}

		foreach ( $value as $val ) {
			if ( isset( $field['options'][ $val ] ) ) {
				$validated[] = $val;
			} else {
				$invalid[] = \LaL_WP_Plugin_Util::format( $val, 'string', 'output' );
			}
		}

		if ( count( $invalid ) == 0 ) {
			return $validated;
		}

		return self::error_handler( sprintf( __( 'The values %s are not valid options for this field.', 'wpod' ), implode( ', ', $invalid ) ) );
	}

	/**
	 * Validates the values of a multibox field.
	 *
	 * Alias for `WPOD\Validator::multiselect()`.
	 *
	 * @since 1.0.0
	 * @see WPOD\Validator::multiselect()
	 * @param string|array $value the field value to validate
	 * @param WPOD\Components\ComponentBase $field the field component `$value` belongs to
	 * @return array the validated value or an error array
	 */
	public static function multibox( $value, $field ) {
		return self::multiselect( $value, $field );
	}

	/**
	 * Validates a number value.
	 *
	 * If min/max/step is/are provided in the field arguments, the function also checks if the value is within the allowed boundaries.
	 *
	 * @since 1.0.0
	 * @param string|int|float $value the field value to validate
	 * @param WPOD\Components\ComponentBase $field the field component `$value` belongs to
	 * @return int|float|array the validated value or an error array
	 */
	public static function number( $value, $field ) {
		$type = '';
		if ( isset( $field->more_attributes['step'] ) && is_int( $field->more_attributes['step'] ) ) {
			$type = 'int';
		} else {
			$type = 'float';
		}

		$value = \LaL_WP_Plugin_Util::format( $value, $type, 'input' );

		if ( ! isset( $field->more_attributes['step'] ) || $value % $field->more_attributes['step'] == 0 ) {
			if ( ! isset( $field->more_attributes['min'] ) || $value >= $field->more_attributes['min'] ) {
				if ( ! isset( $field->more_attributes['max'] ) || $value <= $field->more_attributes['max'] ) {
					return $value;
				}

				return self::error_handler( sprintf( __( 'The number %1$s is invalid. It must be lower than or equal to %2$s.', 'wpod' ), \LaL_WP_Plugin_Util::format( $value, $type, 'output' ), \LaL_WP_Plugin_Util::format( $field->more_attributes['max'], $type, 'output' ) ) );
			}

			return self::error_handler( sprintf( __( 'The number %1$s is invalid. It must be greater than or equal to %2$s.', 'wpod' ), \LaL_WP_Plugin_Util::format( $value, $type, 'output' ), \LaL_WP_Plugin_Util::format( $field->more_attributes['min'], $type, 'output' ) ) );
		}

		return self::error_handler( sprintf( __( 'The number %1$s is invalid since it is not divisible by %2$s.', 'wpod' ), \LaL_WP_Plugin_Util::format( $value, $type, 'output' ), \LaL_WP_Plugin_Util::format( $field->more_attributes['step'], $type, 'output' ) ) );
	}

	/**
	 * Validates a range value.
	 *
	 * Alias for `WPOD\Validator::number()`.
	 *
	 * @since 1.0.0
	 * @see WPOD\Validator::number()
	 * @param string|int|float $value the field value to validate
	 * @param WPOD\Components\ComponentBase $field the field component `$value` belongs to
	 * @return int|float|array the validated value or an error array
	 */
	public static function range( $value, $field ) {
		return self::number( $value, $field );
	}

	/**
	 * Validates a text value.
	 *
	 * The function strips out any HTML characters not allowed by using the plugin utility class.
	 *
	 * @since 1.0.0
	 * @param string $value the field value to validate
	 * @param WPOD\Components\ComponentBase $field the field component `$value` belongs to
	 * @return string|array the validated value or an error array
	 */
	public static function text( $value, $field ) {
		return \LaL_WP_Plugin_Util::format( $value, 'html', 'input' );
	}

	/**
	 * Validates a text value.
	 *
	 * Alias for `WPOD\Validator::text()`.
	 *
	 * @since 1.0.0
	 * @see WPOD\Validator::text()
	 * @param string $value the field value to validate
	 * @param WPOD\Components\ComponentBase $field the field component `$value` belongs to
	 * @return string|array the validated value or an error array
	 */
	public static function textarea( $value, $field ) {
		return self::text( $value, $field );
	}

	/**
	 * Validates a text value.
	 *
	 * Alias for `WPOD\Validator::textarea()`, with the small difference that this function automatically adds paragraphs to the value.
	 *
	 * @since 1.0.0
	 * @see WPOD\Validator::textarea()
	 * @param string $value the field value to validate
	 * @param WPOD\Components\ComponentBase $field the field component `$value` belongs to
	 * @return string|array the validated value or an error array
	 */
	public static function wysiwyg( $value, $field ) {
		return self::textarea( wpautop( $value ), $field );
	}

	/**
	 * Validates an email address.
	 *
	 * @since 1.0.0
	 * @param string $value the field value to validate
	 * @param WPOD\Components\ComponentBase $field the field component `$value` belongs to
	 * @return string|array the validated value or an error array
	 */
	public static function email( $value, $field ) {
		$old_mail = $value;
		$value = is_email( sanitize_email( $value ) );

		if ( $value ) {
			return $value;
		}

		return self::error_handler( sprintf( __( '%s is not a valid email address.', 'wpod' ), \LaL_WP_Plugin_Util::format( $old_mail, 'string', 'output' ) ) );
	}

	/**
	 * Validates a URL.
	 *
	 * @since 1.0.0
	 * @param string $value the field value to validate
	 * @param WPOD\Components\ComponentBase $field the field component `$value` belongs to
	 * @return string|array the validated value or an error array
	 */
	public static function url( $value, $field ) {
		if ( preg_match( '#http(s?)://(.+)#i', $value ) ) {
			return \LaL_WP_Plugin_Util::format( $value, 'url', 'output' );
		}

		return self::error_handler( sprintf( __( '%s is not a valid URL.', 'wpof' ), \LaL_WP_Plugin_Util::format( $value, 'url', 'output' ) ) );
	}

	/**
	 * Validates a datetime string.
	 *
	 * If min/max is/are provided in the field arguments, the function also checks if the value is within the allowed boundaries.
	 *
	 * @since 1.0.0
	 * @param string $value the field value to validate
	 * @param WPOD\Components\ComponentBase $field the field component `$value` belongs to
	 * @return string|array the validated value or an error array
	 */
	public static function datetime( $value, $field ) {
		$timestamp = mysql2date( 'U', $value );

		if ( ! isset( $field->more_attributes['min'] ) || $timestamp >= ( $timestamp_min = mysql2date( 'U', $field->more_attributes['min'] ) ) ) {
			if ( ! isset( $field->more_attributes['max'] ) || $timestamp <= ( $timestamp_max = mysql2date( 'U', $field->more_attributes['max'] ) ) ) {
				return $value;
			}

			return self::error_handler( sprintf( __( 'The date %1$s is invalid. It must not occur later than %2$s.', 'wpod' ), \LaL_WP_Plugin_Util::format( $timestamp, 'datetime', 'output' ), \LaL_WP_Plugin_Util::format( $timestamp_max, 'datetime', 'output' ) ) );
		}

		return self::error_handler( sprintf( __( 'The date %1$s is invalid. It must not occur earlier than %2$s.', 'wpod' ), \LaL_WP_Plugin_Util::format( $timestamp, 'datetime', 'output' ), \LaL_WP_Plugin_Util::format( $timestamp_min, 'datetime', 'output' ) ) );
	}

	/**
	 * Validates a date string.
	 *
	 * If min/max is/are provided in the field arguments, the function also checks if the value is within the allowed boundaries.
	 *
	 * @since 1.0.0
	 * @param string $value the field value to validate
	 * @param WPOD\Components\ComponentBase $field the field component `$value` belongs to
	 * @return string|array the validated value or an error array
	 */
	public static function date( $value, $field ) {
		$timestamp = mysql2date( 'U', $value );

		if ( ! isset( $field->more_attributes['min'] ) || $timestamp >= ( $timestamp_min = mysql2date( 'U', $field->more_attributes['min'] ) ) ) {
			if ( ! isset( $field->more_attributes['max'] ) || $timestamp <= ( $timestamp_max = mysql2date( 'U', $field->more_attributes['max'] ) ) ) {
				return $value;
			}

			return self::error_handler( sprintf( __( 'The date %1$s is invalid. It must not occur later than %2$s.', 'wpod' ), \LaL_WP_Plugin_Util::format( $timestamp, 'date', 'output' ), \LaL_WP_Plugin_Util::format( $timestamp_max, 'date', 'output' ) ) );
		}

		return self::error_handler( sprintf( __( 'The date %1$s is invalid. It must not occur earlier than %2$s.', 'wpod' ), \LaL_WP_Plugin_Util::format( $timestamp, 'date', 'output' ), \LaL_WP_Plugin_Util::format( $timestamp_min, 'date', 'output' ) ) );
	}

	/**
	 * Validates a time string.
	 *
	 * If min/max is/are provided in the field arguments, the function also checks if the value is within the allowed boundaries.
	 *
	 * @since 1.0.0
	 * @param string $value the field value to validate
	 * @param WPOD\Components\ComponentBase $field the field component `$value` belongs to
	 * @return string|array the validated value or an error array
	 */
	public static function time( $value, $field ) {
		$timestamp = mysql2date( 'U', $value );

		if ( ! isset( $field->more_attributes['min'] ) || $timestamp >= ( $timestamp_min = mysql2date( 'U', $field->more_attributes['min'] ) ) ) {
			if ( ! isset( $field->more_attributes['max'] ) || $timestamp <= ( $timestamp_max = mysql2date( 'U', $field->more_attributes['max'] ) ) ) {
				return $value;
			}

			return self::error_handler( sprintf( __( 'The time %1$s is invalid. It must not occur later than %2$s.', 'wpod' ), \LaL_WP_Plugin_Util::format( $timestamp, 'time', 'output' ), \LaL_WP_Plugin_Util::format( $timestamp_max, 'time', 'output' ) ) );
		}

		return self::error_handler( sprintf( __( 'The time %1$s is invalid. It must not occur earlier than %2$s.', 'wpod' ), \LaL_WP_Plugin_Util::format( $timestamp, 'time', 'output' ), \LaL_WP_Plugin_Util::format( $timestamp_min, 'time', 'output' ) ) );
	}

	/**
	 * Validates a hex color string.
	 *
	 * @since 1.0.0
	 * @param string $value the field value to validate
	 * @param WPOD\Components\ComponentBase $field the field component `$value` belongs to
	 * @return string|array the validated value or an error array
	 */
	public static function color( $value, $field )
	{
		if ( preg_match( '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/i', $value ) ) {
			return $value;
		}

		return self::error_handler( sprintf( __( '%s is not a valid hexadecimal color.', 'wpod' ), \LaL_WP_Plugin_Util::format( $value, 'string', 'output' ) ) );
	}

	/**
	 * Validates a WordPress media ID.
	 *
	 * This function by default allows all types of media.
	 * It can be called by other more specific functions to limit the permitted types.
	 *
	 * @since 1.0.0
	 * @param string|int $value the field value to validate
	 * @param WPOD\Components\ComponentBase $field the field component `$value` belongs to
	 * @param string|array $desired_types either 'all', a single mime type file extension or an array of mime type file extensions (optional)
	 * @param string $errmsg_append an additional error message to append if the type is not one of the $desired_types
	 * @return int|array the validated value or an error array
	 */
	public static function media( $value, $field, $desired_types = 'all', $errmsg_append = '' ) {
		$value = absint( $value );

		if ( get_post_type( $value ) == 'attachment' ) {
			$mime = get_post_mime_type( $value );

			$mime_types = get_allowed_mime_types();

			if ( 'all' != $desired_types ) {
				if ( is_string( $desired_types ) && isset( $mime_types[ $desired_types ] ) ) {
					$mime_value = $mime_types[ $desired_types ];
					$mime_types = array( $desired_types => $mime_value );
				} elseif ( is_array( $desired_types ) ) {
					$mime_types = array_intersect_key( $mime_types, array_flip( $desired_types ) );
				}
			}

			if ( in_array( $mime, $mime_types ) ) {
				return $value;
			}

			if ( ! empty( $errmsg_append ) ) {
				$errmsg_append = ' ' . $errmsg_append;
			}

			return self::error_handler( sprintf( __( 'The media file with ID %s is neither of the valid formats.', 'wpod' ), $value ) . $errmsg_append );
		}

		return self::error_handler( sprintf( __( 'The post with ID %s is not a WordPress media file.', 'wpod' ), $value ) );
	}

	/**
	 * Validates a WordPress image media ID.
	 *
	 * @since 1.0.0
	 * @see WPOD\Validator::media()
	 * @param string|int $value the field value to validate
	 * @param WPOD\Components\ComponentBase $field the field component `$value` belongs to
	 * @return int|array the validated value or an error array
	 */
	public static function image( $value, $field ) {
		return self::media( $value, $field, array(
			'jpg|jpeg|jpe',
			'gif',
			'png',
			'bmp',
			'tif|tiff',
			'ico',
		), __( 'It has to be an image file.', 'wpod' ) );
	}

	/**
	 * Validates a WordPress video media ID.
	 *
	 * @since 1.0.0
	 * @see WPOD\Validator::media()
	 * @param string|int $value the field value to validate
	 * @param WPOD\Components\ComponentBase $field the field component `$value` belongs to
	 * @return int|array the validated value or an error array
	 */
	public static function video( $value, $field ) {
		return self::media( $value, $field, array(
			'asf|asx',
			'wmv',
			'wmx',
			'wm',
			'avi',
			'divx',
			'flv',
			'mov|qt',
			'mpeg|mpg|mpe',
			'mp4|m4v',
			'ogv',
			'webm',
			'mkv',
		), __( 'It has to be a video file.', 'wpod' ) );
	}

	/**
	 * Validates a WordPress audio media ID.
	 *
	 * @since 1.0.0
	 * @see WPOD\Validator::media()
	 * @param string|int $value the field value to validate
	 * @param WPOD\Components\ComponentBase $field the field component `$value` belongs to
	 * @return int|array the validated value or an error array
	 */
	public static function audio( $value, $field ) {
		return self::media( $value, $field, array(
			'mp3|m4a|m4b',
			'ra|ram',
			'wav',
			'ogg|oga',
			'mid|midi',
			'wma',
			'wax',
			'mka',
		), __( 'It has to be an audio file.', 'wpod' ) );
	}

	/**
	 * Validates a WordPress archive media ID.
	 *
	 * @since 1.0.0
	 * @see WPOD\Validator::media()
	 * @param string|int $value the field value to validate
	 * @param WPOD\Components\ComponentBase $field the field component `$value` belongs to
	 * @return int|array the validated value or an error array
	 */
	public static function archive( $value, $field ) {
		return self::media( $value, $field, array(
			'tar',
			'zip',
			'gz|gzip',
			'rar',
			'7z',
		), __( 'It has to be a file archive.', 'wpod' ) );
	}

	/**
	 * Validates a WordPress favicon media ID.
	 *
	 * @since 1.0.0
	 * @see WPOD\Validator::media()
	 * @param string|int $value the field value to validate
	 * @param WPOD\Components\ComponentBase $field the field component `$value` belongs to
	 * @return int|array the validated value or an error array
	 */
	public static function favicon( $value, $field ) {
		return self::media( $value, $field, 'ico', __( 'It has to be a favicon.', 'wpod' ) );
	}

	/**
	 * Validates a WordPress PDF media ID.
	 *
	 * @since 1.0.0
	 * @see WPOD\Validator::media()
	 * @param string|int $value the field value to validate
	 * @param WPOD\Components\ComponentBase $field the field component `$value` belongs to
	 * @return int|array the validated value or an error array
	 */
	public static function pdf( $value, $field ) {
		return self::media( $value, $field, 'pdf', __( 'It has to be a PDF file.', 'wpod' ) );
	}

	/**
	 * Validates a repeatable field.
	 *
	 * A repeatable field actually consists of one or more rows where each row itself consists of several fields.
	 * From within this function other validation functions of this class are called accordingly.
	 *
	 * @since 1.0.0
	 * @param array $value the field value to validate
	 * @param WPOD\Components\ComponentBase $field the field component `$value` belongs to
	 * @return array the validated value or an error array
	 */
	public static function repeatable( $value, $field ) {
		if ( is_array( $value ) && count( $value ) > 0 ) {
			$errors = '';

			if ( $field->repeatable['limit'] > 0 && count( $value ) > $field->repeatable['limit'] ) {
				$orig_value = $value;
				$value = array();
				$counter = 0;

				foreach ( $orig_value as $key => $options ) {
					$value[ $key ] = $options;
					$counter++;
					if ( $counter == $field->repeatable['limit'] ) {
						break;
					}
				}
			}

			foreach ( $value as $key => &$options ) {
				foreach ( $field->repeatable['fields'] as $slug => $data ) {
					if ( ! isset( $options[ $slug ] ) ) {
						$options[ $slug ] = $data['default'];
					}

					$validate_args = (object) $data;
					$validate_args->slug = $slug;

					$options[ $slug ] = self::is_valid_empty( $options[ $slug ], $validate_args );
					if ( ! isset( $options[ $slug ]['errmsg'] ) && ! empty( $options[ $slug ] ) ) {
						if ( is_callable( $data['validate'] ) ) {
							$options[ $slug ] = call_user_func( $data['validate'], $options[ $slug ], $validate_args );
						} else {
							$options[ $slug ] = self::invalid_validation_function();
						}
					}

					if ( isset( $options[ $slug ]['errmsg'] ) ) {
						$errors .= '<br/><span class="repeatable-field-error">' . $data['title'] . ': ' . $options[ $slug ]['errmsg'] . '</span>';

						if ( isset( $options[ $slug ]['option'] ) ) {
							$options[ $slug ] = $options[ $slug ]['option'];
						} else {
							$options[ $slug ] = $data['default'];
						}
					}
				}
			}

			if ( empty( $errors ) ) {
				return $value;
			}

			return self::error_handler( __( 'Some errors occurred during validation the repeatable field:', 'wpod' ) . $errors, $value );
		}

		return array();
	}

	/**
	 * Error handling function for the validation.
	 *
	 * Whenever a validation function needs to return an error, it must call this function.
	 *
	 * This function returns an error array.
	 * It always contains a 'errmsg' key holding the error message.
	 * If specified, it also holds an 'value' key with the new value to use for the field.
	 * If this is not specified, the old field value will be used by the plugin.
	 *
	 * @since 1.0.0
	 * @param string $message the error message to display
	 * @param mixed $value the value to use for the field (optional)
	 * @return array the error array
	 */
	public static function error_handler( $message, $value = null ) {
		$ret = array( 'errmsg' => $message );

		if ( isset( $value ) ) {
			$ret['value'] = $value;
		}

		return $ret;
	}

	/**
	 * Validates if a required field is not empty.
	 *
	 * If the field is not required, the function just returns the original value.
	 *
	 * @internal
	 * @since 1.0.0
	 * @param mixed $value the field value to validate
	 * @param WPOD\Components\ComponentBase $field the field component `$value` belongs to
	 * @return mixed|array the validated value or an error array
	 */
	public static function is_valid_empty( $value, $field ) {
		$empty = false;

		if ( 'checkbox' == $field->type || ! isset( $field->more_attributes['required'] ) || ! $field->more_attributes['required'] ) {
			return $value;
		}

		switch ( $field->type ) {
			case 'multiselect':
			case 'multibox':
			case 'repeatable':
				$empty = count( $value );
				$empty = (bool) $empty;
				break;
			case 'media':
				$empty = absint( $value ) < 1;
				break;
			default:
				$empty = empty( $value );
		}

		if ( ! $empty ) {
			return $value;
		}

		return self::error_handler( __( 'The value must not be empty.', 'wpod' ) );
	}

	/**
	 * Fallback validation function.
	 *
	 * If a validation function for a field is invalid, this function is executed instead.
	 * It just returns an error array telling the user that there is no valid function specified.
	 *
	 * @internal
	 * @since 1.0.0
	 * @return array the error array
	 */
	public static function invalid_validation_function() {
		return self::error_handler( __( 'The validation function specified is invalid. It does not exist.', 'wpod' ) );
	}
}
