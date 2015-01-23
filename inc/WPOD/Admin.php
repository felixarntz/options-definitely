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
		$members = \WPOD\Framework::instance()->query( array(
			'type'				=> 'member',
		) );

		foreach ( $members as $member ) {
			$member->register();

			$sections = \WPOD\Framework::instance()->query( array(
				'type'				=> 'section',
				'parent_slug'		=> $member->slug,
				'parent_type'		=> 'member',
			) );

			foreach ( $sections as $section ) {
				$section->register( $member );

				$fields = \WPOD\Framework::instance()->query( array(
					'type'				=> 'field',
					'parent_slug'		=> $section->slug,
					'parent_type'		=> 'section',
				) );

				foreach ( $fields as $field ) {
					$field->register( $member, $section );
				}
			}
		}
	}

	public function create_admin_menu() {
		$groups = \WPOD\Framework::instance()->query( array(
			'type'				=> 'group',
		) );

		foreach ( $groups as $group ) {
			if ( $group->is_already_added() ) {
				\WPOD\Framework::instance()->update( $group->slug, 'group', array(
					'added'			=> true,
					'subslug'		=> $group->slug,
					'sublabel'		=> true,
				) );
			}
		}

		$sets = \WPOD\Framework::instance()->query( array(
			'type'				=> 'set',
		) );

		foreach ( $sets as $set ) {
			$page_hook = $set->add_to_menu();
			if ( !empty( $page_hook ) ) {
				add_action( 'load-' . $page_hook, array( $set, 'render_help' ) );
			}
		}
	}

	public function enqueue_scripts() {
		$currents = $this->get_current();

		if ( $currents ) {
			wp_enqueue_style( 'select2', WPOD_URL . '/assets/third-party/select2/select2.css', array(), false );
			wp_enqueue_script( 'select2', WPOD_URL . '/assets/third-party/select2/select2.min.js', array( 'jquery' ), false, true );

			wp_enqueue_style( 'datetimepicker', WPOD_URL . '/assets/third-party/datetimepicker/jquery.datetimepicker.css', array(), false );
			wp_enqueue_script( 'datetimepicker', WPOD_URL . '/assets/third-party/datetimepicker/jquery.datetimepicker.js', array( 'jquery' ), false, true );

			wp_enqueue_style( 'wpod-admin', WPOD_URL . '/assets/admin.min.css', array(), WPOD_VERSION );
			wp_enqueue_script( 'wpod-admin', WPOD_URL . '/assets/admin.min.js', array( 'select2', 'datetimepicker' ), WPOD_VERSION, true );
			wp_localize_script( 'wpod-admin', '_wpod_admin', array(
				'nonce'						=> wp_create_nonce( 'wpod-ajax-request' ),
				'action_add_repeatable'		=> 'wpod_insert_repeatable',
			) );

			$fields = \WPOD\Framework::instance()->query( array(
				'type'				=> 'field',
				'parent_slug'		=> $currents['member']->slug,
				'parent_type'		=> 'member',
			) );

			foreach ( $fields as $field ) {
				if ( 'media' == $field->type || 'repeatable' == $field->type ) {
					wp_enqueue_media();
					break;
				}
			}

			if ( 'draggable' == $currents['member']->mode ) {
				wp_enqueue_script( 'common' );
				wp_enqueue_script( 'wp-lists' );
				wp_enqueue_script( 'postbox' );
			}
		}
	}

	public function update_option_defaults() {
		$members = \WPOD\Framework::instance()->query( array(
			'type'				=> 'member',
		) );

		foreach ( $members as $member ) {
			$member->update_option_defaults();
		}
	}

	public function get_current( $type = '', $set = null ) {
		if ( isset( $_GET['page'] ) ) {
			if ( null == $set ) {
				$set = \WPOD\Framework::instance()->query( array(
					'slug'				=> $_GET['page'],
					'type'				=> 'set',
				), true );
			}

			if ( $set ) {
				if ( 'set' == $type ) {
					return $set;
				}

				$args = array(
					'type'				=> 'member',
					'parent_slug'		=> $set->slug,
					'parent_type'		=> 'set',
				);

				if ( isset( $_GET['tab'] ) ) {
					$args['slug'] = $_GET['tab'];
				}

				$member = \WPOD\Framework::instance()->query( $args, true );

				if ( $member ) {
					if ( 'member' == $type ) {
						return $member;
					}

					return compact( 'set', 'member' );
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
