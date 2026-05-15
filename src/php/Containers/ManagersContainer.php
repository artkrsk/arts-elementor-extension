<?php

namespace Arts\ElementorExtension\Containers;

use Arts\Base\Containers\ManagersContainer as BaseManagersContainer;
use Arts\ElementorExtension\Managers\Categories;
use Arts\ElementorExtension\Managers\Editor;
use Arts\ElementorExtension\Managers\Tabs;
use Arts\ElementorExtension\Managers\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Typed managers container — exists purely to give static analyzers and IDEs
 * the concrete property types for each manager attached at runtime.
 *
 * @property Categories $categories
 * @property Editor $editor
 * @property Tabs $tabs
 * @property Widgets $widgets
 *
 * @package Arts\ElementorExtension\Containers
 */
class ManagersContainer extends BaseManagersContainer {
}
