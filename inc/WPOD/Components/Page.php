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

class Page extends ComponentBase {

	protected $page_hook = '';
	protected $help = array();

	public function add_to_menu() {
		$menu = \WPOD\Framework::instance()->query( array(
			'slug'			=> $this->parent,
			'type'			=> 'menu',
		), true );

		if ( false === $menu->added ) {
			$this->page_hook = add_menu_page( $this->args['title'], $menu->label, $this->args['capability'], $this->slug, array( $this, 'render' ), $menu->icon, $menu->position );

			\WPOD\Framework::instance()->update( $menu->slug, 'menu', array(
				'added'			=> true,
				'subslug'		=> $this->slug,
				'sublabel'		=> $this->args['label'],
			) );
		} else {
			$this->page_hook = add_submenu_page( $menu->subslug, $this->args['title'], $this->args['label'], $this->args['capability'], $this->slug, array( $this, 'render' ) );

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

	public function render() {
		echo '<div class="wrap">';

		echo '<h1>' . $this->args['title'] . '</h1>';

		do_action( 'wpod_page_before', $this->slug, $this->args, $this->parent );

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

			settings_errors( $current_tab->slug );

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
			\LaL_WP_Plugin_Util::get( 'options-definitely' )->doing_it_wrong( __METHOD__, sprintf( __( 'There are no tabs to display for the page %s. Either add some or adjust the required capabilities.', 'wpod' ), $this->slug ), '1.0.0' );
		}

		do_action( 'wpod_page_after', $this->slug, $this->args, $this->parent );

		echo '</div>';
	}

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
			$tab = wp_parse_args( $tab, array(
				'title'			=> __( 'Help tab title', 'wpod' ),
				'content'		=> '',
				'callback'		=> false,
			) );
		}
	}

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

		return apply_filters( 'wpod_page_defaults', $defaults );
	}
}
