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

/**
 * Class for a page component.
 *
 * A page denotes an options page in the WordPress admin.
 *
 * @internal
 * @since 0.5.0
 */
class Page extends ComponentBase {

	/**
	 * @since 0.5.0
	 * @var string Holds the page hook for this page (used in the WordPress action `'load-' . $page_hook`).
	 */
	protected $page_hook = '';

	/**
	 * Adds the page to the WordPress admin menu.
	 *
	 * If the parent menu has not been added yet, the page will be added as the top level item of this menu.
	 * If it has been added, the page will be added as a submenu item to this menu.
	 *
	 * By adding the page to the menu, a page hook is assigned to it.
	 *
	 * This function is called by the WPOD\Admin class.
	 *
	 * @since 0.5.0
	 * @see WPOD\Admin::create_admin_menu()
	 * @return string the page hook of this page
	 */
	public function add_to_menu() {
		$menu = \WPOD\Framework::instance()->query( array(
			'slug'			=> $this->parent,
			'type'			=> 'menu',
		), true );

		if ( false === $menu->added ) {
			$this->page_hook = add_menu_page( $this->args['title'], $menu->label, $this->args['capability'], $this->real_slug, array( $this, 'render' ), $menu->icon, $menu->position );

			\WPOD\Framework::instance()->update( $menu->slug, 'menu', array(
				'added'			=> true,
				'subslug'		=> $this->real_slug,
				'sublabel'		=> $this->args['label'],
			) );
		} else {
			$this->page_hook = add_submenu_page( $menu->subslug, $this->args['title'], $this->args['label'], $this->args['capability'], $this->real_slug, array( $this, 'render' ) );

			if ( $menu->sublabel !== true ) {
				global $submenu;

				if ( isset( $submenu[ $menu->subslug ] ) ) {
					$submenu[ $menu->subslug ][0][0] = $menu->sublabel;

					\WPOD\Framework::instance()->update( $menu->slug, 'menu', array(
						'sublabel'		=> true,
					) );
				}
			}
		}

		return $this->page_hook;
	}

	/**
	 * Renders the page.
	 *
	 * It displays the title and (optionally) description of the page.
	 * Then it iterates through the tabs that belong to the page and calls each one's `render()` function.
	 *
	 * @since 0.5.0
	 */
	public function render() {
		echo '<div class="wrap">';

		echo '<h1>' . $this->args['title'] . '</h1>';

		do_action( 'wpod_page_before', $this->real_slug, $this->args, $this->parent );

		if ( ! empty( $this->args['description'] ) ) {
			echo '<p class="description">' . $this->args['description'] . '</p>';
		}

		$tabs = \WPOD\Framework::instance()->query( array(
			'type'				=> 'tab',
			'parent_slug'		=> $this->slug,
			'parent_type'		=> 'page',
		) );
		$tabs = array_filter( $tabs, 'wpod_current_user_can' );

		if ( count( $tabs ) > 0 ) {
			$current_tab = \WPOD\Admin::instance()->get_current( 'tab', $this );

			if ( count( $tabs ) > 1 ) {
				$current_url = \WPOD\Admin::instance()->get_current_url();

				echo '<h2 class="nav-tab-wrapper">';

				foreach ( $tabs as $tab ) {
					$class = 'nav-tab';

					if ( $tab->slug == $current_tab->slug ) {
						$class .= ' nav-tab-active';
					}

					echo '<a class="' . $class . '" href="' . add_query_arg( 'tab', $tab->slug, $current_url ) . '">' . $tab->title . '</a>';
				}

				echo '</h2>';
			}
			$current_tab->render();
		} else {
			\LaL_WP_Plugin_Util::get( 'options-definitely' )->doing_it_wrong( __METHOD__, sprintf( __( 'There are no tabs to display for the page %s. Either add some or adjust the required capabilities.', 'wpod' ), $this->real_slug ), '0.5.0' );
		}

		do_action( 'wpod_page_after', $this->real_slug, $this->args, $this->parent );

		echo '</div>';
	}

	/**
	 * Adds help tabs and help sidebar to the page if they are specified.
	 *
	 * This function is called by the WPOD\Admin class.
	 *
	 * @since 0.5.0
	 * @see WPOD\Admin::create_admin_menu()
	 */
	public function render_help() {
		$screen = get_current_screen();

		foreach ( $this->args['help']['tabs'] as $slug => $tab ) {
			$args = array_merge( array( 'id' => $slug ), $tab );

			$screen->add_help_tab( $args );
		}

		if ( ! empty( $this->args['help']['sidebar'] ) ) {
			$screen->set_help_sidebar( $this->args['help']['sidebar'] );
		}
	}

	/**
	 * Validates the arguments array.
	 *
	 * @since 0.5.0
	 */
	public function validate() {
		parent::validate();

		if( ! is_array( $this->args['help'] ) ) {
			$this->args['help'] = array();
		}

		if ( ! isset( $this->args['help']['tabs'] ) || ! is_array( $this->args['help']['tabs'] ) ) {
			$this->args['help']['tabs'] = array();
		}

		if ( ! isset( $this->args['help']['sidebar'] ) ) {
			$this->args['help']['sidebar'] = '';
		}

		foreach ( $this->args['help']['tabs'] as $slug => &$tab ) {
			$tab = \LaL_WP_Plugin_Util::parse_args( $tab, array(
				'title'			=> __( 'Help tab title', 'wpod' ),
				'content'		=> '',
				'callback'		=> false,
			), true );
		}
	}

	/**
	 * Returns the keys of the arguments array and their default values.
	 *
	 * Read the plugin guide for more information about the page arguments.
	 *
	 * @since 0.5.0
	 * @return array
	 */
	protected function get_defaults() {
		$defaults = array(
			'title'			=> __( 'Page title', 'wpod' ),
			'label'			=> __( 'Page label', 'wpod' ),
			'description'	=> '',
			'capability'	=> 'manage_options',
			'help'			=> array(
				'tabs'			=> array(),
				'sidebar'		=> '',
			),
		);

		/**
		 * This filter can be used by the developer to modify the default values for each page component.
		 *
		 * @since 0.5.0
		 * @param array the associative array of default values
		 */
		return apply_filters( 'wpod_page_defaults', $defaults );
	}
}
