<?php
/**
 * Data access and utility functions
 *
 * @package WPOD
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 * @since 0.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! function_exists( 'wpod_get_options' ) ) {
	/**
	 * Returns the options for a tab.
	 *
	 * This function is basically a wrapper for the WordPress core function `get_option()`,
	 * however this function will automatically populate each option with its default value
	 * if the option is not available.
	 *
	 * @since 0.5.0
	 * @param string $tab_slug the tab slug to get the options for
	 * @param boolean $formatted whether to return automatically formatted values, ready for output (default is false)
	 * @return array the options as an associative array
	 */
	function wpod_get_options( $tab_slug, $formatted = false ) {
		$_options = get_option( $tab_slug, array() );

		if ( doing_action( 'wpod' ) || ! did_action( 'wpod' ) ) {
			return $_options;
		}

		$options = array();

		$tab = \WPDLib\Components\Manager::get( '*.*.' . $tab_slug, 'WPDLib\Components\Menu.WPOD\Components\Screen', true );
		if ( $tab ) {
			foreach ( $tab->get_children() as $section ) {
				foreach ( $section->get_children() as $field ) {
					$options[ $field->slug ] = \WPOD\Utility::parse_option( ( isset( $_options[ $field->slug ] ) ? $_options[ $field->slug ] : null ), $field, $formatted );
				}
			}
		}

		return $options;
	}
}

if ( ! function_exists( 'wpod_get_option' ) ) {
	/**
	 * Returns a single specified option of a tab.
	 *
	 * This function uses the WordPress core function `get_option()` to get the options array for the tab.
	 * If the required field option is not available, this function will automatically return its default value.
	 *
	 * @since 0.5.0
	 * @param string $tab_slug the tab slug to get the option for
	 * @param string $field_slug the field slug to get the option for
	 * @param boolean $formatted whether to return an automatically formatted value, ready for output (default is false)
	 * @return mixed the option
	 */
	function wpod_get_option( $tab_slug, $field_slug, $formatted = false ) {
		$_options = get_option( $tab_slug, array() );

		if ( doing_action( 'wpod' ) || ! did_action( 'wpod' ) ) {
			if ( isset( $_options[ $field_slug ] ) ) {
				return $_options[ $field_slug ];
			}
			return null;
		}

		$option = null;

		$field = \WPDLib\Components\Manager::get( '*.*.' . $tab_slug . '.*.' . $field_slug, 'WPDLib\Components\Menu.WPOD\Components\Screen', true );
		if ( $field ) {
			$option = \WPOD\Utility::parse_option( ( isset( $_options[ $field->slug ] ) ? $_options[ $field->slug ] : null ), $field, $formatted );
		}

		return $option;
	}
}
