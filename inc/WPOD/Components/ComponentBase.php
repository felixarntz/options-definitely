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
 * Abstract class for a component.
 *
 * @internal
 * @since 1.0.0
 */
abstract class ComponentBase {

	/**
	 * @since 1.0.0
	 * @var string Holds the slug of this component.
	 */
	protected $slug = '';

	/**
	 * @since 1.0.0
	 * @var string Holds the slug of this component's parent (or an empty string if there is no parent).
	 */
	protected $parent = '';

	/**
	 * @since 1.0.0
	 * @var array Holds the arguments array for this class.
	 */
	protected $args = array();

	/**
	 * Class constructor.
	 *
	 * It will assign the parameters to the class variables.
	 * The arguments array should contain keys and values according to the component's default values.
	 *
	 * @since 1.0.0
	 * @see WPOD\Components\ComponentBase::get_defaults()
	 * @param string $slug slug of this component
	 * @param array $args array of arguments
	 * @param string $parent slug of this component's parent component or an empty string
	 */
	public function __construct( $slug, $args, $parent = '' ) {
		$this->slug = $slug;
		$this->parent = $parent;
		$this->args = $args;
	}

	/**
	 * Magic set function.
	 *
	 * It checks for the specified $property in the following way:
	 * 1. Is there a class method to set this property?
	 * 2. Is there a class property of this name?
	 * 3. Is there a field of this name in the arguments array?
	 *
	 * @since 1.0.0
	 * @param string $property the property to set
	 * @param mixed $value the value to assign
	 */
	public function __set( $property, $value ) {
		if ( method_exists( $this, $method = 'set_' . $property ) ) {
			$this->$method( $value );
		} elseif ( property_exists( $this, $property ) ) {
			$this->$property = $value;
		} elseif ( isset( $this->args[ $property ] ) ) {
			$this->args[ $property ] = $value;
		}
	}

	/**
	 * Magic get function.
	 *
	 * It checks for the specified $property in the following way:
	 * 1. Is there a class method to set this property?
	 * 2. Is there a class property of this name?
	 * 3. Is there a field of this name in the arguments array?
	 *
	 * @since 1.0.0
	 * @param string $property the property to get the value for
	 * @return mixed value of the property or a boolean false if it does not exist
	 */
	public function __get( $property ) {
		if ( method_exists( $this, $method = 'get_' . $property ) ) {
			return $this->$method();
		} elseif ( property_exists( $this, $property ) ) {
			return $this->$property;
		} elseif ( isset( $this->args[ $property ] ) ) {
			return $this->args[ $property ];
		}

		return null;
	}

	/**
	 * Handles a default validation for the class' arguments array.
	 *
	 * This method is recommended to be overridden in the sub class.
	 *
	 * @since 1.0.0
	 */
	public function validate() {
		$this->args = \LaL_WP_Plugin_Util::parse_args( $this->args, $this->get_defaults(), true );
		$types = \WPOD\Framework::instance()->get_type_whitelist();

		foreach ( $types as $type ) {
			$t = $type . 's';
			if ( isset( $this->args[ $t ] ) ) {
				unset( $this->args[ $t ] );
			}
		}
	}

	/**
	 * Abstract function to return the keys of the arguments array and their default values.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	protected abstract function get_defaults();
}
