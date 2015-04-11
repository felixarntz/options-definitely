<?php
/**
 * @package WPOD
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

function wpod_get_options( $tab_slug ) {
	$options = get_option( $tab_slug );
	if ( ! $options ) {
		$options = array();
	}

	$fields = WPOD\Framework::instance()->query( array(
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

function wpod_get_option( $tab_slug, $field_slug ) {
	$options = get_option( $tab_slug );
	if ( isset( $options[ $field_slug ] ) ) {
		return $options[ $field_slug ];
	}

	$field = WPOD\Framework::instance()->query( array(
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

function wpod_is_image( $attachment_id ) {
	$mime = get_post_mime_type( $attachment_id );

	$mime_types = get_allowed_mime_types();
	$image_types = array(
		'jpg|jpeg|jpe',
		'gif',
		'png',
		'bmp',
		'tif|tiff',
		'ico',
	);
	$mime_types = array_intersect_key( $mime_types, array_flip( $image_types ) );

	if ( in_array( $mime, $mime_types ) ) {
		return true;
	}

	return false;
}

/* AJAX FUNCTIONS */

function wpod_ajax_insert_repeatable_row() {
	if ( wp_verify_nonce( $_POST['nonce'], 'wpod-ajax-request' ) ) {
		$key = absint( $_POST['key'] );
		$tab_slug = esc_attr( $_POST['parent_slug'] );
		$field_slug = esc_attr( $_POST['slug'] );

		$field = \WPOD\Framework::instance()->query( array(
			'slug'        => $field_slug,
			'type'        => 'field',
			'parent_slug' => $tab_slug,
			'parent_type' => 'tab',
		), true );

		if( $field ) {
			$id_prefix = $tab_slug . '-' . $field_slug;
			$name_prefix = $tab_slug . '[' . $field_slug . ']';

			$field->render_repeatable_row( $key, $id_prefix, $name_prefix );
		}
	}
	die();
}
add_action( 'wp_ajax_wpod_insert_repeatable', 'wpod_ajax_insert_repeatable_row' );

/* CALLBACK HELPER FUNCTIONS */

function wpod_component_to_slug( $component ) {
	return $component->slug;
}

function wpod_current_user_can( $component ) {
	$cap = $component->capability;

	if ( null === $cap || current_user_can( $cap ) ) {
		return true;
	}

	return false;
}
