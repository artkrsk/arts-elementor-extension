<?php

namespace Arts\ElementorExtension\Plugins;

use Arts\Base\Containers\ManagersContainer;
use Arts\Base\Plugins\BasePlugin as ArtsBasePlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Elementor-aware variant of the framework BasePlugin.
 *
 * The constraint is written against the imported name, not a leading-backslash
 * FQN: Strauss rewrites `use` statements and @param/@property/@var/@return
 * FQNs, but leaves `@template … of \FQN` untouched — which in a prefixed build
 * would point the constraint at a class that doesn't exist.
 *
 * @template TManagers of ManagersContainer
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
