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

class Menu extends ComponentBase {
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

	public function validate() {
		parent::validate();

		$this->args['added'] = false;
		$this->args['subslug'] = $this->slug;
		$this->args['sublabel'] = false;
	}

	protected function get_defaults() {
		$defaults = array(
			'label'			=> __( 'Menu label', 'wpod' ),
			'icon'			=> '',
			'position'		=> null,
		);

		return apply_filters( 'wpod_menu_defaults', $defaults );
	}
}
