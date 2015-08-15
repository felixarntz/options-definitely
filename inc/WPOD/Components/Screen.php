<?php
/**
 * @package WPOD
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPOD\Components;

use WPOD\App as App;
use WPOD\Admin as Admin;
use WPDLib\Components\Base as Base;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPOD\Components\Screen' ) ) {
	/**
	 * Class for a screen component.
	 *
	 * A screen denotes an options screen in the WordPress admin.
	 *
	 * @internal
	 * @since 0.5.0
	 */
	class Screen extends Base {

		/**
		 * @since 0.5.0
		 * @var string Holds the page hook for this screen (used in the WordPress action `'load-' . $page_hook`).
		 */
		protected $page_hook = '';

		/**
		 * Adds the screen to the WordPress admin menu.
		 *
		 * If the parent menu has an empty slug, the menu won't be added. The screen will be added though, without showing it in any menu.
		 *
		 * If the parent menu has not been added yet, the screen will be added as the top level item of this menu.
		 * If it has been added, the screen will be added as a submenu item to this menu.
		 *
		 * By adding the screen to the menu, a page hook is assigned to it.
		 *
		 * This function is called by the WPOD\Admin class.
		 *
		 * @since 0.5.0
		 * @see WPOD\Admin::create_admin_menu()
		 * @return string the page hook of this screen
		 */
		public function add_to_menu() {
			$menu = $this->get_parent();

			if ( empty( $menu->slug ) ) {
				$this->page_hook = add_submenu_page( null, $this->args['title'], $this->args['label'], $this->args['capability'], $this->slug, array( $this, 'render' ) );
			} else {
				if ( false === $menu->is_already_added( $this->slug ) ) {
					$this->page_hook = add_menu_page( $this->args['title'], $menu->label, $this->args['capability'], $this->slug, array( $this, 'render' ), $menu->icon, $menu->position );
					$menu->added = true;
					$menu->subslug = $this->slug;
					$menu->sublabel = $this->args['label'];
				} else {
					if ( false === $menu->sublabel ) {
						return false;
					}

					if ( preg_match( '/^add_[a-z]+_page$/', $menu->subslug ) && function_exists( $menu->subslug ) ) {
						$this->page_hook = call_user_func( $menu->subslug, $this->args['title'], $this->args['label'], $this->args['capability'], $this->slug, array( $this, 'render' ) );
					} else {
						$this->page_hook = add_submenu_page( $menu->subslug, $this->args['title'], $this->args['label'], $this->args['capability'], $this->slug, array( $this, 'render' ) );
					}

					if ( true !== $menu->sublabel ) {
						global $submenu;

						if ( isset( $submenu[ $menu->subslug ] ) ) {
							$submenu[ $menu->subslug ][0][0] = $menu->sublabel;
							$menu->sublabel = true;
						}
					}
				}
			}

			return $this->page_hook;
		}

		/**
		 * Renders the screen.
		 *
		 * It displays the title and (optionally) description of the screen.
		 * Then it iterates through the tabs that belong to the screen and calls each one's `render()` function.
		 *
		 * @since 0.5.0
		 */
		public function render() {
			$parent_menu = $this->get_parent();

			echo '<div class="wrap">';

			echo '<h1>' . $this->args['title'] . '</h1>';

			do_action( 'wpod_screen_before', $this->real_slug, $this->args, $parent_menu->slug );

			if ( ! empty( $this->args['description'] ) ) {
				echo '<p class="description">' . $this->args['description'] . '</p>';
			}

			$tabs = $this->get_children();
			$tabs = array_filter( $tabs, 'wpod_current_user_can' );

			if ( count( $tabs ) > 0 ) {
				$current_tab = Admin::instance()->get_current( 'tab', $this );

				if ( count( $tabs ) > 1 ) {
					$current_url = Admin::instance()->get_current_url();

					echo '<h2 class="nav-tab-wrapper">';

					foreach ( $tabs as $tab ) {
						$class = 'nav-tab';

						if ( $tab->slug == $current_tab->slug ) {
							$class .= ' nav-tab-active';
						}

						echo '<a class="' . $class . '" href="' . add_query_arg( 'tab', $tab->slug, $current_url ) . '">' . $tab->title . '</a>';
					}

					echo '</h2>';
				}
				$current_tab->render();
			} else {
				App::doing_it_wrong( __METHOD__, sprintf( __( 'There are no tabs to display for the screen %s. Either add some or adjust the required capabilities.', 'wpod' ), $this->real_slug ), '0.5.0' );
			}

			do_action( 'wpod_screen_after', $this->real_slug, $this->args, $parent_menu->slug );

			echo '</div>';
		}

		/**
		 * Adds help tabs and help sidebar to the screen if they are specified.
		 *
		 * This function is called by the WPOD\Admin class.
		 *
		 * @since 0.5.0
		 * @see WPOD\Admin::create_admin_menu()
		 */
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

		/**
		 * Validates the arguments array.
		 *
		 * @since 0.5.0
		 */
		public function validate( $parent = null ) {
			$status = parent::validate( $parent );

			if ( $status === true ) {
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

			return $status;
		}

		/**
		 * Returns the keys of the arguments array and their default values.
		 *
		 * Read the plugin guide for more information about the screen arguments.
		 *
		 * @since 0.5.0
		 * @return array
		 */
		protected function get_defaults() {
			$defaults = array(
				'title'			=> __( 'Screen title', 'wpod' ),
				'label'			=> __( 'Screen label', 'wpod' ),
				'description'	=> '',
				'capability'	=> 'manage_options',
				'help'			=> array(
					'tabs'			=> array(),
					'sidebar'		=> '',
				),
			);

			/**
			 * This filter can be used by the developer to modify the default values for each screen component.
			 *
			 * @since 0.5.0
			 * @param array the associative array of default values
			 */
			return apply_filters( 'wpod_screen_defaults', $defaults );
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
