<?php
/**
 * @package WPOD
 * @version 0.5.0
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
		 * @var boolean Holds the status whether the initialization function has been called yet.
		 */
		private $initialization_triggered = false;

		/**
		 * @since 0.5.0
		 * @var boolean Holds the status whether the app has been initialized yet.
		 */
		private $initialized = false;

		/**
		 * @since 0.5.0
		 * @var array Holds the plugin data.
		 */
		protected static $_args = array();

		/**
		 * Class constructor.
		 *
		 * @since 0.5.0
		 */
		protected function __construct( $args ) {
			parent::__construct( $args );
		}

		/**
		 * The run() method.
		 *
		 * This will initialize the plugin on the 'after_setup_theme' action.
		 * If we are currently in the WordPress admin area, the WPOD\Admin class will be instantiated.
		 *
		 * @since 0.5.0
		 */
		protected function run() {
			if ( is_admin() ) {
				Admin::instance();
			}

			// use after_setup_theme action so it is initialized as soon as possible, but also so that both plugins and themes can use the action
			add_action( 'after_setup_theme', array( $this, 'init' ), 1 );
		}

		public function set_scope( $scope ) {
			ComponentManager::set_scope( $scope );
		}

		public function add( $component ) {
			return ComponentManager::add( $component );
		}

		public function add_components( $components, $scope = '' ) {
			$this->set_scope( $scope );

			if ( is_array( $components ) ) {
				foreach ( $components as $menu_slug => $menu_args ) {
					$screens = array();
					if ( isset( $menu_args['screens'] ) ) {
						$screens = $menu_args['screens'];
						unset( $menu_args['screens'] );
					}
					$menu = ComponentManager::add( new Menu( $menu_slug, $menu_args ) );
					if ( is_wp_error( $menu ) ) {
						self::doing_it_wrong( __METHOD__, $menu->get_error_message(), '0.5.0' );
					} elseif ( is_array( $screens ) ) {
						foreach ( $screens as $screen_slug => $screen_args ) {
							$tabs = array();
							if ( isset( $screen_args['tabs'] ) ) {
								$tabs = $screen_args['tabs'];
								unset( $screen_args['tabs'] );
							}
							$screen = $menu->add( new Screen( $screen_slug, $screen_args ) );
							if ( is_wp_error( $screen ) ) {
								self::doing_it_wrong( __METHOD__, $screen->get_error_message(), '0.5.0' );
							} elseif ( is_array( $tabs ) ) {
								foreach ( $tabs as $tab_slug => $tab_args ) {
									$sections = array();
									if ( isset( $tab_args['sections'] ) ) {
										$sections = $tab_args['sections'];
										unset( $tab_args['sections'] );
									}
									$tab = $screen->add( new Tab( $tab_slug, $tab_args ) );
									if ( is_wp_error( $tab ) ) {
										self::doing_it_wrong( __METHOD__, $tab->get_error_message(), '0.5.0' );
									} elseif ( is_array( $sections ) ) {
										foreach ( $sections as $section_slug => $section_args ) {
											$fields = array();
											if ( isset( $section_args['fields'] ) ) {
												$fields = $section_args['fields'];
												unset( $section_args['fields'] );
											}
											$section = $tab->add( new Section( $section_slug, $section_args ) );
											if ( is_wp_error( $section ) ) {
												self::doing_it_wrong( __METHOD__, $section->get_error_message(), '0.5.0' );
											} elseif ( is_array( $fields ) ) {
												foreach ( $fields as $field_slug => $field_args ) {
													$field = $section->add( new Field( $field_slug, $field_args ) );
													if ( is_wp_error( $field ) ) {
														self::doing_it_wrong( __METHOD__, $field->get_error_message(), '0.5.0' );
													}
												}
											}
										}
									}
								}
							}
						}
					}
				}
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
			if ( ! $this->initialization_triggered ) {
				$this->initialization_triggered = true;

				ComponentManager::register_hierarchy( array(
					'WPDLib\Components\Menu'		=> array(
						'WPOD\Components\Screen'		=> array(
							'WPOD\Components\Tab'			=> array(
								'WPOD\Components\Section'		=> array(
									'WPOD\Components\Field'			=> array(),
								),
							),
						),
					),
				) );

				do_action( 'wpod', $this );

				$this->initialized = true;
			} else {
				self::doing_it_wrong( __METHOD__, __( 'This function should never be called manually.', 'wpod' ), '0.5.0' );
			}
		}
	}
}
