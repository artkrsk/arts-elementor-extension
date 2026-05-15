<?php

namespace Arts\ElementorExtension\Managers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Elementor\Elements_Manager;

/**
 * @package Arts\ElementorExtension\Managers
 */
class Categories extends BaseManager {
	/**
	 * Widget category definitions, each as { name, title, icon? }.
	 *
	 * @var array<int, array{name: string, title: string, icon?: string}>
	 */
	public $categories = array();

	protected function apply_filters(): void {
		/**
		 * @var array<int, array{name: string, title: string, icon?: string}> $filtered
		 */
		$filtered = apply_filters( 'arts/elementor_extension/widgets/categories', $this->categories );
		if ( is_array( $filtered ) ) {
			$this->categories = $filtered;
		}
	}

	/**
	 * Hooked on `elementor/elements/categories_registered`. Adds every configured
	 * category through Elements_Manager::add_category() and fires
	 * arts/elementor_extension/widgets/categories_registered.
	 *
	 * @param Elements_Manager $elements_manager
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

		do_action( 'arts/elementor_extension/widgets/categories_registered', $this->categories, $this );
	}
}
