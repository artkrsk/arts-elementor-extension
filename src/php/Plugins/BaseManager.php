<?php

namespace Arts\ElementorExtension\Plugins;

use Arts\Base\Managers\BaseManager as ArtsBaseManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Elementor-aware variant of the framework BaseManager.
 *
 * @package Arts\ElementorExtension\Plugins
 */
abstract class BaseManager extends ArtsBaseManager {
}
