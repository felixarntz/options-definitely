<?php
/**
 * @package WPOD
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPOD;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPOD\Utility' ) ) {
	/**
	 * This class contains some utility functions.
	 *
	 * @internal
	 * @since 0.5.0
	 */
	class Utility {

		/**
		 * This function correctly parses (and optionally formats) an option.
		 *
		 * @see wpod_get_options()
		 * @see wpod_get_option()
		 * @since 0.5.0
		 * @param mixed $option the option to parse (or format)
		 * @param WPOD\Components\Field $field the field component the option belongs to
		 * @param boolean $formatted whether to return automatically formatted values, ready for output (default is false)
		 * @return mixed the parsed (or formatted) option
		 */
		public static function parse_option( $option, $field, $formatted = false ) {
			if ( null !== $option ) {
				$option = $field->_field->parse( $option, $formatted );
			} else {
				$option = $field->_field->parse( $field->default, $formatted );
			}

			return $option;
		}

		/**
		 * This function adds help tabs and a help sidebar to a screen.
		 *
		 * @since 0.5.0
		 * @param WP_Screen $screen the screen to add the help data to
		 * @param array $data help tabs and sidebar (if specified)
		 */
		public static function render_help( $screen, $data ) {
			foreach ( $data['tabs'] as $slug => $tab ) {
				$args = array_merge( array( 'id' => $slug ), $tab );

				$screen->add_help_tab( $args );
			}

			if ( ! empty( $data['sidebar'] ) ) {
				$screen->set_help_sidebar( $data['sidebar'] );
			}
		}

		/**
		 * Validates the position argument.
		 *
		 * @see WPOD\Components\Screen
		 * @see WPOD\Components\Tab
		 * @see WPOD\Components\Section
		 * @see WPOD\Components\Field
		 * @since 0.5.0
		 * @param array $args array of arguments
		 * @return array the validated arguments
		 */
		public static function validate_position_args( $args ) {
			if ( null !== $args['position'] ) {
				$args['position'] = floatval( $args['position'] );
			}

			return $args;
		}

		/**
		 * Validates any help arguments.
		 *
		 * @see WPOD\Components\Screen
		 * @since 0.5.0
		 * @param array $args array of arguments
		 * @param string $key the name of the argument to validate
		 * @return array the validated arguments
		 */
		public static function validate_help_args( $args, $key ) {
			if( ! is_array( $args[ $key ] ) ) {
				$args[ $key ] = array();
			}
			if ( ! isset( $args[ $key ]['tabs'] ) || ! is_array( $args[ $key ]['tabs'] ) ) {
				$args[ $key ]['tabs'] = array();
			}
			if ( ! isset( $args[ $key ]['sidebar'] ) ) {
				$args[ $key ]['sidebar'] = '';
			}
			foreach ( $args[ $key ]['tabs'] as &$tab ) {
				$tab = wp_parse_args( $tab, array(
					'title'			=> __( 'Help tab title', 'options-definitely' ),
					'content'		=> '',
					'callback'		=> false,
				) );
			}

			return $args;
		}

	}
}
