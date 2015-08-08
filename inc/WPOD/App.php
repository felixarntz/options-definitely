<?php
/**
 * @package WPOD
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPOD;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * This class initializes the plugin.
 *
 * It also triggers the action and filter to hook into and contains all API functions of the plugin.
 *
 * @since 0.5.0
 */
class App extends \LaL_WP_Plugin {

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
			\WPOD\Admin::instance();
		}

		// use after_setup_theme action so it is initialized as soon as possible, but also so that both plugins and themes can use the action
		add_action( 'after_setup_theme', array( $this, 'init' ), 1 );
	}

	public function set_scope( $scope ) {
		\WPDLib\Components\Manager::set_scope( $scope );
	}

	public function add( $component, $scope = '' ) {
		if ( ! empty( $scope ) ) {
			$this->set_scope( $scope );
		}
		return \WPDLib\Components\Manager::add( $component );
	}

	public function add_components( $components, $scope = '' ) {
		if ( ! empty( $scope ) ) {
			$this->set_scope( $scope );
		}
		if ( is_array( $components ) ) {
			foreach ( $components as $menu_slug => $menu_args ) {
				$menu = \WPDLib\Components\Manager::add( new \WPOD\Components\Menu( $menu_slug, $menu_args ) );
				if ( is_wp_error( $menu ) ) {
					self::doing_it_wrong( __METHOD__, $menu->get_error_message(), '0.5.0' );
				} elseif ( isset( $menu_args['screens'] ) && is_array( $menu_args['screens'] ) ) {
					foreach ( $menu_args['screens'] as $screen_slug => $screen_args ) {
						$screen = $menu->add( new \WPOD\Components\Screen( $screen_slug, $screen_args ) );
						if ( is_wp_error( $screen ) ) {
							self::doing_it_wrong( __METHOD__, $screen->get_error_message(), '0.5.0' );
						} elseif ( isset( $screen_args['tabs'] ) && is_array( $screen_args['tabs'] ) ) {
							foreach ( $screen_args['tabs'] as $tab_slug => $tab_args ) {
								$tab = $screen->add( new \WPOD\Components\Tab( $tab_slug, $tab_args ) );
								if ( is_wp_error( $tab ) ) {
									self::doing_it_wrong( __METHOD__, $tab->get_error_message(), '0.5.0' );
								} elseif ( isset( $tab_args['sections'] ) && is_array( $tab_args['sections'] ) ) {
									foreach ( $tab_args['sections'] as $section_slug => $section_args ) {
										$section = $tab->add( new \WPOD\Components\Section( $section_slug, $section_args ) );
										if ( is_wp_error( $section ) ) {
											self::doing_it_wrong( __METHOD__, $section->get_error_message(), '0.5.0' );
										} elseif ( isset( $section_args['fields'] ) && is_array( $section_args['fields'] ) ) {
											foreach ( $section_args['fields'] as $field_slug => $field_args ) {
												$field = $section->add( new \WPOD\Components\Field( $field_slug, $field_args ) );
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
	 * There are two ways to add components: Either the filter 'wpod' can be used to specify a nested hiearchical array of components
	 * or, alternatively, the action 'wpod_oo' can be used to interact directly with this class.
	 *
	 * This function first applies the 'wpod' filter, then iterates through the array to add the components to the plugin.
	 * To do that it utilizes the add() method of this class which can also be utilized by the developer when using the 'wpod_oo' action.
	 * This action is triggered after the filter. This means that whatever is included in the filter will be added first.
	 *
	 * @internal
	 * @see WPOD\App::add()
	 * @since 0.5.0
	 */
	public function init() {
		if ( ! $this->initialization_triggered ) {
			$this->initialization_triggered = true;

			\WPDLib\Components\Manager::register_hierarchy( array(
				'WPOD\Components\Menu'		=> array(
					'WPOD\Components\Screen'		=> array(
						'WPOD\Components\Tab'		=> array(
							'WPOD\Components\Section'	=> array(
								'WPOD\Components\Field'		=> array(),
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
