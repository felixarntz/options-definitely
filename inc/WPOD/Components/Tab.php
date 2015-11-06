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

if ( ! class_exists( 'WPOD\Components\Tab' ) ) {
	/**
	 * Class for a tab component.
	 *
	 * A tab denotes a tab inside an options screen in the WordPress admin.
	 * The slug of a tab, at the same time, is the slug of the option (or rather array of options) stored in WordPress.
	 *
	 * @internal
	 * @since 0.5.0
	 */
	class Tab extends Base {

		/**
		 * Class constructor.
		 *
		 * @since 0.5.0
		 * @param string $slug the tab slug
		 * @param array $args array of tab properties
		 */
		public function __construct( $slug, $args ) {
			parent::__construct( $slug, $args );
			$this->validate_filter = 'wpod_tab_validated';
		}

		/**
		 * Registers the setting for this tab.
		 *
		 * @since 0.5.0
		 */
		public function register() {
			if ( count( $this->get_children() ) > 0 ) {
				register_setting( $this->slug, $this->slug, array( $this, 'validate_options' ) );
			}
		}

		/**
		 * Renders the tab.
		 *
		 * It displays the tab description (if available) and renders the form tag.
		 * Inside the form tag, it iterates through all the sections belonging to this tab and calls each one's `render()` function.
		 *
		 * If no sections are available for this tab, the function will try to call the tab callback function to generate the output.
		 *
		 * If the tab is draggable (i.e. uses meta boxes), the meta boxes are handled in here as well.
		 *
		 * @since 0.5.0
		 */
		public function render() {
			$parent_screen = $this->get_parent();

			settings_errors( $this->slug );

			/**
			 * This action can be used to display additional content on top of this tab.
			 *
			 * @since 0.5.0
			 * @param string the slug of the current tab
			 * @param array the arguments array for the current tab
			 * @param string the slug of the current screen
			 */
			do_action( 'wpod_tab_before', $this->slug, $this->args, $parent_screen->slug );

			if ( ! empty( $this->args['description'] ) ) {
				echo '<p class="description">' . $this->args['description'] . '</p>';
			}

			if ( count( $this->get_children() ) > 0 ) {
				$this->render_sections();
			} elseif ( $this->args['callback'] && is_callable( $this->args['callback'] ) ) {
				call_user_func( $this->args['callback'] );
			} else {
				App::doing_it_wrong( __METHOD__, sprintf( __( 'There are no sections to display for tab %s. Either add some or provide a valid callback function instead.', 'options-definitely' ), $this->slug ), '0.5.0' );
			}

			if ( 'draggable' == $this->args['mode'] ) {
				$this->print_metaboxes_script();
			}

			/**
			 * This action can be used to display additional content at the bottom of this tab.
			 *
			 * @since 0.5.0
			 * @param string the slug of the current tab
			 * @param array the arguments array for the current tab
			 * @param string the slug of the current screen
			 */
			do_action( 'wpod_tab_after', $this->slug, $this->args, $parent_screen->slug );
		}

		/**
		 * Validates the options for this tab's setting.
		 *
		 * It iterates through all the fields (i.e. options) of this tab and validates each one's value.
		 * If a field is not set for some reason, its default value is saved.
		 *
		 * Furthermore this function adds settings errors if any occur.
		 *
		 * @since 0.5.0
		 * @param array $options the unvalidated options
		 * @return array the validated options
		 */
		public function validate_options( $options ) {
			$options_validated = array();

			$options_old = get_option( $this->slug, array() );

			$errors = array();

			$changes = false;

			foreach ( $this->get_children() as $section ) {
				foreach ( $section->get_children() as $field ) {
					$option_old = $field->default;
					if ( isset( $options_old[ $field->slug ] ) ) {
						$option_old = $options_old[ $field->slug ];
					} else {
						$options_old[ $field->slug ] = $option_old;
					}

					$option = null;
					if ( isset( $options[ $field->slug ] ) ) {
						$option = $options[ $field->slug ];
					}

					list( $option_validated, $error, $changed ) = $this->validate_option( $field, $option, $option_old );

					$options_validated[ $field->slug ] = $option_validated;
					if ( $error ) {
						$errors[ $field->slug ] = $error;
					} elseif ( $changed ) {
						$changes = true;
					}
				}
			}

			if ( $changes ) {
				/**
				 * This action can be used to perform additional steps when the options of this tab were updated.
				 *
				 * @since 0.5.0
				 * @param array the updated option values as $field_slug => $value
				 * @param array the previous option values as $field_slug => $value
				 */
				do_action( 'wpod_update_options_' . $this->slug, $options_validated, $options_old );
			}

			/**
			 * This filter can be used by the developer to modify the validated options right before they are saved.
			 *
			 * @since 0.5.0
			 * @param array the associative array of options and their values
			 */
			$options_validated = apply_filters( 'wpod_validated_options', $options_validated );

			$this->add_settings_message( $errors );

			return $options_validated;
		}

		/**
		 * Enqueues necessary stylesheets and scripts for this tab.
		 *
		 * In addition to stylesheets and scripts, this function will also add the metabox scripts if the tab is draggable.
		 *
		 * @since 0.5.0
		 */
		public function enqueue_assets() {
			if ( 'draggable' == $this->args['mode'] ) {
				wp_enqueue_script( 'common' );
				wp_enqueue_script( 'wp-lists' );
				wp_enqueue_script( 'postbox' );

				add_action( 'admin_head', array( $this, 'fix_metabox_styles' ) );
			}

			$_fields = array();
			foreach ( $this->get_children() as $section ) {
				foreach ( $section->get_children() as $field ) {
					$_fields[] = $field->_field;
				}
			}

			FieldManager::enqueue_assets( $_fields );
		}

		/**
		 * Outputs an inline style tag to fix a styling issue with metaboxes on option screens.
		 *
		 * @since 0.5.0
		 */
		public function fix_metabox_styles() {
			?>
			<style type="text/css">
				.postbox-container {
					float: none;
				}
			</style>
			<?php
		}

		/**
		 * Validates the arguments array.
		 *
		 * @since 0.5.0
		 * @param WPOD\Components\Screen $parent the parent component
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
		 * Read the plugin guide for more information about the tab arguments.
		 *
		 * @since 0.5.0
		 * @return array
		 */
		protected function get_defaults() {
			$defaults = array(
				'title'			=> __( 'Tab title', 'options-definitely' ),
				'description'	=> '',
				'capability'	=> 'manage_options',
				'mode'			=> 'default',
				'callback'		=> false, //only used if no sections are attached to this tab
				'position'		=> null,
			);

			/**
			 * This filter can be used by the developer to modify the default values for each tab component.
			 *
			 * @since 0.5.0
			 * @param array the associative array of default values
			 */
			return apply_filters( 'wpod_tab_defaults', $defaults );
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
			return false;
		}

		/**
		 * Renders the sections of this tab.
		 *
		 * If the tab is set to be draggable, the function will run its metabox action.
		 * Otherwise it will just manually output the sections.
		 *
		 * @since 0.5.0
		 */
		protected function render_sections() {
			$form_atts = array(
				'id'			=> $this->slug,
				'action'		=> admin_url( 'options.php' ),
				'method'		=> 'post',
				'novalidate'	=> true,
			);

			/**
			 * This filter can be used to adjust the form attributes.
			 *
			 * @since 0.5.0
			 * @param array the associative array of form attributes
			 * @param WPOD\Components\Tab current tab instance
			 */
			$form_atts = apply_filters( 'wpod_form_atts', $form_atts, $this );

			echo '<form' . FieldManager::make_html_attributes( $form_atts, false, false ) . '>';

			if ( 'draggable' == $this->args['mode'] ) {
				wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
				wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );

				echo '<div class="metabox-holder">';
				echo '<div class="postbox-container">';

				do_meta_boxes( $this->slug, 'normal', null );

				echo '</div>';
				echo '</div>';
			} else {
				foreach ( $this->get_children() as $section ) {
					$section->render( false );
				}
			}

			settings_fields( $this->slug );

			submit_button();

			echo '</form>';
		}

		/**
		 * Outputs a script to handle metaboxes inside this tab.
		 *
		 * @since 0.5.0
		 */
		protected function print_metaboxes_script() {
			?>
			<script type="text/javascript">
			//<![CDATA[
			jQuery(document).ready( function ($) {
			  // close postboxes that should be closed
			  $('.if-js-closed').removeClass('if-js-closed').addClass('closed');
			  // postboxes setup
			  postboxes.add_postbox_toggles('<?php echo $this->slug; ?>');
			});
			//]]>
			</script>
			<?php
		}

		/**
		 * Validates an option.
		 *
		 * @since 0.5.0
		 * @param WPOD\Components\Field $field field object to validate the option for
		 * @param mixed $option the option value to validate
		 * @param mixed $option_old the previous option value
		 * @return array an array containing the validated value, a variable possibly containing a WP_Error object and a boolean value whether the option value has changed
		 */
		protected function validate_option( $field, $option, $option_old ) {
			$option = $field->validate_option( $option );
			$error = false;
			$changed = false;

			if ( is_wp_error( $option ) ) {
				$error = $option;
				$option = $option_old;
			} elseif ( $option != $option_old ) {
				/**
				 * This action can be used to perform additional steps when the option for a specific field of this tab has been updated.
				 *
				 * @since 0.5.0
				 * @param mixed the updated option value
				 * @param mixed the previous option value
				 */
				do_action( 'wpod_update_option_' . $this->slug . '_' . $field->slug, $option, $option_old );
				$changed = true;
			}

			return array( $option, $error, $changed );
		}

		/**
		 * Adds settings errors and/or updated messages for the current tab.
		 *
		 * @since 0.5.0
		 * @param array $errors an array (possibly) containing validation errors as $field_slug => $wp_error
		 */
		protected function add_settings_message( $errors ) {
			$status_text = __( 'Settings successfully saved.', 'options-definitely' );

			if ( count( $errors ) > 0 ) {
				$error_text = __( 'Some errors occurred while trying to save the following settings:', 'options-definitely' );
				foreach ( $errors as $field_slug => $error ) {
					$error_text .= '<br/><em>' . $field_slug . '</em>: ' . $error->get_error_message();
				}

				add_settings_error( $this->slug, $this->slug . '-error', $error_text, 'error' );

				$status_text = __( 'Other settings were successfully saved.', 'options-definitely' );
			}

			add_settings_error( $this->slug, $this->slug . '-updated', $status_text, 'updated' );
		}
	}
}
