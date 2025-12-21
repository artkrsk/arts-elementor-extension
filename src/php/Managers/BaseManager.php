<?php

namespace Arts\ElementorExtension\Managers;

use Arts\Base\Managers\BaseManager as ArtsBaseManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Abstract base class for managers.
 * Extends Arts\Base for type safety and code reuse.
 *
 * @property \Arts\ElementorExtension\Containers\ManagersContainer $managers
 *
 * @package Arts\ElementorExtension\Managers
 */
abstract class BaseManager extends ArtsBaseManager {
	/**
	 * Files to require manually for the manager.
	 *
	 * @var array<int, string>
	 */
	protected $require_files = array();

	/**
	 * Initialize the manager with other managers.
	 * Adds apply_filters hook before parent initialization.
	 *
	 * @param \Arts\Base\Containers\ManagersContainer $managers Other managers.
	 * @return void
	 */
	public function init( $managers ): void {
		$this->init_properties();
		$this->apply_filters();
		$this->add_managers( $managers );
	}

	/**
	 * Apply filters to modify the values of class properties.
	 * Can be overridden in child classes.
	 *
	 * @return void
	 */
	protected function apply_filters(): void {
		// Override in child classes if needed.
	}

	/**
	 * Manually requires files specified in the $require_files property.
	 *
	 * @return void
	 */
	public function require_files(): void {
		if ( ! is_array( $this->require_files ) || empty( $this->require_files ) ) {
			return;
		}

		foreach ( $this->require_files as $file ) {
			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
	}
}
