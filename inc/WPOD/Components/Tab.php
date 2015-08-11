<?php
/**
 * @package WPOD
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPOD\Components;

use WPOD\App as App;
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
	class Tab extends \WPDLib\Components\Base {

		/**
		 * Registers the setting for this tab.
		 *
		 * @since 0.5.0
		 */
		public function register() {
			if ( count( $this->children ) > 0 ) {
				register_setting( $this->slug, $this->slug, array( $this, 'validate_options' ) );
			}
		}

		/**
		 * Renders the tab.
		 *
		 * It displays the tab description (if available) and renders the form tag.
		 * Inside the form tag, it iterates through all the sections belonging to this tab and calls each one's `render()` function.
		 *
		 * If the tab is draggable (i.e. uses meta boxes), the meta boxes are handled in here as well.
		 *
		 * @since 0.5.0
		 */
		public function render() {
			$parent_screen = $this->get_parent();

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

			if ( count( $this->children ) > 0 ) {
				$form_atts = array(
					'id'			=> $this->slug,
					'action'		=> admin_url( 'options.php' ),
					'method'		=> 'post',
					'novalidate'	=> true,
				);

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
					foreach ( $this->children as $section ) {
						$section->render( false );
					}
				}

				settings_fields( $this->slug );

				submit_button();

				echo '</form>';
			} elseif ( $this->args['callback'] && is_callable( $this->args['callback'] ) ) {
				call_user_func( $this->args['callback'] );
			} else {
				App::doing_it_wrong( __METHOD__, sprintf( __( 'There are no sections to display for tab %s. Either add some or provide a valid callback function instead.', 'wpod' ), $this->slug ), '0.5.0' );
			}

			if ( 'draggable' == $this->args['mode'] ) {
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

			foreach ( $this->children as $section ) {
				foreach ( $section->children as $field ) {
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

					$option = $field->validate_option( $option );
					if ( is_wp_error( $option ) ) {
						$errors[ $field->slug ] = $option;
						$option = $option_old;
					}

					$options_validated[ $field->slug ] = $option;

					if ( $option != $option_old ) {
						do_action( 'wpod_update_option_' . $this->slug . '_' . $field->slug, $option, $option_old );
						$changes = true;
					}
				}
			}

			if ( $changes ) {
				do_action( 'wpod_update_options_' . $this->slug, $options_validated, $options_old );
			}

			$options_validated = apply_filters( 'wpod_validated_options', $options_validated );

			$status_text = __( 'Settings successfully saved.', 'wpod' );

			if ( count( $errors ) > 0 ) {
				$error_text = __( 'Some errors occurred while trying to save the following settings:', 'wpod' );
				foreach ( $errors as $field_slug => $error ) {
					$error_text .= '<br/><em>' . $field_slug . '</em>: ' . $error->get_error_message();
				}

				add_settings_error( $this->slug, $this->slug . '-error', $error_text, 'error' );

				$status_text = __( 'Other settings were successfully saved.', 'wpod' );
			}

			add_settings_error( $this->slug, $this->slug . '-updated', $status_text, 'updated' );

			return $options_validated;
		}

		public function enqueue_assets() {
			if ( 'draggable' == $this->args['mode'] ) {
				wp_enqueue_script( 'common' );
				wp_enqueue_script( 'wp-lists' );
				wp_enqueue_script( 'postbox' );
			}

			$_fields = array();
			foreach ( $this->children as $section ) {
				foreach ( $section->children as $field ) {
					$_fields[] = $field->_field;
				}
			}

			FieldManager::enqueue_assets( $_fields );
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
				'title'			=> __( 'Tab title', 'wpod' ),
				'description'	=> '',
				'capability'	=> 'manage_options',
				'mode'			=> 'default',
				'callback'		=> false, //only used if no sections are attached to this tab
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
	}
}
