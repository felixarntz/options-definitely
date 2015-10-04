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

			add_filter( 'wpdlib_menu_validated', array( $this, 'menu_validated' ), 10, 2 );
			add_filter( 'wpdlib_screen_validated', array( $this, 'screen_validated' ), 10, 2 );
			add_filter( 'wpdlib_tab_validated', array( $this, 'tab_validated' ), 10, 2 );
			add_filter( 'wpdlib_section_validated', array( $this, 'section_validated' ), 10, 2 );
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
					$menu = ComponentManager::add( new Menu( $menu_slug, $menu_args ) );
					if ( is_wp_error( $menu ) ) {
						self::doing_it_wrong( __METHOD__, $menu->get_error_message(), '0.5.0' );
					} else {
						if ( isset( $menu_args['screens'] ) && is_array( $menu_args['screens'] ) ) {
							foreach ( $menu_args['screens'] as $screen_slug => $screen_args ) {
								$screen = $menu->add( new Screen( $screen_slug, $screen_args ) );
								if ( is_wp_error( $screen ) ) {
									self::doing_it_wrong( __METHOD__, $screen->get_error_message(), '0.5.0' );
								} else {
									if ( isset( $screen_args['tabs'] ) && is_array( $screen_args['tabs'] ) ) {
										foreach ( $screen_args['tabs'] as $tab_slug => $tab_args ) {
											$tab = $screen->add( new Tab( $tab_slug, $tab_args ) );
											if ( is_wp_error( $tab ) ) {
												self::doing_it_wrong( __METHOD__, $tab->get_error_message(), '0.5.0' );
											} else {
												if ( isset( $tab_args['sections'] ) && is_array( $tab_args['sections'] ) ) {
													foreach ( $tab_args['sections'] as $section_slug => $section_args ) {
														$section = $tab->add( new Section( $section_slug, $section_args ) );
														if ( is_wp_error( $section ) ) {
															self::doing_it_wrong( __METHOD__, $section->get_error_message(), '0.5.0' );
														} else {
															if ( isset( $section_args['fields'] ) && is_array( $section_args['fields'] ) ) {
																foreach ( $section_args['fields'] as $field_slug => $field_args ) {
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
			if ( ! did_action( 'wpod' ) ) {
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

				do_action( 'wpod', $this );
			} else {
				self::doing_it_wrong( __METHOD__, __( 'This function should never be called manually.', 'options-definitely' ), '0.5.0' );
			}
		}

		public function menu_validated( $args, $menu ) {
			if ( isset( $args['screens'] ) ) {
				unset( $args['screens'] );
			}
			return $args;
		}

		public function screen_validated( $args, $screen ) {
			if ( isset( $args['tabs'] ) ) {
				unset( $args['tabs'] );
			}
			return $args;
		}

		public function tab_validated( $args, $tab ) {
			if ( isset( $args['sections'] ) ) {
				unset( $args['sections'] );
			}
			return $args;
		}

		public function section_validated( $args, $section ) {
			if ( isset( $args['fields'] ) ) {
				unset( $args['fields'] );
			}
			return $args;
		}
	}
}
