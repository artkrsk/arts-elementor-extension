<?php

namespace Arts\ElementorExtension\Plugins;

use Arts\Base\Plugins\BasePlugin as ArtsBasePlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract base class for Elementor-aware plugins.
 *
 * Extends the framework-agnostic BasePlugin with Elementor integration.
 *
 * @template TManagers of \Arts\Base\Containers\ManagersContainer
 * @extends ArtsBasePlugin<TManagers>
 *
 * @package Arts\ElementorExtension\Plugins
 */
abstract class BasePlugin extends ArtsBasePlugin {
	/**
	 * Initializes Elementor Extension Plugin after run.
	 */
	protected function do_run(): void {
		\Arts\ElementorExtension\Plugin::instance();
	}
}
