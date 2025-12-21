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
 * Typed managers container for ArtsElementorExtension.
 *
 * Provides type-safe access to all managers.
 *
 * @package Arts\ElementorExtension\Containers
 */
class ManagersContainer extends BaseManagersContainer {
	public Categories $categories;
	public Editor $editor;
	public Tabs $tabs;
	public Widgets $widgets;
}
