<?php
/**
 * @package WPOD
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPOD\Components;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPOD\Components\Menu' ) ) {
	/**
	 * Class for a menu component.
	 *
	 * A menu works slightly different compared to other components:
	 * It is always a top component, so it does not have a parent component.
	 * Furthermore it might not be an actually new component, it can also specify a menu that already exists in WordPress Core.
	 *
	 * @internal
	 * @since 0.5.0
	 */
	class Menu extends \WPDLib\Components\Base {

		/**
		 * Checks if the menu has already been added or if it is part of WP Core.
		 *
		 * @since 0.5.0
		 * @return string|boolean the slug of the menu if it has already been added, otherwise boolean false
		 */
		public function is_already_added( $screen_slug ) {
			global $admin_page_hooks;

			if ( null !== $this->args['added'] ) {
				return $this->args['added'];
			}

			$this->args['added'] = false;

			if ( isset( $admin_page_hooks[ $this->slug ] ) ) {
				// check for the exact menu slug
				$this->args['added'] = true;
				$this->args['subslug'] = $this->slug;
				$this->args['sublabel'] = true;
			} elseif ( ( $key = array_search( $this->slug, $admin_page_hooks ) ) !== false && strstr( $key, 'separator' ) === false ) {
				// check for the sanitized menu title
				$this->args['added'] = true;
				$this->args['subslug'] = $key;
				$this->args['sublabel'] = true;
			} elseif ( ! in_array( $this->slug, array( 'menu', 'submenu' ) ) && function_exists( 'add_' . $this->slug . '_page' ) ) {
				// check for submenu page function
				$this->args['added'] = true;
				$this->args['subslug'] = 'add_' . $this->slug . '_page';
				$this->args['sublabel'] = true;
			} elseif ( isset( $admin_page_hooks[ 'edit.php?post_type=' . $this->slug ] ) ) {
				// check if it is a post type menu
				$this->args['added'] = true;
				$this->args['subslug'] = 'edit.php?post_type=' . $this->slug;
				$this->args['sublabel'] = true;
			} elseif ( 'post' == $this->slug ) {
				// special case: post type 'post'
				$this->args['added'] = true;
				$this->args['subslug'] = 'edit.php';
				$this->args['sublabel'] = true;
			} elseif ( isset( $admin_page_hooks[ $screen_slug ] ) ) {
				$this->args['added'] = true;
				$this->args['subslug'] = $screen_slug;
				$this->args['sublabel'] = true;
			}

			return $this->args['added'];
		}

		/**
		 * Validates the arguments array.
		 *
		 * @since 0.5.0
		 */
		public function validate( $parent = null ) {
			$status = parent::validate( $parent );

			if ( $status === true ) {
				$this->args['added'] = null;
				$this->args['subslug'] = $this->slug;
				$this->args['sublabel'] = false;

				//TODO add special validation for icon
			}

			return $status;
		}

		/**
		 * Returns the keys of the arguments array and their default values.
		 *
		 * Read the plugin guide for more information about the menu arguments.
		 *
		 * @since 0.5.0
		 * @return array
		 */
		protected function get_defaults() {
			$defaults = array(
				'label'			=> __( 'Menu label', 'wpod' ),
				'icon'			=> '',
				'position'		=> null,
			);

			/**
			 * This filter can be used by the developer to modify the default values for each menu component.
			 *
			 * @since 0.5.0
			 * @param array the associative array of default values
			 */
			return apply_filters( 'wpod_menu_defaults', $defaults );
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
			return true;
		}
	}
}
