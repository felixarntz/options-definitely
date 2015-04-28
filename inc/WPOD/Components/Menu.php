<?php
/**
 * @package WPOD
 * @version 1.0.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPOD\Components;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class for a menu component.
 *
 * A menu works slightly different compared to other components:
 * It is always a top component, so it does not have a parent component.
 * Furthermore it might not be an actually new component, it can also specify a menu that already exists in WordPress Core.
 *
 * @internal
 * @since 1.0.0
 */
class Menu extends ComponentBase {

	/**
	 * Checks if the menu has already been added or if it is part of WP Core.
	 *
	 * @since 1.0.0
	 * @return string|boolean the slug of the menu if it has already been added, otherwise boolean false
	 */
	public function is_already_added() {
		global $admin_page_hooks;

		// check for the exact menu slug
		if ( isset( $admin_page_hooks[ $this->slug ] ) )
		{
			return $this->slug;
		}

		// check for the sanitized menu title
		if ( ( $key = array_search( $this->slug, $admin_page_hooks ) ) !== false && strstr( $key, 'separator' ) === false ) {
			return $key;
		}

		// check if it is a post type menu
		if ( isset( $admin_page_hooks[ 'edit.php?post_type=' . $this->slug ] ) ) {
			return 'edit.php?post_type=' . $this->slug;
		}

		// special case: post type 'post'
		if ( 'post' == $this->slug ) {
			return 'edit.php';
		}

		return false;
	}

	/**
	 * Validates the arguments array.
	 *
	 * @since 1.0.0
	 */
	public function validate() {
		parent::validate();

		$this->args['added'] = false;
		$this->args['subslug'] = $this->slug;
		$this->args['sublabel'] = false;
	}

	/**
	 * Returns the keys of the arguments array and their default values.
	 *
	 * Read the plugin guide for more information about the menu arguments.
	 *
	 * @since 1.0.0
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
		 * @since 1.0.0
		 * @param array the associative array of default values
		 */
		return apply_filters( 'wpod_menu_defaults', $defaults );
	}
}
