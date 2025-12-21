<?php

namespace Arts\ElementorExtension\Plugins;

use Arts\Base\Managers\BaseManager as ArtsBaseManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract base class for plugin managers.
 *
 * Extends the framework-agnostic BaseManager for use with Elementor plugins.
 *
 * @package Arts\ElementorExtension\Plugins
 */
abstract class BaseManager extends ArtsBaseManager {
}
