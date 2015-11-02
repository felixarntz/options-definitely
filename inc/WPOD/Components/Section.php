<?php
/**
 * @package WPOD
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPOD\Components;

use WPOD\App as App;
use WPOD\Utility as Utility;
use WPDLib\Components\Base as Base;
use WPDLib\FieldTypes\Manager as FieldManager;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPOD\Components\Section' ) ) {
	/**
	 * Class for a section component.
	 *
	 * A section denotes a settings section inside an options tab in the WordPress admin.
	 * It has no further meaning other than to visually group certain fields.
	 *
	 * @internal
	 * @since 0.5.0
	 */
	class Section extends Base {

		/**
		 * Class constructor.
		 *
		 * @since 0.5.0
		 * @param string $slug the section slug
		 * @param array $args array of section properties
		 */
		public function __construct( $slug, $args ) {
			parent::__construct( $slug, $args );
			$this->validate_filter = 'wpod_section_validated';
		}

		/**
		 * Registers the settings section.
		 *
		 * If the parent tab is draggable, this function will also add a meta box for this section.
		 *
		 * @since 0.5.0
		 * @param WPOD\Components\Tab|null $parent_tab the parent tab component of this section or null
		 */
		public function register( $parent_tab = null ) {
			global $wp_settings_sections;

			if ( null === $parent_tab ) {
				$parent_tab = $this->get_parent();
			}

			add_settings_section( $this->slug, $this->args['title'], false, $parent_tab->slug );
			$wp_settings_sections[ $parent_tab->slug ][ $this->slug ]['description'] = $this->args['description'];

			if ( 'draggable' == $parent_tab->mode ) {
				add_meta_box( $this->slug, $this->args['title'], array( $this, 'render' ), $parent_tab->slug, 'normal' );
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
			// only display the title if the section is not displayed as a metabox
			if ( null !== $metabox || false === $metabox ) {
				echo '<h3>' . $this->args['title'] . '</h3>';
			}

			$parent_tab = $this->get_parent();

			/**
			 * This action can be used to display additional content on top of this section.
			 *
			 * @since 0.5.0
			 * @param string the slug of the current section
			 * @param array the arguments array for the current section
			 * @param string the slug of the current tab
			 */
			do_action( 'wpod_section_before', $this->slug, $this->args, $parent_tab->slug );

			if ( ! empty( $this->args['description'] ) ) {
				echo '<p class="description">' . $this->args['description'] . '</p>';
			}

			if ( count( $this->get_children() ) > 0 ) {
				$table_atts = array(
					'class'		=> 'form-table wpdlib-form-table',
				);

				/**
				 * This filter can be used to adjust the form table attributes.
				 *
				 * @since 0.5.0
				 * @param array the associative array of form table attributes
				 * @param WPOD\Components\Section current section instance
				 */
				$table_atts = apply_filters( 'wpod_table_atts', $table_atts, $this );

				echo '<table' . FieldManager::make_html_attributes( $table_atts, false, false ) . '>';

				do_settings_fields( $parent_tab->slug, $this->slug );

				echo '</table>';
			} elseif ( $this->args['callback'] && is_callable( $this->args['callback'] ) ) {
				call_user_func( $this->args['callback'] );
			} else {
				App::doing_it_wrong( __METHOD__, sprintf( __( 'There are no fields to display for section %s. Either add some or provide a valid callback function instead.', 'options-definitely' ), $this->slug ), '0.5.0' );
			}

			/**
			 * This action can be used to display additional content at the bottom of this section.
			 *
			 * @since 0.5.0
			 * @param string the slug of the current section
			 * @param array the arguments array for the current section
			 * @param string the slug of the current tab
			 */
			do_action( 'wpod_section_after', $this->slug, $this->args, $parent_tab->slug );
		}

		/**
		 * Validates the arguments array.
		 *
		 * @since 0.5.0
		 */
		public function validate( $parent = null ) {
			$status = parent::validate( $parent );

			if ( $status === true ) {
				$this->args = Utility::validate_position_args( $this->args );
			}

			return $status;
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
				'title'			=> __( 'Section title', 'options-definitely' ),
				'description'	=> '',
				'callback'		=> false, //only used if no fields are attached to this section
				'position'		=> null,
			);

			/**
			 * This filter can be used by the developer to modify the default values for each section component.
			 *
			 * @since 0.5.0
			 * @param array the associative array of default values
			 */
			return apply_filters( 'wpod_section_defaults', $defaults );
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
