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
 * Class for a section component.
 *
 * A section denotes a settings section inside an options tab in the WordPress admin.
 * It has no further meaning other than to visually group certain fields.
 *
 * @internal
 * @since 0.5.0
 */
class Section extends ComponentBase {

	/**
	 * Registers the settings section.
	 *
	 * If the parent tab is draggable, this function will also add a meta box for this section.
	 *
	 * @since 0.5.0
	 * @param WPOD\Components\Tab $parent_tab the parent tab component of this section
	 */
	public function register( $parent_tab ) {
		global $wp_settings_sections;

		add_settings_section( $this->real_slug, $this->args['title'], false, $parent_tab->slug );
		$wp_settings_sections[ $parent_tab->slug ][ $this->real_slug ]['description'] = $this->args['description'];

		if ( 'draggable' == $parent_tab->mode ) {
			add_meta_box( $this->real_slug, $this->args['title'], array( $this, 'render' ), $parent_tab->slug, 'normal' );
		}
	}

	/**
	 * Renders the section.
	 *
	 * It displays the title and description (if available) for the section.
	 * Then it shows the fields of this section or, if no fields are available, calls the callback function.
	 *
	 * @since 0.5.0
	 * @param boolean $metabox if this function is called inside a metabox, this parameter needs to be true, otherwise it has to be explicitly false
	 */
	public function render( $metabox = true ) {
		if ( null !== $metabox || false === $metabox ) {
			echo '<h3>' . $this->args['title'] . '</h3>';
		}

		/**
		 * This action can be used to display additional content on top of this section.
		 *
		 * @since 0.5.0
		 * @param string the slug of the current section
		 * @param array the arguments array for the current section
		 * @param string the slug of the current tab
		 */
		do_action( 'wpod_section_before', $this->real_slug, $this->args, $this->parent );

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

			do_settings_fields( $this->parent, $this->real_slug );

			echo '</table>';
		} elseif ( $this->args['callback'] && is_callable( $this->args['callback'] ) ) {
			call_user_func( $this->args['callback'] );
		} else {
			\LaL_WP_Plugin_Util::get( 'options-definitely' )->doing_it_wrong( __METHOD__, sprintf( __( 'There are no fields to display for section %s. Either add some or provide a valid callback function instead.', 'wpod' ), $this->real_slug ), '0.5.0' );
		}

		/**
		 * This action can be used to display additional content at the bottom of this section.
		 *
		 * @since 0.5.0
		 * @param string the slug of the current section
		 * @param array the arguments array for the current section
		 * @param string the slug of the current tab
		 */
		do_action( 'wpod_section_after', $this->real_slug, $this->args, $this->parent );
	}

	/**
	 * Returns the keys of the arguments array and their default values.
	 *
	 * Read the plugin guide for more information about the section arguments.
	 *
	 * @since 0.5.0
	 * @return array
	 */
	protected function get_defaults() {
		$defaults = array(
			'title'			=> __( 'Section title', 'wpod' ),
			'description'	=> '',
			'callback'		=> false, //only used if no fields are attached to this section
		);

		/**
		 * This filter can be used by the developer to modify the default values for each section component.
		 *
		 * @since 0.5.0
		 * @param array the associative array of default values
		 */
		return apply_filters( 'wpod_section_defaults', $defaults );
	}
}
