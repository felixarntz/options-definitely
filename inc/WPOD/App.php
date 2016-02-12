<?php
/**
 * @package WPOD
 * @version 0.6.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPOD;

use WPOD\Admin as Admin;
use WPOD\Components\Screen as Screen;
use WPOD\Components\Tab as Tab;
use WPOD\Components\Section as Section;
use WPOD\Components\Field as Field;
use WPDLib\Components\Manager as ComponentManager;
use WPDLib\Components\Menu as Menu;
use WPDLib\FieldTypes\Manager as FieldManager;
use LaL_WP_Plugin as Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPOD\App' ) ) {
	/**
	 * This class initializes the plugin.
	 *
	 * It also triggers the action and filter to hook into and contains all API functions of the plugin.
	 *
	 * @since 0.5.0
	 */
	class App extends Plugin {

		/**
		 * @since 0.5.0
		 * @var array Holds the plugin data.
		 */
		protected static $_args = array();

		/**
		 * Class constructor.
		 *
		 * This is protected on purpose since it is called by the parent class' singleton.
		 *
		 * @internal
		 * @since 0.5.0
		 */
		protected function __construct( $args ) {
			parent::__construct( $args );
		}

		/**
		 * The run() method.
		 *
		 * This will initialize the plugin on the 'after_setup_theme' action.
		 *
		 * @internal
		 * @since 0.5.0
		 * @param array $args array of class arguments (passed by the plugin utility class)
		 */
		protected function run() {
			FieldManager::init();

			if ( is_admin() ) {
				Admin::instance();
			}

			// use after_setup_theme action so it is initialized as soon as possible, but also so that both plugins and themes can use the action
			add_action( 'after_setup_theme', array( $this, 'init' ), 1 );

			add_filter( 'wpdlib_menu_validated', array( $this, 'menu_validated' ), 10, 2 );
			add_filter( 'wpod_screen_validated', array( $this, 'screen_validated' ), 10, 2 );
			add_filter( 'wpod_tab_validated', array( $this, 'tab_validated' ), 10, 2 );
			add_filter( 'wpod_section_validated', array( $this, 'section_validated' ), 10, 2 );
		}

		/**
		 * Sets the current scope.
		 *
		 * The scope is an internal identifier. When adding a component, it will be added to the currently active scope.
		 * Therefore every plugin or theme should define its own unique scope to prevent conflicts.
		 *
		 * @since 0.5.0
		 * @param string $scope the current scope to set
		 */
		public function set_scope( $scope ) {
			ComponentManager::set_scope( $scope );
		}

		/**
		 * Adds a toplevel component.
		 *
		 * This function should be utilized when using the plugin manually.
		 * Every component has an `add()` method to add subcomponents to it, however if you want to add toplevel components, use this function.
		 *
		 * @since 0.5.0
		 * @param WPDLib\Components\Base $component the component object to add
		 * @return WPDLib\Components\Base|WP_Error either the component added or a WP_Error object if an error occurred
		 */
		public function add( $component ) {
			return ComponentManager::add( $component );
		}

		/**
		 * Takes an array of hierarchically nested components and adds them.
		 *
		 * This function is the general function to add an array of components.
		 * You should call it from your plugin or theme within the 'wpod' action.
		 *
		 * @since 0.5.0
		 * @param array $components the components to add
		 * @param string $scope the scope to add the components to
		 */
		public function add_components( $components, $scope = '' ) {
			$this->set_scope( $scope );

			if ( is_array( $components ) ) {
				$this->add_menus( $components );
			}
		}

		/**
		 * Initializes the plugin framework.
		 *
		 * This function adds all components to the plugin. It is executed on the 'after_setup_theme' hook with priority 1.
		 * The action 'wpod' should be used to add all the components.
		 *
		 * @internal
		 * @see WPOD\App::add_components()
		 * @see WPOD\App::add()
		 * @since 0.5.0
		 */
		public function init() {
			if ( ! did_action( 'wpod' ) ) {
				/**
				 * This filter can be used to alter the component hierarchy of the plugin.
				 * It must only be used to add more components to the hierarchy, never to change or remove something existing.
				 *
				 * @since 0.5.0
				 * @param array the nested array of component class names
				 */
				ComponentManager::register_hierarchy( apply_filters( 'wpod_class_hierarchy', array(
					'WPDLib\Components\Menu'		=> array(
						'WPOD\Components\Screen'		=> array(
							'WPOD\Components\Tab'			=> array(
								'WPOD\Components\Section'		=> array(
									'WPOD\Components\Field'			=> array(),
								),
							),
						),
					),
				) ) );

				/**
				 * The main API action of the plugin.
				 *
				 * Every developer must hook into this action to register components.
				 *
				 * @since 0.5.0
				 * @param WPOD\App instance of the main plugin class
				 */
				do_action( 'wpod', $this );
			} else {
				self::doing_it_wrong( __METHOD__, __( 'This function should never be called manually.', 'options-definitely' ), '0.5.0' );
			}
		}

		/**
		 * Callback function run after a menu has been validated.
		 *
		 * @internal
		 * @since 0.5.0
		 * @param array $args the menu arguments
		 * @param WPDLib\Components\Menu $menu the current menu object
		 * @return array the adjusted menu arguments
		 */
		public function menu_validated( $args, $menu ) {
			if ( isset( $args['screens'] ) ) {
				unset( $args['screens'] );
			}
			return $args;
		}

		/**
		 * Callback function run after a screen has been validated.
		 *
		 * @internal
		 * @since 0.5.0
		 * @param array $args the screen arguments
		 * @param WPOD\Components\Screen $menu the current screen object
		 * @return array the adjusted screen arguments
		 */
		public function screen_validated( $args, $screen ) {
			if ( isset( $args['tabs'] ) ) {
				unset( $args['tabs'] );
			}
			return $args;
		}

		/**
		 * Callback function run after a tab has been validated.
		 *
		 * @internal
		 * @since 0.5.0
		 * @param array $args the tab arguments
		 * @param WPOD\Components\Tab $menu the current tab object
		 * @return array the adjusted tab arguments
		 */
		public function tab_validated( $args, $tab ) {
			if ( isset( $args['sections'] ) ) {
				unset( $args['sections'] );
			}
			return $args;
		}

		/**
		 * Callback function run after a section has been validated.
		 *
		 * @internal
		 * @since 0.5.0
		 * @param array $args the section arguments
		 * @param WPOD\Components\Section $menu the current section object
		 * @return array the adjusted section arguments
		 */
		public function section_validated( $args, $section ) {
			if ( isset( $args['fields'] ) ) {
				unset( $args['fields'] );
			}
			return $args;
		}

		/**
		 * Adds menus and their subcomponents.
		 *
		 * @internal
		 * @since 0.5.0
		 * @param array $menus the menus to add as $menu_slug => $menu_args
		 */
		protected function add_menus( $menus ) {
			foreach ( $menus as $menu_slug => $menu_args ) {
				$menu = $this->add( new Menu( $menu_slug, $menu_args ) );
				if ( is_wp_error( $menu ) ) {
					self::doing_it_wrong( __METHOD__, $menu->get_error_message(), '0.5.0' );
				} elseif ( isset( $menu_args['screens'] ) && is_array( $menu_args['screens'] ) ) {
					$this->add_screens( $menu_args['screens'], $menu );
				}
			}
		}

		/**
		 * Adds screens and their subcomponents.
		 *
		 * @internal
		 * @since 0.5.0
		 * @param array $screens the screens to add as $screen_slug => $screen_args
		 * @param WPDLib\Components\Menu $menu the menu to add the screens to
		 */
		protected function add_screens( $screens, $menu ) {
			foreach ( $screens as $screen_slug => $screen_args ) {
				$screen = $menu->add( new Screen( $screen_slug, $screen_args ) );
				if ( is_wp_error( $screen ) ) {
					self::doing_it_wrong( __METHOD__, $screen->get_error_message(), '0.5.0' );
				} elseif ( isset( $screen_args['tabs'] ) && is_array( $screen_args['tabs'] ) ) {
					$this->add_tabs( $screen_args['tabs'], $screen );
				}
			}
		}

		/**
		 * Adds tabs and their subcomponents.
		 *
		 * @internal
		 * @since 0.5.0
		 * @param array $tabs the tabs to add as $tab_slug => $tab_args
		 * @param WPOD\Components\Screen $screen the screen to add the tabs to
		 */
		protected function add_tabs( $tabs, $screen ) {
			foreach ( $tabs as $tab_slug => $tab_args ) {
				$tab = $screen->add( new Tab( $tab_slug, $tab_args ) );
				if ( is_wp_error( $tab ) ) {
					self::doing_it_wrong( __METHOD__, $tab->get_error_message(), '0.5.0' );
				} elseif ( isset( $tab_args['sections'] ) && is_array( $tab_args['sections'] ) ) {
					$this->add_sections( $tab_args['sections'], $tab );
				}
			}
		}

		/**
		 * Adds sections and their subcomponents.
		 *
		 * @internal
		 * @since 0.5.0
		 * @param array $sections the sections to add as $section_slug => $section_args
		 * @param WPOD\Components\Tab $tab the tab to add the sections to
		 */
		protected function add_sections( $sections, $tab ) {
			foreach ( $sections as $section_slug => $section_args ) {
				$section = $tab->add( new Section( $section_slug, $section_args ) );
				if ( is_wp_error( $section ) ) {
					self::doing_it_wrong( __METHOD__, $section->get_error_message(), '0.5.0' );
				} elseif ( isset( $section_args['fields'] ) && is_array( $section_args['fields'] ) ) {
					$this->add_fields( $section_args['fields'], $section );
				}
			}
		}

		/**
		 * Adds fields and their subcomponents.
		 *
		 * @internal
		 * @since 0.5.0
		 * @param array $fields the fields to add as $field_slug => $field_args
		 * @param WPOD\Components\Section $section the section to add the fields to
		 */
		protected function add_fields( $fields, $section ) {
			foreach ( $fields as $field_slug => $field_args ) {
				$field = $section->add( new Field( $field_slug, $field_args ) );
				if ( is_wp_error( $field ) ) {
					self::doing_it_wrong( __METHOD__, $field->get_error_message(), '0.5.0' );
				}
			}
		}

		/**
		 * Adds a link to the framework guide to the plugins table.
		 *
		 * @internal
		 * @since 0.6.1
		 * @param array $links the original links
		 * @return array the modified links
		 */
		public static function filter_plugin_links( $links = array() ) {
			$custom_links = array(
				'<a href="' . 'https://github.com/felixarntz/options-definitely/wiki' . '">' . __( 'Guide', 'options-definitely' ) . '</a>',
			);

			return array_merge( $custom_links, $links );
		}

		/**
		 * Adds a link to the framework guide to the network plugins table.
		 *
		 * @internal
		 * @since 0.6.1
		 * @param array $links the original links
		 * @return array the modified links
		 */
		public static function filter_network_plugin_links( $links = array() ) {
			return self::filter_plugin_links( $links );
		}

		/**
		 * Renders a plugin information message.
		 *
		 * @internal
		 * @since 0.6.1
		 * @param string $status either 'activated' or 'active'
		 * @param string $context either 'site' or 'network'
		 */
		public static function render_status_message( $status, $context = 'site' ) {
			?>
			<p>
				<?php if ( 'activated' === $status ) : ?>
					<?php printf( __( 'You have just activated %s.', 'options-definitely' ), '<strong>' . self::get_info( 'name' ) . '</strong>' ); ?>
				<?php elseif ( 'network' === $context ) : ?>
					<?php printf( __( 'You are running the plugin %s on your network.', 'options-definitely' ), '<strong>' . self::get_info( 'name' ) . '</strong>' ); ?>
				<?php else : ?>
					<?php printf( __( 'You are running the plugin %s on your site.', 'options-definitely' ), '<strong>' . self::get_info( 'name' ) . '</strong>' ); ?>
				<?php endif; ?>
				<?php _e( 'This plugin is a framework that developers can leverage to quickly add settings pages with tabs, meta box sections and fields.', 'options-definitely' ); ?>
			</p>
			<p>
				<?php printf( __( 'For a guide on how to use the framework please read the <a href="%s">Wiki</a>.', 'options-definitely' ), 'https://github.com/felixarntz/options-definitely/wiki' ); ?>
			</p>
			<?php
		}

		/**
		 * Renders a network plugin information message.
		 *
		 * @internal
		 * @since 0.6.1
		 * @param string $status either 'activated' or 'active'
		 * @param string $context either 'site' or 'network'
		 */
		public static function render_network_status_message( $status, $context = 'network' ) {
			self::render_status_message( $status, $context );
		}
	}
}
