<?php
/**
 * @package WPOD
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPOD\Components;

class Tab extends Component_Base {
	public function register() {
		$sections = \WPOD\Framework::instance()->query( array(
			'type'			=> 'section',
			'parent_slug'	=> $this->slug,
			'parent_type'	=> 'tab',
		) );

		if ( count( $sections ) > 0 ) {
			register_setting( $this->slug, $this->slug, array( $this, 'validate_options' ) );
		}
	}

	public function render() {
		if ( ! empty( $this->args['description'] ) ) {
			echo '<p class="description">' . $this->args['description'] . '</p>';
		}

		$sections = \WPOD\Framework::instance()->query( array(
			'type'			=> 'section',
			'parent_slug'	=> $this->slug,
			'parent_type'	=> 'tab',
		) );

		if ( count( $sections ) > 0 ) {
			$form_atts = array(
				'id'			=> $this->slug,
				'action'		=> admin_url( 'options.php' ),
				'method'		=> 'post',
				'novalidate'	=> true,
			);

			$form_atts = apply_filters( 'wpod_form_atts', $form_atts, $this );

			echo '<form' . wpod_make_html_attributes( $form_atts, false, false ) . '>';

			if ( 'draggable' == $this->args['mode'] ) {
				wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
				wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );

				echo '<div class="metabox-holder">';
				echo '<div class="postbox-container">';

				do_meta_boxes( $this->slug, 'normal', null );

				echo '</div>';
				echo '</div>';
			} else {
				foreach ( $sections as $section ) {
					$section->render( false );
				}
			}

			settings_fields( $this->slug );

			submit_button();

			echo '</form>';
		} elseif ( $this->args['callback'] && is_callable( $this->args['callback'] ) ) {
			call_user_func( $this->args['callback'] );
		} else {
			wpod_doing_it_wrong( __METHOD__, sprintf( __( 'There are no sections to display for tab %s. Either add some or provide a valid callback function instead.', 'wpod' ), $this->slug ), '1.0.0' );
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
	}

	public function validate_options( $options ) {
		$options_validated = array();

		$options_old = get_option( $this->slug );

		$errors = array();

		$fields = \WPOD\Framework::instance()->query( array(
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

			add_settings_error( $this->slug, $this->slug . '-error', $error_text . '<br/>' . implode( '<br/>', $errors ), 'error' );

			$status_text = __( 'All other settings not mentioned above were saved successfully.', 'wpod' );
		}

		add_settings_error( $this->slug, $this->slug . '-updated', $status_text, 'updated' );

		do_action( 'wpod_options_validated', $this, $options_validated );

		return $options_validated;
	}

	public function update_option_defaults() {
		$options = get_option( $this->slug );

		if ( ! is_array( $options ) ) {
			$options = array();
		}

		$fields = WPOD\Framework::instance()->query( array(
			'type'			=> 'field',
			'parent_slug'	=> $this->slug,
			'parent_type'	=> 'tab',
		) );

		foreach ( $fields as $field ) {
			if ( ! isset( $options[ $field->slug ] ) ) {
				$options[ $field->slug ] = $field->default;
			}
		}

		update_option( $this->slug, $options );
	}

	protected function get_defaults() {
		$defaults = array(
			'title'			=> __( 'Tab title', 'wpod' ),
			'description'	=> '',
			'capability'	=> 'manage_options',
			'mode'			=> 'default',
			'callback'		=> false, //only used if no sections are attached to this tab
		);

		return apply_filters( 'wpod_tab_defaults', $defaults );
	}
}
