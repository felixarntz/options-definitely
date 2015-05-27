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
 * Class for a tab component.
 *
 * A tab denotes a tab inside an options page in the WordPress admin.
 * The slug of a tab, at the same time, is the slug of the option (or rather array of options) stored in WordPress.
 *
 * @internal
 * @since 0.5.0
 */
class Tab extends ComponentBase {

	/**
	 * Class constructor.
	 *
	 * In addition to calling the parent constructor, the tab also adds an action to update the default values for an option.
	 * Those actions are not run anywhere in the plugin, but can be triggered by any other plugin or theme, for example to initialize the options during the installation.
	 * The actions are:
	 * * 'wpod_update_TABSLUG_defaults' (will update option defaults for TABSLUG; when running the action, replace TABSLUG by the slug of the tab)
	 * * 'wpod_update_defaults' (will update option defaults for all options added by the plugin; usage is not recommended)
	 *
	 * @since 0.5.0
	 * @param string $slug internal slug of this component
	 * @param string $real_slug real slug of this component
	 * @param array $args array of arguments
	 * @param string $parent slug of this component's parent component or an empty string
	 */
	public function __construct( $slug, $real_slug, $args, $parent = '' ) {
		parent::__construct( $slug, $real_slug, $args, $parent );

		add_action( 'wpod_update_' . $real_slug . '_defaults', array( $this, 'update_option_defaults' ) );

		add_action( 'wpod_update_defaults', array( $this, 'update_option_defaults' ) );
	}

	/**
	 * Registers the setting for this tab.
	 *
	 * @since 0.5.0
	 */
	public function register() {
		$sections = \WPOD\App::instance()->query( array(
			'type'			=> 'section',
			'parent_slug'	=> $this->slug,
			'parent_type'	=> 'tab',
		) );

		if ( count( $sections ) > 0 ) {
			register_setting( $this->real_slug, $this->real_slug, array( $this, 'validate_options' ) );
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
		/**
		 * This action can be used to display additional content on top of this tab.
		 *
		 * @since 0.5.0
		 * @param string the slug of the current tab
		 * @param array the arguments array for the current tab
		 * @param string the slug of the current page
		 */
		do_action( 'wpod_tab_before', $this->real_slug, $this->args, $this->parent );

		if ( ! empty( $this->args['description'] ) ) {
			echo '<p class="description">' . $this->args['description'] . '</p>';
		}

		$sections = \WPOD\App::instance()->query( array(
			'type'			=> 'section',
			'parent_slug'	=> $this->slug,
			'parent_type'	=> 'tab',
		) );

		if ( count( $sections ) > 0 ) {
			$form_atts = array(
				'id'			=> $this->real_slug,
				'action'		=> admin_url( 'options.php' ),
				'method'		=> 'post',
				'novalidate'	=> true,
			);

			$form_atts = apply_filters( 'wpod_form_atts', $form_atts, $this );

			echo '<form' . \WPOD\Util::make_html_attributes( $form_atts, false, false ) . '>';

			if ( 'draggable' == $this->args['mode'] ) {
				wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
				wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );

				echo '<div class="metabox-holder">';
				echo '<div class="postbox-container">';

				do_meta_boxes( $this->real_slug, 'normal', null );

				echo '</div>';
				echo '</div>';
			} else {
				foreach ( $sections as $section ) {
					$section->render( false );
				}
			}

			settings_fields( $this->real_slug );

			submit_button();

			echo '</form>';
		} elseif ( $this->args['callback'] && is_callable( $this->args['callback'] ) ) {
			call_user_func( $this->args['callback'] );
		} else {
			\WPOD\App::doing_it_wrong( __METHOD__, sprintf( __( 'There are no sections to display for tab %s. Either add some or provide a valid callback function instead.', 'wpod' ), $this->real_slug ), '0.5.0' );
		}

		if ( 'draggable' == $this->args['mode'] ) {
			?>
			<script type="text/javascript">
			//<![CDATA[
			jQuery(document).ready( function ($) {
			  // close postboxes that should be closed
			  $('.if-js-closed').removeClass('if-js-closed').addClass('closed');
			  // postboxes setup
			  postboxes.add_postbox_toggles('<?php echo $this->real_slug; ?>');
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
		 * @param string the slug of the current page
		 */
		do_action( 'wpod_tab_after', $this->real_slug, $this->args, $this->parent );
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

		$options_old = get_option( $this->real_slug, array() );

		$errors = array();

		$fields = \WPOD\App::instance()->query( array(
			'type'			=> 'field',
			'parent_slug'	=> $this->slug,
			'parent_type'	=> 'tab',
		) );

		foreach ( $fields as $field ) {
			$option = null;
			$option_old = $field->default;

			if ( isset( $options[ $field->slug ] ) ) {
				$option = $options[ $field->slug ];
			}

			if ( isset( $options_old[ $field->slug ] ) ) {
				$option_old = $options_old[ $field->slug ];
			}

			list( $option_validated, $error ) = $field->validate_option( $option, $option_old );
			$options_validated[ $field->slug ] = $option_validated;

			if ( ! empty( $error ) ) {
				$errors[] = $error;
			}
		}

		$status_text = __( 'Settings successfully saved.', 'wpod' );

		if ( count( $errors ) > 0 ) {
			$error_text = __( 'Some errors occured while trying to save the following settings:', 'wpod' );

			add_settings_error( $this->real_slug, $this->real_slug . '-error', $error_text . '<br/>' . implode( '<br/>', $errors ), 'error' );

			$status_text = __( 'All other settings not mentioned above were saved successfully.', 'wpod' );
		}

		add_settings_error( $this->real_slug, $this->real_slug . '-updated', $status_text, 'updated' );

		/**
		 * This action can be used to perform specific actions whenever a setting is validated.
		 *
		 * @since 0.5.0
		 * @param WPOD\Components\Tab the tab object for which the setting is currently being updated
		 * @param array the validated options
		 */
		do_action( 'wpod_options_validated', $this, $options_validated );

		return $options_validated;
	}

	/**
	 * Updates the saved setting for this tab with the default values (if required).
	 *
	 * This function will fill all options that are not set yet with their default value.
	 * A valid use-case may be, for example, a plugin installation where the plugin requires the options to be set.
	 * Whenever this function should be run, it is recommended to trigger it by executing the action 'wpod_update_TABSLUG_defaults' or 'wpod_update_defaults'.
	 *
	 * @since 0.5.0
	 */
	public function update_option_defaults() {
		$options = get_option( $this->real_slug );

		if ( ! is_array( $options ) ) {
			$options = array();
		}

		$fields = WPOD\App::instance()->query( array(
			'type'			=> 'field',
			'parent_slug'	=> $this->slug,
			'parent_type'	=> 'tab',
		) );

		foreach ( $fields as $field ) {
			if ( ! isset( $options[ $field->slug ] ) ) {
				$options[ $field->slug ] = $field->default;
			}
		}

		update_option( $this->real_slug, $options );
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
}
