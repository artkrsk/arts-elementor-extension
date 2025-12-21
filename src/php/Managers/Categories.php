<?php

namespace Arts\ElementorExtension\Managers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Elementor\Elements_Manager;

/**
 * Class Categories
 *
 * @package Arts\ElementorExtension\Managers
 */
class Categories extends BaseManager {
	/**
	 * The categories for the widgets.
	 *
	 * @var array
	 */
	public $categories = array();

	protected function apply_filters() {
		$this->categories = apply_filters( 'arts/elementor_extension/widgets/categories', $this->categories );
	}

	/**
	 * Register the categories.
	 *
	 * @param Elements_Manager $elements_manager The elements manager.
	 *
	 * @return void
	 */
	public function register( Elements_Manager $elements_manager ) {
		if ( ! is_array( $this->categories ) || empty( $this->categories ) ) {
			return;
		}

		foreach ( $this->categories as $category ) {
			if ( ! isset( $category['name'] ) || ! isset( $category['title'] ) ) {
				continue;
			}

			$category_name       = $category['name'];
			$category_properties = array(
				'title' => $category['title'],
			);

			if ( isset( $category['icon'] ) ) {
				$category_properties['icon'] = $category['icon'];
			}

			$elements_manager->add_category( $category_name, $category_properties );
		}

		// Allow developers to hook into the categories registration event.
		do_action( 'arts/elementor_extension/widgets/categories_registered', $this->categories, $this );
	}
}
