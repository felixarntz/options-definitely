<?php
/**
 * @package WPOD
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

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
function wpod_get_options( $tab_slug ) {
	$options = get_option( $tab_slug );
	if ( ! $options ) {
		$options = array();
	}

	$fields = WPOD\App::instance()->query( array(
		'type'          => 'field',
		'parent_slug'   => $tab_slug,
		'parent_type'   => 'tab',
	) );

	foreach ( $fields as $field ) {
		if ( ! isset( $options[ $field->slug ] ) ) {
			$options[ $field->slug ] = $field->default;
		}
	}

	return $options;
}

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
function wpod_get_option( $tab_slug, $field_slug ) {
	$options = get_option( $tab_slug );
	if ( isset( $options[ $field_slug ] ) ) {
		return $options[ $field_slug ];
	}

	$field = WPOD\App::instance()->query( array(
		'slug'          => $field_slug,
		'type'          => 'field',
		'parent_slug'   => $tab_slug,
		'parent_type'   => 'tab',
	), true );

	if ( $field ) {
		return $field->default;
	}

	return false;
}

/**
 * Checks whether a specific WordPress attachment is a valid file type.
 *
 * The second parameter can be a file extension or a file type to check for (or an array containing multiple).
 *
 * @since 0.5.0
 * @param int $attachment_id the ID of the attachment
 * @param string|array $desired_types allowed file extensions / types (or 'all' to allow everything)
 * @return bool true if the attachment is an image, otherwise false
 */
function wpod_is_valid_file_type( $attachment_id, $desired_types = 'all' ) {
	$filename = get_attached_file( $attachment_id );
	if ( $filename ) {
		$extension = wp_check_filetype( get_attached_file( $attachment_id ) );
		$extension = $extension['ext'];
		if ( $extension ) {
			if ( 'all' == $desired_types || ! $desired_types ) {
				return true;
			}

			if ( ! is_array( $desired_types ) ) {
				$desired_types = array( $desired_types );
			}

			if ( in_array( strtolower( $extension ), $desired_types ) ) {
				return true;
			}

			$type = wp_ext2type( $extension );

			if ( $type !== null && in_array( $type, $desired_types ) ) {
				return true;
			}
		}
	}
	return false;
}

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
