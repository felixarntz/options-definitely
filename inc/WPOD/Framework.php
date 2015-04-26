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
 * This class initializes the plugin.
 *
 * It also triggers the action and filter to hook into and contains all API functions of the plugin.
 *
 * @since 1.0.0
 */
class Framework {

	/**
	 * @since 1.0.0
	 * @var WPOD\Framework|null Holds the instance of this class.
	 */
	private static $instance = null;

	/**
	 * Gets the instance of this class. If it does not exist, it will be created.
	 *
	 * @since 1.0.0
	 * @return WPOD\Framework
	 */
	public static function instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * @since 1.0.0
	 * @var boolean Holds the status whether the initialization function has been called yet.
	 */
	private $initialization_triggered = false;

	/**
	 * @since 1.0.0
	 * @var boolean Holds the status whether the app has been initialized yet.
	 */
	private $initialized = false;

	/**
	 * @since 1.0.0
	 * @var array Holds all menu component objects added by using the plugin.
	 */
	private $menus = array();

	/**
	 * @since 1.0.0
	 * @var array Holds all page component objects added by using the plugin.
	 */
	private $pages = array();

	/**
	 * @since 1.0.0
	 * @var array Holds all tab component objects added by using the plugin.
	 */
	private $tabs = array();

	/**
	 * @since 1.0.0
	 * @var array Holds all section component objects added by using the plugin.
	 */
	private $sections = array();

	/**
	 * @since 1.0.0
	 * @var array Holds all field component objects added by using the plugin.
	 */
	private $fields = array();

	/**
	 * Class constructor.
	 *
	 * This will initialize the plugin on the 'after_setup_theme' action.
	 * If we are currently in the WordPress admin area, the WPOD\Admin class will be instantiated.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		if ( is_admin() ) {
			\WPOD\Admin::instance();
		}

		// use after_setup_theme action so it is initialized as soon as possible, but also so that both plugins and themes can use the filter/action
		add_action( 'after_setup_theme', array( $this, 'init' ), 1 );
		add_action( 'after_setup_theme', array( $this, 'validate' ), 2 );
	}

	/**
	 * Adds a component.
	 *
	 * This function should be used on the 'wpod_oo' action.
	 * It is also used internally if you use the 'wpod' filter instead.
	 *
	 * For more information on the arguments array, check the `get_defaults()` method of the respective component class.
	 *
	 * @since 1.0.0
	 * @param string $slug slug of the component to be added
	 * @param string $type type of the component (either 'menu', 'page', 'tab' or 'field')
	 * @param array $args additional arguments for the component
	 * @param string $parent parent slug of the component (only if it's not a 'menu')
	 * @return bool true if the component was added successfully, otherwise false
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

	/**
	 * Updates/modifies a component.
	 *
	 * This function should be used on the 'wpod_oo' action.
	 *
	 * For more information on the arguments array, check the `get_defaults()` method of the respective component class.
	 *
	 * @since 1.0.0
	 * @param string $slug slug of the component to be updated
	 * @param string $type type of the component to be updated (either 'menu', 'page', 'tab' or 'field')
	 * @param array $args arguments to update for the component
	 * @param string $parent parent slug of the component (only if it's not a 'menu')
	 * @return bool true if the component was updated successfully, otherwise false
	 */
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

	/**
	 * Deletes a component.
	 *
	 * This function should be used on the 'wpod_oo' action.
	 *
	 * @since 1.0.0
	 * @param string $slug slug of the component to be deleted
	 * @param string $type type of the component to be deleted (either 'menu', 'page', 'tab' or 'field')
	 * @param string $parent parent slug of the component (only if it's not a 'menu')
	 * @return bool true if the component was deleted successfully, otherwise false
	 */
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

	/**
	 * Initializes the plugin framework.
	 *
	 * This function adds all components to the plugin. It is executed on the 'after_setup_theme' hook with priority 1.
	 * There are two ways to add components: Either the filter 'wpod' can be used to specify a nested hiearchical array of components
	 * or, alternatively, the action 'wpod_oo' can be used to interact directly with this class.
	 *
	 * This function first applies the 'wpod' filter, then iterates through the array to add the components to the plugin.
	 * To do that it utilizes the add() method of this class which can also be utilized by the developer when using the 'wpod_oo' action.
	 * This action is triggered after the filter. This means that whatever is included in the filter will be added first.
	 *
	 * @internal
	 * @see WPOD\Framework::add()
	 * @since 1.0.0
	 */
	public function init() {
		if ( ! $this->initialization_triggered ) {
			$this->initialization_triggered = true;

			$raw = array();

			/**
			 * This filter can be utilized by the developer to add components to this plugin.
			 * The components must be nested in the hierarchical array provided by the filter.
			 *
			 * Read the plugin guide for more information.
			 *
			 * @since 1.0.0
			 * @param array the array of components (initially empty)
			 */
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

			/**
			 * This action can be utilized by the developer to add, update or delete components in the plugin.
			 * This action provides an alternative to using the filter with a huge array so that this class can be directly interacted with.
			 *
			 * If you need more flexibility or you want to work object-oriented, this action is the way to go.
			 *
			 * The fields supported by each component type are the same like when using the filter (except for the sub type field obviously).
			 *
			 * Read the plugin guide for more information.
			 *
			 * @since 1.0.0
			 * @param WPOD\Framework instance of this class
			 */
			do_action( 'wpod_oo', $this );

			$this->initialized = true;
		} else {
			\LaL_WP_Plugin_Util::get( 'options-definitely' )->doing_it_wrong( __METHOD__, __( 'This function should never be called manually.', 'wpod' ), '1.0.0' );
		}
	}

	/**
	 * Validates all components.
	 *
	 * This function checks if all components contain the required fields.
	 * It is executed after the plugin has been initialized, on the 'after_setup_theme' hook with priority 2.
	 *
	 * @internal
	 * @since 1.0.0
	 */
	public function validate() {
		$types = $this->get_type_whitelist();

		foreach ( $types as $type ) {
			$arrayname = $type . 's';

			foreach ( $this->$arrayname as &$component ) {
				$component->validate();
			}
		}
	}

	/**
	 * Queries one or more components.
	 *
	 * This function is used to query components. It will either return an array of component objects or a single component object.
	 * The function is only used to get a component, so it is recommended to be used internally, only by the plugin itself.
	 * However, there might be use-cases where another plugin or a theme might need this function.
	 *
	 * The function should be used with an array of arguments being provided as the $args parameter.
	 * This array can have the following fields:
	 * - 'slug': a single component slug or an array of component slugs to look for (optional)
	 * - 'type': the component type to look for (must be either 'menu', 'page', 'tab', 'section' or 'field', default is 'field')
	 * - 'parent_slug': a single parent component slug or an array of parent component slugs; if this field is used, the function will only look for components which are nested in these parent components (optional)
	 * - 'parent_type': the parent component type to look for (must be either 'menu', 'page', 'tab', 'section' or 'field', default is 'section')
	 *
	 * Note that you can either search for a specific slug OR for sub components of a parent slug.
	 * The 'slug' field has a higher priority there, so if you specify a slug, the parent fields will be ignored.
	 *
	 * Also be aware that this function will always return components of one specific type.
	 * It cannot be used to get components of different types at the same time.
	 *
	 * @since 1.0.0
	 * @param array $args an array of query arguments (for details read the function description above)
	 * @param boolean $single if this is set to true, the function will always return a single object only (or false if the query did not produce any results)
	 * @return WPOD\Components\ComponentBase|array|false return value depends on the parameters
	 */
	public function query( $args = array(), $single = false ) {
		$args = \LaL_WP_Plugin_Util::parse_args( $args, array(
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

	/**
	 * Extracts components of a specific type and slug from the array of all components of this type.
	 *
	 * The function is used internally by the query() method of this class.
	 *
	 * @internal
	 * @see WPOD\Framework::query()
	 * @since 1.0.0
	 * @param array $slug array of slugs to look for
	 * @param array $haystack array of components to find the slugs in
	 * @param string $haystack_type type of the components array
	 * @return array components with the slug or an emtpy array if none were found
	 */
	private function query_by_slug( $slug, $haystack, $haystack_type ) {
		$results = array();

		foreach ( $haystack as $component ) {
			if ( in_array( $component->slug, $slug ) ) {
				$results[] = $component;
			}
		}

		return $results;
	}

	/**
	 * Queries sub components of one or more specific parent slugs and a specific type from the array of all components of this type.
	 *
	 * The function is used internally by the query() method of this class.
	 *
	 * @internal
	 * @see WPOD\Framework::query()
	 * @since 1.0.0
	 * @param array $parent_slug array of parent slugs to query components for
	 * @param string $parent_type type of the parent component slugs
	 * @param array $haystack array of components to find the components in
	 * @param string $haystack_type type of the components array
	 * @return array sub components of this parent slug and of the specified type or an empty array if none were found
	 */
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

	/**
	 * Checks if a specific component exists or not.
	 *
	 * This function uses the query() method to find out the status.
	 *
	 * @internal
	 * @see WPOD\Framework::query()
	 * @since 1.0.0
	 * @param string $slug the slug of the component to check
	 * @param string $type the type of the component to check; must be either 'menu', 'page', 'tab', 'section' or 'field'
	 * @param string $parent the slug of the components parent (only used if the searched type is 'section' or 'field', otherwise an empty string should be provided)
	 * @param boolean $return_key if true, the function will return the slug of the component if found, otherwise it will return true if found
	 * @return boolean|string if the component exists, either true or the component slug will be returned (depending on $return_key parameter); if it is not found, false is returned
	 */
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

	/**
	 * Gets the next superior type in the hierarchy for a specific type.
	 *
	 * @internal
	 * @since 1.0.0
	 * @param string $type type to get the next superior type for
	 * @return string|boolean either returns the superior type or false if there is no superior type or if the specified type was invalid
	 */
	private function get_next_superior_type( $type ) {
		$types = $this->get_type_whitelist();

		$type_key = array_search( $type, $types );

		if ( false !== $type_key && $type_key > 0 ) {
			return $types[ $type_key - 1 ];
		}

		return false;
	}

	/**
	 * Gets the next inferior type in the hierarchy for a specific type.
	 *
	 * @internal
	 * @since 1.0.0
	 * @param string $type type to get the next inferior type for
	 * @return string|boolean either returns the inferior type or false if there is no inferior type or if the specified type was invalid
	 */
	private function get_next_inferior_type( $type ) {
		$types = $this->get_type_whitelist();

		$type_key = array_search( $type, $types );

		if ( false !== $type_key && $type_key < 4 ) {
			return $types[ $type_key + 1 ];
		}

		return false;
	}

	/**
	 * Checks if a type is valid.
	 *
	 * @internal
	 * @since 1.0.0
	 * @param string $type the type to check if it is a valid one
	 * @return boolean true if the type is valid, otherwise false
	 */
	private function is_valid_type( $type ) {
		return in_array( $type, $this->get_type_whitelist() );
	}

	/**
	 * Returns the array of valid component types, in their hierarchical order.
	 *
	 * The types are 'menu', 'page', 'tab', 'section' and 'field'.
	 *
	 * @internal
	 * @since 1.0.0
	 * @return array the array of valid types
	 */
	public function get_type_whitelist() {
		return array( 'menu', 'page', 'tab', 'section', 'field' );
	}
}
