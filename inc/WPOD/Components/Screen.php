<?php
/**
 * @package WPOD
 * @version 0.6.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPOD\Components;

use WPOD\App as App;
use WPOD\Admin as Admin;
use WPOD\Utility as Utility;
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
		 * Class constructor.
		 *
		 * @since 0.5.0
		 * @param string $slug the screen slug
		 * @param array $args array of screen properties
		 */
		public function __construct( $slug, $args ) {
			parent::__construct( $slug, $args );
			$this->validate_filter = 'wpod_screen_validated';
		}

		/**
		 * Adds the screen to the WordPress admin menu.
		 *
		 * By adding the screen to the menu, a page hook is assigned to it.
		 * This function is called by the WPDLib\Components\Menu class.
		 * The function returns the menu label this screen should have. This is then processed by the calling class.
		 *
		 * @since 0.5.0
		 * @see WPDLib\Components\Menu::add_menu_page()
		 * @param array $args an array with keys 'mode' (either 'menu' or 'submenu'), 'menu_label', 'menu_icon' and 'menu_position'
		 * @return string the menu label that this screen should have
		 */
		public function add_to_menu( $args ) {
			if ( 'menu' === $args['mode'] ) {
				$this->page_hook = add_menu_page( $this->args['title'], $args['menu_label'], $this->args['capability'], $this->slug, array( $this, 'render' ), $args['menu_icon'], $args['menu_position'] );
			} else {
				$this->page_hook = add_submenu_page( $args['menu_slug'], $this->args['title'], $this->args['label'], $this->args['capability'], $this->slug, array( $this, 'render' ) );
			}

			return $this->args['label'];
		}

		/**
		 * Renders the screen.
		 *
		 * It displays the title and (optionally) description of the screen.
		 * Then it displays the tab navigation (if there are multiple tabs) and the currently active tab.
		 *
		 * @since 0.5.0
		 */
		public function render() {
			$parent_menu = $this->get_parent();

			echo '<div class="wrap">';

			echo '<h1>' . $this->args['title'] . '</h1>';

			/**
			 * This action can be used to display additional content on top of this screen.
			 *
			 * @since 0.5.0
			 * @param string the slug of the current screen
			 * @param array the arguments array for the current screen
			 * @param string the slug of the current menu
			 */
			do_action( 'wpod_screen_before', $this->real_slug, $this->args, $parent_menu->slug );

			if ( ! empty( $this->args['description'] ) ) {
				echo '<p class="description">' . $this->args['description'] . '</p>';
			}

			$tabs = $this->get_children();
			$tabs = array_filter( $tabs, array( 'WPDLib\Util\Util', 'current_user_can' ) );

			if ( count( $tabs ) > 0 ) {
				$current_tab = Admin::instance()->get_current( 'tab', $this );

				if ( count( $tabs ) > 1 ) {
					$this->render_tab_navigation( $tabs, $current_tab );
				}
				$current_tab->render();
			} else {
				App::doing_it_wrong( __METHOD__, sprintf( __( 'There are no tabs to display for the screen %s. Either add some or adjust the required capabilities.', 'options-definitely' ), $this->real_slug ), '0.5.0' );
			}

			/**
			 * This action can be used to display additional content at the bottom of this screen.
			 *
			 * @since 0.5.0
			 * @param string the slug of the current screen
			 * @param array the arguments array for the current screen
			 * @param string the slug of the current menu
			 */
			do_action( 'wpod_screen_after', $this->real_slug, $this->args, $parent_menu->slug );

			echo '</div>';
		}

		/**
		 * Adds help tabs and help sidebar to the screen if they are specified.
		 *
		 * This function is called by the WPOD\Admin class.
		 *
		 * @since 0.5.0
		 */
		public function render_help() {
			Utility::render_help( get_current_screen(), $this->args['help'] );
		}

		/**
		 * Validates the arguments array.
		 *
		 * @since 0.5.0
		 * @param WPDLib\Components\Menu $parent the parent component
		 * @return bool|WPDLib\Util\Error an error object if an error occurred during validation, true if it was validated, false if it did not need to be validated
		 */
		public function validate( $parent = null ) {
			$status = parent::validate( $parent );

			if ( $status === true ) {
				$this->args = Utility::validate_position_args( $this->args );

				$this->args = Utility::validate_help_args( $this->args, 'help' );
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
				'title'			=> __( 'Screen title', 'options-definitely' ),
				'label'			=> __( 'Screen label', 'options-definitely' ),
				'description'	=> '',
				'capability'	=> 'manage_options',
				'position'		=> null,
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

		/**
		 * Renders the tab navigation.
		 *
		 * @since 0.5.0
		 * @param array $tabs array of WPOD\Components\Tab objects
		 * @param WPOD\Components\Tab $current_tab the current tab object
		 */
		protected function render_tab_navigation( $tabs, $current_tab ) {
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
	}
}
