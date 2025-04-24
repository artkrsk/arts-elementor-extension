<?php

namespace Arts\ElementorExtension\Managers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Abstract class BaseManager
 *
 * Serves as a base for managers of the plugin.
 *
 * @package Arts\ElementorExtension\Managers
 */
abstract class BaseManager {
	/**
	 * Arguments for the manager.
	 *
	 * @var array
	 */
	protected $args;

	/**
	 * Array of text strings used by the manager.
	 *
	 * @var array
	 */
	protected $strings;

	/**
	 * Other managers used by the current manager.
	 *
	 * @var \stdClass
	 */
	protected $managers;

	/**
	 * Files to require manually for the manager.
	 *
	 * @var array
	 */
	protected $require_files = array();

	/**
	 * Constructor for the BaseManager class.
	 *
	 * @param array $args    Arguments for the manager.
	 * @param array $strings Array of text strings used by the manager.
	 */
	public function __construct( $args = array(), $strings = array() ) {
		$this->args    = $args;
		$this->strings = $strings;
	}

	/**
	 * Initialize the manager with other managers.
	 *
	 * @param \stdClass $managers Other managers used by the current manager.
	 */
	public function init( $managers ) {
		$this->apply_filters();
		$this->add_managers( $managers );
	}

	/**
	 * Apply filters to modify the values of class properties.
	 *
	 * This method should be implemented in the child class to apply filters to modify the values of class properties.
	 *
	 * @return void
	 */
	protected function apply_filters() {
		// This method should be implemented in the child class.
	}

	/**
	 * Manually requires files specified in the $require_files property.
	 * It should be called on the appropriate action to ensure
	 * that all necessary files are loaded.
	 *
	 * @return void
	 */
	public function require_files() {
		if ( ! is_array( $this->require_files ) || empty( $this->require_files ) ) {
			return;
		}

		foreach ( $this->require_files as $file ) {
			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
	}

	/**
	 * Add other managers to the current manager.
	 *
	 * @param \stdClass $managers Other managers used by the current manager.
	 */
	protected function add_managers( $managers ) {
		if ( ! isset( $this->managers ) ) {
			$this->managers = new \stdClass();
		}

		foreach ( $managers as $key => $manager ) {
			// Prevent adding self to the managers property to avoid infinite loop.
			if ( $manager !== $this ) {
				$this->managers->$key = $manager;
			}
		}

		return $this;
	}

	/**
	 * Initialize a property from the args array.
	 *
	 * This method checks if the specified property exists in the args array and initializes
	 * the property with the corresponding value from the args array if it exists.
	 *
	 * @param string $property The name of the property to initialize.
	 *
	 * @return BaseManager Returns the current instance for method chaining.
	 */
	protected function init_property( $property ) {
		if ( isset( $this->args[ $property ] ) ) {
			$this->$property = $this->args[ $property ];
		}

		return $this;
	}

	/**
	 * Initialize an array property from the args array.
	 *
	 * This method checks if the specified property exists in the args array, is an array,
	 * and is not empty. If all conditions are met, it initializes the property with the
	 * corresponding value from the args array.
	 *
	 * @param string $property The name of the property to initialize.
	 *
	 * @return BaseManager Returns the current instance for method chaining.
	 */
	protected function init_array_property( $property ) {
		if ( isset( $this->args[ $property ] ) && is_array( $this->args[ $property ] ) && ! empty( $this->args[ $property ] ) ) {
			$this->$property = $this->args[ $property ];
		}

		return $this;
	}

	/**
	 * Helper method to instantiate a class.
	 *
	 * @param string $class The class to instantiate.
	 *
	 * @return object The instantiated class.
	 */
	protected function get_class_instance( $class ) {
		try {
			$reflection = new \ReflectionClass( $class );
			return $reflection->newInstanceArgs();
		} catch ( \ReflectionException $e ) {
			return new $class();
		}
	}
}
