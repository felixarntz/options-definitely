<?php
/**
 * @package WPOD
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPOD;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * This class performs the necessary actions in the WordPress admin.
 *
 * This includes both registering and displaying options.
 *
 * @internal
 * @since 1.0.0
 */
class Admin {

	/**
	 * @since 1.0.0
	 * @var WPOD\Admin|null Holds the instance of this class.
	 */
	private static $instance = null;

	/**
	 * Gets the instance of this class. If it does not exist, it will be created.
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
	 */
	private function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'create_admin_menu' ), 50 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
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
	 * @since 1.0.0
	 * @return void
	 */
	public function register_settings() {
		$tabs = \WPOD\Framework::instance()->query( array(
			'type'				=> 'tab',
		) );

		foreach ( $tabs as $tab ) {
			$tab->register();

			$sections = \WPOD\Framework::instance()->query( array(
				'type'				=> 'section',
				'parent_slug'		=> $tab->slug,
				'parent_type'		=> 'tab',
			) );

			foreach ( $sections as $section ) {
				$section->register( $tab );

				$fields = \WPOD\Framework::instance()->query( array(
					'type'				=> 'field',
					'parent_slug'		=> $section->slug,
					'parent_type'		=> 'section',
				) );

				foreach ( $fields as $field ) {
					$field->register( $tab, $section );
				}
			}
		}
	}

	/**
	 * Adds pages to the WordPress admin menu.
	 *
	 * For each menu, it is checked whether this menu has already been added.
	 * If so, the menu slug is updated accordingly.
	 *
	 * Every page will be added to the menu it has been assigned to.
	 * Furthermore the function to add a help tab is hooked into the page loading action.
	 *
	 * @see WPOD\Components\Menu
	 * @see WPOD\Components\Page
	 * @since 1.0.0
	 * @return void
	 */
	public function create_admin_menu() {
		$menus = \WPOD\Framework::instance()->query( array(
			'type'				=> 'menu',
		) );

		foreach ( $menus as $menu ) {
			if ( ( $menu_slug = $menu->is_already_added() ) ) {
				\WPOD\Framework::instance()->update( $menu->slug, 'menu', array(
					'added'			=> true,
					'subslug'		=> $menu_slug,
					'sublabel'		=> true,
				) );
			}
		}

		$pages = \WPOD\Framework::instance()->query( array(
			'type'				=> 'page',
		) );

		foreach ( $pages as $page ) {
			$page_hook = $page->add_to_menu();
			if ( ! empty( $page_hook ) ) {
				add_action( 'load-' . $page_hook, array( $page, 'render_help' ) );
			}
		}
	}

	/**
	 * Enqueues necessary stylesheets and scripts.
	 *
	 * All assets are only enqueued if we are on a settings page created by the plugin.
	 * Besides adding the plugin stylesheets and scripts, this function might also enqueue
	 * the WordPress media scripts and the WordPress meta box scripts, both depending on
	 * whether they are needed on the current page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_scripts() {
		$currents = $this->get_current();

		if ( $currents ) {
			$locale = str_replace( '_', '-', get_locale() );
			$language = substr( $locale, 0, 2 );

			$fields = \WPOD\Framework::instance()->query( array(
				'type'				=> 'field',
				'parent_slug'		=> $currents['tab']->slug,
				'parent_type'		=> 'tab',
			) );

			$repeatable_field_templates = array();

			$media_enqueued = false;
			foreach ( $fields as $field ) {
				if ( 'repeatable' == $field->type ) {
					$id_prefix = $currents['tab']->slug . '-' . $field->slug;
					$name_prefix = $currents['tab']->slug . '[' . $field->slug . ']';
					ob_start();
					$field->render_repeatable_row( '{{' . 'KEY' . '}}', $id_prefix, $name_prefix );
					$repeatable_field_templates[ $id_prefix ] = ob_get_clean();

					$repeatable = $field->repeatable;
					if ( ! $media_enqueued ) {
						foreach ( $repeatable['fields'] as $repeatable_field ) {
							if ( 'media' == $repeatable_field['type'] ) {
								wp_enqueue_media();
								$media_enqueued = true;
							}
						}
					}
				} elseif ( 'media' == $field->type && ! $media_enqueued ) {
					wp_enqueue_media();
					$media_enqueued = true;
				}
			}

			if ( 'draggable' == $currents['tab']->mode ) {
				wp_enqueue_script( 'common' );
				wp_enqueue_script( 'wp-lists' );
				wp_enqueue_script( 'postbox' );
			}

			wp_enqueue_style( 'select2', WPOD_URL . '/assets/third-party/select2/select2.css', array(), false );
			wp_enqueue_script( 'select2', WPOD_URL . '/assets/third-party/select2/select2.min.js', array( 'jquery' ), false, true );
			if ( file_exists( WPOD_PATH . '/assets/third-party/select2/select2_locale_' . $locale . '.js' ) ) {
				wp_enqueue_script( 'select2-locale', WPOD_URL . '/assets/third-party/select2/select2_locale_' . $locale . '.js', array( 'select2' ), false, true );
			} elseif( file_exists( WPOD_PATH . '/assets/third-party/select2/select2_locale_' . $language . '.js' ) ) {
				wp_enqueue_script( 'select2-locale', WPOD_URL . '/assets/third-party/select2/select2_locale_' . $language . '.js', array( 'select2' ), false, true );
			}

			wp_enqueue_style( 'datetimepicker', WPOD_URL . '/assets/third-party/datetimepicker/jquery.datetimepicker.css', array(), false );
			wp_enqueue_script( 'datetimepicker', WPOD_URL . '/assets/third-party/datetimepicker/jquery.datetimepicker.js', array( 'jquery' ), false, true );

			wp_enqueue_style( 'wpod-admin', WPOD_URL . '/assets/admin.min.css', array(), WPOD_VERSION );
			wp_enqueue_script( 'wpod-admin', WPOD_URL . '/assets/admin.min.js', array( 'select2', 'datetimepicker' ), WPOD_VERSION, true );
			wp_localize_script( 'wpod-admin', '_wpod_admin', array(
				'locale'						=> $locale,
				'language'						=> $language,
				'date_format'					=> get_option( 'date_format' ),
				'time_format'					=> get_option( 'time_format' ),
				'start_of_week'					=> get_option( 'start_of_week' ),
				'localized_open_file'			=> __( 'Open file', 'wpod' ),
				'repeatable_field_templates'	=> $repeatable_field_templates,
			) );
		}
	}

	/**
	 * Gets the currently active page and tab.
	 *
	 * The function checks the currently loaded admin page.
	 * If it is not created by the plugin, the function will return false.
	 * Otherwise the output depends on the $type parameter:
	 * The function may return the page object, the tab object or an array of both objects.
	 *
	 * The second parameter may be used to omit the retrieving process by specifying a page object.
	 * In that case, only the current tab as part of this page will be looked for.
	 *
	 * @since 1.0.0
	 * @param string $type the type to get the current component for; must be either 'page', 'tab' or an empty string to get an array of both
	 * @param WPOD\Components\Page|null $page a page object to override the retrieving process or null
	 * @return WPOD\Components\Page|WPOD\Components\Tab|array|false either the page or tab object, an array of both objects or false if no plugin component is currently active
	 */
	public function get_current( $type = '', $page = null ) {
		if ( isset( $_GET['page'] ) ) {
			if ( null == $page ) {
				$page = \WPOD\Framework::instance()->query( array(
					'slug'				=> $_GET['page'],
					'type'				=> 'page',
				), true );
			}

			if ( $page ) {
				if ( 'page' == $type ) {
					return $page;
				}

				$args = array(
					'type'				=> 'tab',
					'parent_slug'		=> $page->slug,
					'parent_type'		=> 'page',
				);

				if ( isset( $_GET['tab'] ) ) {
					$args['slug'] = $_GET['tab'];
				}

				$tab = \WPOD\Framework::instance()->query( $args, true );

				if ( $tab ) {
					if ( 'tab' == $type ) {
						return $tab;
					}

					return compact( 'page', 'tab' );
				}
			}
		}

		return false;
	}

	/**
	 * Gets the current URL in the WordPress backend.
	 *
	 * @since 1.0.0
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
