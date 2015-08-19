<?php
/**
 * @package WPOD
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPOD;

use WPDLib\Components\Manager as ComponentManager;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPOD\Admin' ) ) {
	/**
	 * This class performs the necessary actions in the WordPress admin.
	 *
	 * This includes both registering and displaying options.
	 *
	 * @internal
	 * @since 0.5.0
	 */
	class Admin {

		/**
		 * @since 0.5.0
		 * @var WPOD\Admin|null Holds the instance of this class.
		 */
		private static $instance = null;

		/**
		 * Gets the instance of this class. If it does not exist, it will be created.
		 *
		 * @since 0.5.0
		 * @return WPOD\Admin
		 */
		public static function instance() {
			if ( null == self::$instance ) {
				self::$instance = new self;
			}

			return self::$instance;
		}

		/**
		 * Class constructor.
		 *
		 * This will hook functions into the 'admin_init', 'admin_menu' and 'admin_enqueue_scripts' actions.
		 *
		 * @since 0.5.0
		 */
		private function __construct() {
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_menu', array( $this, 'register_help' ), 100 );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		}

		/**
		 * Registers the settings.
		 *
		 * For all tabs, it registers its setting which will actually be an array of settings.
		 * It also registers settings sections and settings fields.
		 *
		 * @see WPOD\Components\Tab
		 * @see WPOD\Components\Section
		 * @see WPOD\Components\Field
		 * @since 0.5.0
		 */
		public function register_settings() {
			$tabs = ComponentManager::get( '*.*.*', 'WPDLib\Components\Menu.WPOD\Components\Screen' );
			foreach ( $tabs as $tab ) {
				$tab->register();
				foreach ( $tab->get_children() as $section ) {
					$section->register( $tab );
					foreach ( $section->get_children() as $field ) {
						$field->register( $tab, $section );
					}
				}
			}
		}

		public function register_help() {
			$screens = ComponentManager::get( '*.*', 'WPDLib\Components\Menu.WPOD\Components\Screen' );
			foreach ( $screens as $screen ) {
				$page_hook = $screen->page_hook;
				if ( $page_hook ) {
					add_action( 'load-' . $page_hook, array( $screen, 'render_help' ) );
				}
			}
		}

		/**
		 * Adds screens to the WordPress admin menu.
		 *
		 * Every screen will be added to the menu it has been assigned to.
		 * Furthermore the function to add a help tab is hooked into the screen loading action.
		 *
		 * @see WPOD\Components\Screen
		 * @since 0.5.0
		 */
		public function create_admin_menu() {
			$screens = ComponentManager::get( '*.*', 'WPDLib\Components\Menu.WPOD\Components\Screen' );
			foreach ( $screens as $screen ) {
				$page_hook = $screen->add_to_menu();
				if ( $page_hook ) {
					add_action( 'load-' . $page_hook, array( $screen, 'render_help' ) );
				}
			}
		}

		/**
		 * Enqueues necessary stylesheets and scripts.
		 *
		 * All assets are only enqueued if we are on a settings screen created by the plugin.
		 * Besides adding the plugin stylesheets and scripts, this function might also enqueue
		 * the WordPress media scripts and the WordPress meta box scripts, both depending on
		 * whether they are needed on the current screen.
		 *
		 * @since 0.5.0
		 */
		public function enqueue_assets() {
			$currents = $this->get_current();

			if ( $currents ) {
				$currents['tab']->enqueue_assets();
			}
		}

		/**
		 * Gets the currently active screen and tab.
		 *
		 * The function checks the currently loaded admin screen.
		 * If it is not created by the plugin, the function will return false.
		 * Otherwise the output depends on the $type parameter:
		 * The function may return the screen object, the tab object or an array of both objects.
		 *
		 * The second parameter may be used to omit the retrieving process by specifying a screen object.
		 * In that case, only the current tab as part of this screen will be looked for.
		 *
		 * @since 0.5.0
		 * @param string $type the type to get the current component for; must be either 'screen', 'tab' or an empty string to get an array of both
		 * @param WPOD\Components\Screen|null $screen a screen object to override the retrieving process or null
		 * @return WPOD\Components\Screen|WPOD\Components\Tab|array|false either the screen or tab object, an array of both objects or false if no plugin component is currently active
		 */
		public function get_current( $type = '', $screen = null ) {
			if ( isset( $_GET['page'] ) ) {
				if ( null === $screen ) {
					$screen = ComponentManager::get( '*.' . $_GET['page'], 'WPDLib\Components\Menu.WPOD\Components\Screen', true );
				}

				if ( null !== $screen ) {
					if ( 'screen' == $type ) {
						return $screen;
					}

					$tabs = $screen->get_children();
					$tab = null;
					if ( isset( $_GET['tab'] ) ) {
						$tab = $tabs[ $_GET['tab'] ];
						foreach ( $tabs as $_tab ) {
							if ( $_GET['tab'] == $_tab->slug ) {
								$tab = $_tab;
								break;
							}
						}
					} elseif ( count( $tabs ) > 0 ) {
						$tab = array_shift( $tabs );
					}

					if ( null !== $tab ) {
						if ( 'tab' == $type ) {
							return $tab;
						}

						return compact( 'screen', 'tab' );
					}
				}
			}

			return false;
		}

		/**
		 * Gets the current URL in the WordPress backend.
		 *
		 * @since 0.5.0
		 * @return string the current URL
		 */
		public function get_current_url() {
			global $pagenow;

			if ( isset( $_GET ) && is_array( $_GET ) ) {
				return add_query_arg( $_GET, get_admin_url( null, $pagenow ) );
			}
			return get_admin_url( null, $pagenow );
		}
	}
}
