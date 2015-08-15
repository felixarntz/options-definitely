<?php
/**
 * @package WPOD
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPOD\Components;

use WPDLib\Components\Manager as ComponentManager;
use WPDLib\Components\Base as Base;
use WPDLib\FieldTypes\Manager as FieldManager;
use WPDLib\Util\Error as UtilError;
use WP_Error as WPError;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPOD\Components\Field' ) ) {
	/**
	 * Class for a field component.
	 *
	 * A field denotes a settings field, i.e. both the field option and the visual input in the WordPress admin.
	 * Since WPOD stores all options inside an array (where the option name is the tab slug), the field slugs are used as array keys in that options array.
	 *
	 * @internal
	 * @since 0.5.0
	 */
	class Field extends Base {

		/**
		 * @since 0.5.0
		 * @var WPDLib\FieldTypes\Base Holds the field type object from WPDLib.
		 */
		protected $_field = null;

		public function __get( $property ) {
			$value = parent::__get( $property );
			if ( null === $value ) {
				$value = $this->_field->$property;
			}
			return $value;
		}

		/**
		 * Registers the settings fields.
		 *
		 * @since 0.5.0
		 * @param WPOD\Components\Tab $parent_tab the parent tab component of this field
		 * @param WPOD\Components\Section $parent_section the parent section component of this field
		 */
		public function register( $parent_tab = null, $parent_section = null ) {
			if ( null === $parent_section ) {
				$parent_section = $this->get_parent();
			}
			if ( null === $parent_tab ) {
				$parent_tab = $parent_section->get_parent();
			}

			add_settings_field( $this->slug, $this->args['title'], array( $this, 'render' ), $parent_tab->slug, $parent_section->slug );
		}

		/**
		 * Renders the field.
		 *
		 * This function will show the input field(s) in the WordPress admin.
		 *
		 * @since 0.5.0
		 */
		public function render() {
			$parent_section = $this->get_parent();
			$parent_tab = $parent_section->get_parent();

			/**
			 * This action can be used to display additional content on top of this field.
			 *
			 * @since 0.5.0
			 * @param string the slug of the current field
			 * @param array the arguments array for the current field
			 * @param string the slug of the current section
			 * @param string the slug of the current tab
			 */
			do_action( 'wpod_field_before', $this->slug, $this->args, $parent_section->slug, $parent_tab->slug );

			$option = wpod_get_option( $parent_tab->slug, $this->slug );

			$this->_field->display( $option );

			if ( ! empty( $this->args['description'] ) ) {
				if ( 'checkbox' != $this->args['type'] ) {
					echo '<br/>';
				}
				echo '<span class="description">' . $this->args['description'] . '</span>';
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
			do_action( 'wpod_field_after', $this->slug, $this->args, $parent_section->slug, $parent_tab->slug );
		}

		/**
		 * Validates the option for this field.
		 *
		 * @see WPOD\Components\Tab::validate_options()
		 * @since 0.5.0
		 * @param mixed $option the new option value to validate
		 * @return mixed either the validated option or a WP_Error object
		 */
		public function validate_option( $option = null, $skip_required = false ) {
			if ( $this->args['required'] && ! $skip_required ) {
				if ( $option === null || $this->_field->is_empty( $option ) ) {
					return new WPError( 'invalid_empty_value', __( 'No value was provided for the required field.', 'wpod' ) );
				}
			}
			return $this->_field->validate( $option );
		}

		/**
		 * Validates the arguments array.
		 *
		 * @since 0.5.0
		 */
		public function validate( $parent = null ) {
			$status = parent::validate( $parent );

			if ( $status === true ) {
				if ( is_array( $this->args['class'] ) ) {
					$this->args['class'] = implode( ' ', $this->args['class'] );
				}

				if ( isset( $this->args['options'] ) && ! is_array( $this->args['options'] ) ) {
					$this->args['options'] = array();
				}

				$parent_section = $this->get_parent();
				$parent_tab = $parent_section->get_parent();

				$this->args['id'] = $parent_tab->slug . '-' . $this->slug;
				$this->args['name'] = $parent_tab->slug . '[' . $this->slug . ']';

				$this->_field = FieldManager::get_instance( $this->args );
				if ( $this->_field === null ) {
					return new UtilError( 'no_valid_field_type', sprintf( __( 'The field type %1$s assigned to the field component %2$s is not a valid field type.', 'wpod' ), $this->args['type'], $this->slug ), '', ComponentManager::get_scope() );
				}
				if ( null === $this->args['default'] ) {
					$this->args['default'] = $this->_field->validate();
				}
			}

			return $status;
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
				'class'				=> '',
				'default'			=> null,
				'required'			=> false,
			);

			/**
			 * This filter can be used by the developer to modify the default values for each field component.
			 *
			 * @since 0.5.0
			 * @param array the associative array of default values
			 */
			return apply_filters( 'wpod_field_defaults', $defaults );
		}

		/**
		 * Returns whether this component supports multiple parents.
		 *
		 * @since 0.5.0
		 * @return bool
		 */
		protected function supports_multiparents() {
			return false;
		}

		/**
		 * Returns whether this component supports global slugs.
		 *
		 * If it does not support global slugs, the function either returns false for the slug to be globally unique
		 * or the class name of a parent component to ensure the slug is unique within that parent's scope.
		 *
		 * @since 0.5.0
		 * @return bool|string
		 */
		protected function supports_globalslug() {
			return 'WPOD\Components\Tab';
		}
	}
}
