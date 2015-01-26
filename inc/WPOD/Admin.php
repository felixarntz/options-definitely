<?php
/**
 * @package WPOD
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPOD;

class Admin {
	private static $instance = null;

	public static function instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'create_admin_menu' ), 50 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'wpod_update_option_defaults', array( $this, 'update_option_defaults' ) );
	}

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

	public function enqueue_scripts() {
		$currents = $this->get_current();

		if ( $currents ) {
			$locale = str_replace( '_', '-', get_locale() );
			$language = substr( $locale, 0, 2 );

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
				'nonce'						=> wp_create_nonce( 'wpod-ajax-request' ),
				'action_add_repeatable'		=> 'wpod_insert_repeatable',
				'locale'					=> $locale,
				'language'					=> $language,
				'date_format'				=> get_option( 'date_format' ),
				'time_format'				=> get_option( 'time_format' ),
				'start_of_week'				=> get_option( 'start_of_week' ),
				'localized_open_file'		=> __( 'Open file', 'wpod' ),
			) );

			$fields = \WPOD\Framework::instance()->query( array(
				'type'				=> 'field',
				'parent_slug'		=> $currents['tab']->slug,
				'parent_type'		=> 'tab',
			) );

			foreach ( $fields as $field ) {
				if ( 'media' == $field->type || 'repeatable' == $field->type ) {
					wp_enqueue_media();
					break;
				}
			}

			if ( 'draggable' == $currents['tab']->mode ) {
				wp_enqueue_script( 'common' );
				wp_enqueue_script( 'wp-lists' );
				wp_enqueue_script( 'postbox' );
			}
		}
	}

	public function update_option_defaults() {
		$tabs = \WPOD\Framework::instance()->query( array(
			'type'				=> 'tab',
		) );

		foreach ( $tabs as $tab ) {
			$tab->update_option_defaults();
		}
	}

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

	public function get_current_url() {
		global $pagenow;

		return add_query_arg( $_GET, get_admin_url( null, $pagenow ) );
	}
}
