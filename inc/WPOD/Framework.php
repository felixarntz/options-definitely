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

class Framework {

	private static $instance = null;

	public static function instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	private $initialized = false;

	private $menus = array();
	private $pages = array();
	private $tabs = array();
	private $sections = array();
	private $fields = array();

	private function __construct() {
		if ( is_admin() ) {
			\WPOD\Admin::instance();
		}

		// use after_setup_theme action so it is initialized as soon as possible, but also so that both plugins and themes can use the filter/action
		add_action( 'after_setup_theme', array( $this, 'init' ), 1 );
		add_action( 'after_setup_theme', array( $this, 'validate' ), 2 );
	}

	/*
	* ===================================================================================================
	* BASIC USAGE
	* You can either use the filter 'wpod' to create a multidimensional array of nested components
	* (menus, pages, tabs, sections and fields).
	* Alternatively, you can use the action 'wpod_oo' which passes this class to the hooked function.
	* In that function, you can then use the class methods 'add', 'update' and 'delete' (which you see
	* right below) to directly modify components (menus, pages, tabs, sections and fields).
	*
	* Both methods can be used interchangeably and are compatible with each other since the plugin
	* internally runs through the filtered array and then also uses the 'add' method on each component
	* in there. The action is executed after that process.
	* ===================================================================================================
	*/

	public function add( $slug, $type, $args, $parent = '' ) {
		if ( ! $this->initialized ) {
			$type = strtolower( $type );
			if ( $this->is_valid_type( $type ) ) {
				if ( ! empty( $slug ) ) {
					$arrayname = $type . 's';
					$classname = '\\WPOD\\Components\\' . ucfirst( $type );

					if ( 'menu' == $type || ! empty( $parent ) ) {
						if ( ! $this->exists( $slug, $type, $parent ) ) {
							array_push( $this->$arrayname, new $classname( $slug, $args, $parent ) );

							return true;
						} else {
							\LaL_WP_Plugin_Util::get( 'options-definitely' )->doing_it_wrong( __METHOD__, sprintf( __( 'The %1$s %2$s already exists. If you want to modify it, please use the update method.', 'wpod' ), $type, $slug ), '1.0.0' );
						}
					} else {
						\LaL_WP_Plugin_Util::get( 'options-definitely' )->doing_it_wrong( __METHOD__, sprintf( __( 'The %1$s %2$s was not provided a parent.', 'wpod' ), $type, $slug ), '1.0.0' );
					}
				} else {
					\LaL_WP_Plugin_Util::get( 'options-definitely' )->doing_it_wrong( __METHOD__, __( 'No slug was provided.', 'wpod' ), '1.0.0' );
				}
			} else {
				\LaL_WP_Plugin_Util::get( 'options-definitely' )->doing_it_wrong( __METHOD__, sprintf( __( 'The type %s is not a valid type for a component.', 'wpod' ), $type ), '1.0.0' );
			}
		} else {
			\LaL_WP_Plugin_Util::get( 'options-definitely' )->doing_it_wrong( __METHOD__, __( 'The plugin is already initialized. You must perform every modifications either in the wpod filter or in the wpod_oo action.', 'wpod' ), '1.0.0' );
		}

		return false;
	}

	public function update( $slug, $type, $args, $parent = '' ) {
		if ( ! $this->initialized || 'menu' == $type ) {
			$type = strtolower( $type );
			if ( $this->is_valid_type( $type ) ) {
				if ( ! empty( $slug ) ) {
					$key = $this->exists( $slug, $type, $parent, true );
					if ( false !== $key )
					{
						$arrayname = $type . 's';
						$array = $this->$arrayname;
						$component = $array[ $key ];
						foreach( $args as $name => $value )
						{
							$component->$name = $value;
						}
						$array[ $key ] = $component;
						$this->$arrayname = $array;

						return true;
					} else {
						\LaL_WP_Plugin_Util::get( 'options-definitely' )->doing_it_wrong( __METHOD__, sprintf( __( 'The %1$s %2$s does not exist. You can instead use the add method to add it.', 'wpod' ), $type, $slug ), '1.0.0' );
					}
				} else {
					\LaL_WP_Plugin_Util::get( 'options-definitely' )->doing_it_wrong( __METHOD__, __( 'No slug was provided.', 'wpod' ), '1.0.0' );
				}
			} else {
				\LaL_WP_Plugin_Util::get( 'options-definitely' )->doing_it_wrong( __METHOD__, sprintf( __( 'The type %s is not a valid type for a component.', 'wpod' ), $type ), '1.0.0' );
			}
		} else {
			\LaL_WP_Plugin_Util::get( 'options-definitely' )->doing_it_wrong( __METHOD__, __( 'The plugin is already initialized. You must perform every modifications either in the wpod filter or in the wpod_oo action.', 'wpod' ), '1.0.0' );
		}

		return false;
	}

	public function delete( $slug, $type, $parent = '' ) {
		if ( ! $this->initialized ) {
			$type = strtolower( $type );
			if ( $this->is_valid_type( $type ) ) {
				if ( ! empty( $slug ) ) {
					$key = $this->exists( $slug, $type, $parent, true );

					if ( false !== $key ) {
						$arrayname = $type . 's';
						$array = $this->$arrayname;
						unset( $array[ $key ] );
						$this->$arrayname = $array;

						return true;
					} else {
						\LaL_WP_Plugin_Util::get( 'options-definitely' )->doing_it_wrong( __METHOD__, sprintf( __( 'The %1$s %2$s does not exist, so it does not need to be deleted.', 'wpod' ), $type, $slug ), '1.0.0' );
					}
				} else {
					\LaL_WP_Plugin_Util::get( 'options-definitely' )->doing_it_wrong( __METHOD__, __( 'No slug was provided.', 'wpod' ), '1.0.0' );
				}
			} else {
				\LaL_WP_Plugin_Util::get( 'options-definitely' )->doing_it_wrong( __METHOD__, sprintf( __( 'The type %s is not a valid type for a component.', 'wpod' ), $type ), '1.0.0' );
			}
		} else {
			\LaL_WP_Plugin_Util::get( 'options-definitely' )->doing_it_wrong( __METHOD__, __( 'The plugin is already initialized. You must perform every modifications either in the wpod filter or in the wpod_oo action.', 'wpod' ), '1.0.0' );
		}

		return false;
	}

	/*
	* ===================================================================================================
	* INTERNAL FUNCTIONS
	* The following functions should never be used outside the actual Options, Definitely plugin.
	* ===================================================================================================
	*/

	public function init() {
		if ( ! $this->initialized ) {
			$raw = array();

			// filter for the components array
			$raw = apply_filters( 'wpod', $raw );

			if ( is_array( $raw ) ) {
				foreach ( $raw as $menu_slug => $menu ) {
					$this->add( $menu_slug, 'menu', $menu );

					if ( isset( $menu['pages'] ) && is_array( $menu['pages'] ) ) {
						foreach ( $menu['pages'] as $page_slug => $page ) {
							$this->add( $page_slug, 'page', $page, $menu_slug );

							if ( isset( $page['tabs'] ) && is_array( $page['tabs'] ) ) {
								foreach ( $page['tabs'] as $tab_slug => $tab ) {
									$this->add( $tab_slug, 'tab', $tab, $page_slug );

									if ( isset( $tab['sections'] ) && is_array( $tab['sections'] ) ) {
										foreach ( $tab['sections'] as $section_slug => $section ) {
											$this->add( $section_slug, 'section', $section, $tab_slug );

											if ( isset( $section['fields'] ) && is_array( $section['fields'] ) ) {
												foreach ( $section['fields'] as $field_slug => $field ) {
													$this->add( $field_slug, 'field', $field, $section_slug );
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

			// action for the object-oriented alternative
			do_action( 'wpod_oo', $this );

			$this->initialized = true;
		} else {
			\LaL_WP_Plugin_Util::get( 'options-definitely' )->doing_it_wrong( __METHOD__, __( 'This function should never be called manually.', 'wpod' ), '1.0.0' );
		}
	}

	public function validate() {
		$types = $this->get_type_whitelist();

		foreach ( $types as $type ) {
			$arrayname = $type . 's';

			foreach ( $this->$arrayname as &$component ) {
				$component->validate();
			}
		}
	}

	public function query( $args = array(), $single = false ) {
		$args = wp_parse_args( $args, array(
		  'slug'			=> array(),
		  'type'			=> 'field',
		  'parent_slug'		=> array(),
		  'parent_type'		=> 'section',
		) );
		extract( $args );

		$results = false;

		if ( $this->is_valid_type( $type ) ) {
			$arrayname = $type . 's';

			if ( ! is_array( $slug ) ) {
				if ( ! empty( $slug ) ) {
					$slug = array( $slug );
				} else {
					$slug = array();
				}
			}

			if ( ! is_array( $parent_slug ) ) {
				if ( ! empty( $parent_slug ) ) {
					$parent_slug = array( $parent_slug );
				} else {
					$parent_slug = array();
				}
			}

			$results = $this->$arrayname;
			if ( count( $slug ) > 0 ) {
				$results = $this->query_by_slug( $slug, $results, $type );
			}
			if ( 'menu' != $type && $this->is_valid_type( $parent_type ) && count( $parent_slug ) > 0 && count( $results ) > 0 ) {
				$results = $this->query_by_parent( $parent_slug, $parent_type, $results, $type );
			}

			if ( $single ) {
				if ( count( $results ) > 0 ) {
					$results = $results[0];
				} else {
					$results = false;
				}
			}
		} else {
			\LaL_WP_Plugin_Util::get( 'options-definitely' )->doing_it_wrong( __METHOD__, sprintf( __( 'The type %s is not a valid type for a component.', 'wpod' ), $type ), '1.0.0' );
		}

		return $results;
	}

	private function query_by_slug( $slug, $haystack, $haystack_type ) {
		$results = array();

		foreach ( $haystack as $component ) {
			if ( in_array( $component->slug, $slug ) ) {
				$results[] = $component;
			}
		}

		return $results;
	}

	private function query_by_parent( $parent_slug, $parent_type, $haystack, $haystack_type ) {
		while ( ( $current_type = $this->get_next_inferior_type( $parent_type ) ) != $haystack_type ) {
			$current_arrayname = $current_type .'s';
			$current_haystack = $this->query_by_parent( $parent_slug, $parent_type, $this->$current_arrayname, $current_type );
			$parent_slug = array_map( 'wpod_component_to_slug', $current_haystack );
			$parent_type = $current_type;
		}

		$valid_haystack = array();

		foreach ( $haystack as $component ) {
			if ( in_array( $component->parent, $parent_slug ) ) {
				$valid_haystack[] = $component;
			}
		}

		return $valid_haystack;
	}

	private function exists( $slug, $type, $parent, $return_key = false ) {
		$types = $this->get_type_whitelist();
		$status = array_search( $type, $types );

		$results = array();

		if ( $status <= 2 ) {
			// for menus, pages and tabs the slug has to be globally unique
			$results = $this->query( array(
				'slug'			=> $slug,
				'type'			=> $type,
			) );
		} else {
			// for sections and fields the slug has to be unique inside its tab scope
			$parent_slug = $parent;

			$parent_type = $this->get_next_superior_type( $type );

			if ( 4 == $status ) {
				$parent_slug = $this->query( array(
					'slug'			=> $parent,
					'type'			=> $this->get_next_superior_type( $type ),
				), true );

				$parent_slug = $parent_slug->parent;

				$parent_type = $this->get_next_superior_type( $parent_type );
			}

			$results = $this->query( array(
				'slug'			=> $slug,
				'type'			=> $type,
				'parent_slug'	=> $parent_slug,
				'parent_type'	=> $parent_type,
			) );
		}

		if ( count( $results ) > 0 ) {
			if ( $return_key ) {
				$arrayname = $type . 's';
				if ( 4 == $status ) {
					$arrayname = $this->get_next_superior_type( $type ) . 's';
				}

				foreach ( $this->$arrayname as $key => $component ) {
					if ( $component->slug == $slug ) {
						if ( $status <= 2 || $component->parent == $parent ) {
							return $key;
						}
					}
				}
			}

			return true;
		}

		return false;
	}

	private function get_next_superior_type( $type ) {
		$types = $this->get_type_whitelist();

		$type_key = array_search( $type, $types );

		if ( false !== $type_key && $type_key > 0 ) {
			return $types[ $type_key - 1 ];
		}

		return false;
	}

	private function get_next_inferior_type( $type ) {
		$types = $this->get_type_whitelist();

		$type_key = array_search( $type, $types );

		if ( false !== $type_key && $type_key < 4 ) {
			return $types[ $type_key + 1 ];
		}

		return false;
	}

	private function is_valid_type( $type ) {
		return in_array( $type, $this->get_type_whitelist() );
	}

	public function get_type_whitelist() {
		return array( 'menu', 'page', 'tab', 'section', 'field' );
	}
}
