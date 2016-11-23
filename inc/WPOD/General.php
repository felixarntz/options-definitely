<?php
/**
 * WPOD\General class
 *
 * @package WPOD
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 * @since 0.6.7
 */

namespace WPOD;

use WPDLib\Components\Manager as ComponentManager;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPOD\General' ) ) {
	/**
	 * This class registers settings.
	 *
	 * @internal
	 * @since 0.6.7
	 */
	class General {

		/**
		 * Holds the instance of this class.
		 *
		 * @since 0.6.7
		 * @access private
		 * @static
		 * @var WPOD\General|null
		 */
		private static $instance = null;

		/**
		 * Gets the instance of this class. If it does not exist, it will be created.
		 *
		 * @since 0.6.7
		 * @access public
		 * @static
		 *
		 * @return WPOD\General
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
		 * @since 0.6.7
		 * @access private
		 */
		private function __construct() {
			add_action( 'after_setup_theme', array( $this, 'add_hooks' ) );
		}

		/**
		 * Hooks in all the necessary actions.
		 *
		 * This function should be executed after the plugin has been initialized.
		 *
		 * @since 0.6.7
		 * @access public
		 */
		public function add_hooks() {
			if ( version_compare( get_bloginfo( 'version' ), '4.7', '>=' ) ) {
				add_action( 'init', array( $this, 'register_settings' ), 50 );
			}
		}
		/**
		 * Registers settings.
		 *
		 * Registering settings with details was introduced in WordPress 4.7. Therefore this
		 * method is not used in versions below that.
		 *
		 * @since 0.6.7
		 * @access public
		 *
		 * @see WPOD\Components\Tab
		 */
		public function register_settings() {
			$tabs = ComponentManager::get( '*.*.*', 'WPDLib\Components\Menu.WPOD\Components\Screen' );
			foreach ( $tabs as $tab ) {
				$tab->register();
			}
		}

	}
}
