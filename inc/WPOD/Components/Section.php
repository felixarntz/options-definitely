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

class Section extends ComponentBase {

	public function register( $parent_tab ) {
		global $wp_settings_sections;

		add_settings_section( $this->slug, $this->args['title'], false, $parent_tab->slug );
		$wp_settings_sections[ $parent_tab->slug ][ $this->slug ]['description'] = $this->args['description'];

		if ( 'draggable' == $parent_tab->mode ) {
			add_meta_box( $this->slug, $this->args['title'], array( $this, 'render' ), $parent_tab->slug, 'normal' );
		}
	}

	public function render( $metabox = true ) {
		if ( null !== $metabox || false === $metabox ) {
			echo '<h3>' . $this->args['title'] . '</h3>';
		}

		do_action( 'wpod_section_before', $this->slug, $this->args, $this->parent );

		if ( ! empty( $this->args['description'] ) ) {
			echo '<p class="description">' . $this->args['description'] . '</p>';
		}

		$fields = \WPOD\Framework::instance()->query( array(
			'type'			=> 'field',
			'parent_slug'	=> $this->slug,
			'parent_type'	=> 'section',
		) );

		if ( count( $fields ) > 0 ) {
			$table_atts = array(
				'class'		=> 'form-table',
			);
			$table_atts = apply_filters( 'wpod_table_atts', $table_atts, $this );

			echo '<table' . \LaL_WP_Plugin_Util::make_html_attributes( $table_atts, false, false ) . '>';

			do_settings_fields( $this->parent, $this->slug );

			echo '</table>';
		} elseif ( $this->args['callback'] && is_callable( $this->args['callback'] ) ) {
			call_user_func( $this->args['callback'] );
		} else {
			\LaL_WP_Plugin_Util::get( 'options-definitely' )->doing_it_wrong( __METHOD__, sprintf( __( 'There are no fields to display for section %s. Either add some or provide a valid callback function instead.', 'wpod' ), $this->slug ), '0.5.0' );
		}

		do_action( 'wpod_section_after', $this->slug, $this->args, $this->parent );
	}

	protected function get_defaults()
	{
		$defaults = array(
			'title'			=> __( 'Section title', 'wpod' ),
			'description'	=> '',
			'callback'		=> false, //only used if no fields are attached to this section
		);

		return apply_filters( 'wpod_section_defaults', $defaults );
	}
}
