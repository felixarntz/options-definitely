<?php
/**
 * @package WPOD
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! function_exists( 'wpod_get_options' ) ) {
	/**
	 * Returns the options for a tab.
	 *
	 * This function is basically a wrapper for the WordPress core function get_option(),
	 * however this function will automatically populate each option with its default value
	 * if the option is not available.
	 *
	 * @since 0.5.0
	 * @param string $tab_slug the tab slug to get the options for
	 * @return array the options as an associative array
	 */
	function wpod_get_options( $tab_slug, $formatted = false ) {
		$_options = get_option( $tab_slug, array() );

		$options = array();

		$tab = \WPDLib\Components\Manager::get( '*.*.' . $tab_slug, 'WPDLib\Components\Menu.WPOD\Components\Screen', true );
		if ( $tab ) {
			foreach ( $tab->get_children() as $section ) {
				foreach ( $section->get_children() as $field ) {
					if ( isset( $_options[ $field->slug ] ) ) {
						$options[ $field->slug ] = $field->_field->parse( $_options[ $field->slug ], $formatted );
					} else {
						$options[ $field->slug ] = $field->_field->parse( $field->default, $formatted );
					}
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
	 * This function uses the WordPress core function get_option() to get the options array for the tab.
	 * If the required field option is not available, this function will automatically return its default value.
	 *
	 * @since 0.5.0
	 * @param string $tab_slug the tab slug to get the option for
	 * @param string $field_slug the field slug to get the option for
	 * @return mixed the option
	 */
	function wpod_get_option( $tab_slug, $field_slug, $formatted = false ) {
		$_options = get_option( $tab_slug, array() );

		$option = null;

		$field = \WPDLib\Components\Manager::get( '*.*.' . $tab_slug . '.*.' . $field_slug, 'WPDLib\Components\Menu.WPOD\Components\Screen', true );
		if ( $field ) {
			if ( isset( $_options[ $field->slug ] ) ) {
				$option = $field->_field->parse( $_options[ $field->slug ], $formatted );
			} else {
				$option = $field->_field->parse( $field->default, $formatted );
			}
		}

		return $option;
	}
}

if ( ! function_exists( 'wpod_component_to_slug' ) ) {
	/**
	 * Transforms a component into its slug.
	 *
	 * This is intended to be used as a callback function, for example to use with array_map().
	 *
	 * @internal
	 * @since 0.5.0
	 * @param WPOD\Components\ComponentBase $component any plugin component
	 * @return string the slug of the component
	 */
	function wpod_component_to_slug( $component ) {
		return $component->slug;
	}
}

if ( ! function_exists( 'wpod_current_user_can' ) ) {
	/**
	 * Checks if the current user is allowed to access a specific component.
	 *
	 * @internal
	 * @since 0.5.0
	 * @param WPOD\Components\ComponentBase $component any plugin component
	 * @return bool true if the user may access the component, otherwise false
	 */
	function wpod_current_user_can( $component ) {
		$cap = $component->capability;

		if ( null === $cap || current_user_can( $cap ) ) {
			return true;
		}

		return false;
	}
}
