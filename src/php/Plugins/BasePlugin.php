<?php

namespace Arts\ElementorExtension\Plugins;

use Arts\Base\Plugins\BasePlugin as ArtsBasePlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Elementor-aware variant of the framework BasePlugin.
 *
 * @template TManagers of \Arts\Base\Containers\ManagersContainer
 * @extends ArtsBasePlugin<TManagers>
 *
 * @package Arts\ElementorExtension\Plugins
 */
abstract class BasePlugin extends ArtsBasePlugin {
	/**
	 * Boots the shared ArtsElementorExtension Plugin singleton alongside the consumer plugin.
	 */
	protected function do_run(): void {
		\Arts\ElementorExtension\Plugin::instance();
	}
}
