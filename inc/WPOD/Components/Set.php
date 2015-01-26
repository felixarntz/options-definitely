<?php
/**
 * @package WPOD
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPOD\Components;

class Set extends Component_Base {
	protected $page_hook = '';
	protected $help = array();

	public function add_to_menu() {
		$group = \WPOD\Framework::instance()->query( array(
			'slug'			=> $this->parent,
			'type'			=> 'group',
		), true );

		$function_name = 'add_' . $group->slug . '_page';

		if ( function_exists( $function_name ) && ! in_array( $group->slug, array( 'menu', 'submenu' ) ) ) {
			$this->page_hook = call_user_func( $function_name, $this->args['title'], $this->args['label'], $this->args['capability'], $this->slug, array( $this, 'render' ) );
		} else {
			if ( false === $group->added ) {
				$this->page_hook = add_menu_page( $this->args['title'], $group->label, $this->args['capability'], $this->slug, array( $this, 'render' ), $group->icon, $group->position );

				\WPOD\Framework::instance()->update( $group->slug, 'group', array(
					'added'			=> true,
					'subslug'		=> $this->slug,
					'sublabel'		=> $this->args['label'],
				) );
			} else {
				$this->page_hook = add_submenu_page( $group->subslug, $this->args['title'], $this->args['label'], $this->args['capability'], $this->slug, array( $this, 'render' ) );

				if ( $group->sublabel !== true ) {
					global $submenu;

					if ( isset( $submenu[ $group->subslug ] ) ) {
						$submenu[ $group->subslug ][0][0] = $group->sublabel;

						\WPOD\Framework::instance()->update( $group->slug, 'group', array(
							'sublabel'		=> true,
						) );
					}
				}
			}
		}

		return $this->page_hook;
	}

	public function render() {
		echo '<div class="wrap">';

		echo '<h1>' . $this->args['title'] . '</h1>';

		if ( ! empty( $this->args['description'] ) ) {
			echo '<p class="description">' . $this->args['description'] . '</p>';
		}

		$members = \WPOD\Framework::instance()->query( array(
			'type'				=> 'member',
			'parent_slug'		=> $this->slug,
			'parent_type'		=> 'set',
		) );
		$members = array_filter( $members, 'wpod_current_user_can' );

		if ( count( $members ) > 0 ) {
			$current_member = \WPOD\Admin::instance()->get_current( 'member', $this );

			settings_errors( $current_member->slug );

			if ( count( $members ) > 1 ) {
				$current_url = \WPOD\Admin::instance()->get_current_url();

				echo '<h2 class="nav-tab-wrapper">';

				foreach ( $members as $member ) {
					$class = 'nav-tab';

					if ( $member->slug == $current_member->slug ) {
						$class .= ' nav-tab-active';
					}

					echo '<a class="' . $class . '" href="' . add_query_arg( 'tab', $member->slug, $current_url ) . '">' . $member->title . '</a>';
				}

				echo '</h2>';
			}
			$current_member->render();
		} else {
			wpod_doing_it_wrong( __METHOD__, sprintf( __( 'There are no members to display for the set %s. Either add some or adjust the required capabilities.', 'wpod' ), $this->slug ), '1.0.0' );
		}

		echo '</div>';
	}

	public function render_help() {
		$screen = get_current_screen();

		foreach ( $this->args['help']['tabs'] as $slug => $tab ) {
			$args = array_merge( array( 'id' => $slug ), $tab );

			$screen->add_help_tab( $args );
		}

		if ( ! empty( $this->args['help']['sidebar'] ) ) {
			$screen->set_help_sidebar( $this->args['help']['sidebar'] );
		}
	}

	public function validate() {
		parent::validate();

		if( ! is_array( $this->args['help'] ) ) {
			$this->args['help'] = array();
		}

		if ( ! isset( $this->args['help']['tabs'] ) || ! is_array( $this->args['help']['tabs'] ) ) {
			$this->args['help']['tabs'] = array();
		}

		if ( ! isset( $this->args['help']['sidebar'] ) ) {
			$this->args['help']['sidebar'] = '';
		}

		foreach ( $this->args['help']['tabs'] as $slug => &$tab ) {
			$tab = wp_parse_args( $tab, array(
				'title'			=> __( 'Help tab title', 'wpod' ),
				'content'		=> '',
				'callback'		=> false,
			) );
		}
	}

	protected function get_defaults() {
		$defaults = array(
			'title'			=> __( 'Set title', 'wpod' ),
			'label'			=> __( 'Set label', 'wpod' ),
			'description'	=> '',
			'capability'	=> 'manage_options',
			'help'			=> array(
				'tabs'			=> array(),
				'sidebar'		=> '',
			),
		);

		return apply_filters( 'wpod_set_defaults', $defaults );
	}
}
