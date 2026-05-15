<?php

namespace Arts\ElementorExtension\Managers;

use Arts\Base\Managers\BaseManager as ArtsBaseManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Base for managers in this package. Overrides the framework init() to insert an
 * apply_filters() step between property setup and manager wiring, and exposes
 * a $require_files list that subclasses can load lazily via require_files().
 *
 * @property \Arts\ElementorExtension\Containers\ManagersContainer $managers
 *
 * @package Arts\ElementorExtension\Managers
 */
abstract class BaseManager extends ArtsBaseManager {
	/**
	 * Optional files the manager require_once()s on demand.
	 *
	 * @var array<int, string>
	 */
	protected $require_files = array();

	/**
	 * Replaces the framework init() (does not call parent::init()): runs
	 * init_properties(), then apply_filters(), then add_managers().
	 *
	 * @param \Arts\Base\Containers\ManagersContainer $managers
	 * @return void
	 */
	public function init( $managers ): void {
		$this->init_properties();
		$this->apply_filters();
		$this->add_managers( $managers );
	}

	/**
	 * Extension point for filtering class properties after init_properties().
	 * Override in subclasses; the default is a no-op.
	 *
	 * @return void
	 */
	protected function apply_filters(): void {
	}

	/**
	 * require_once()s every existing file declared in $require_files. Safe to
	 * call multiple times — require_once handles deduplication.
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
