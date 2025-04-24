<?php

namespace Arts\ElementorExtension\Widgets\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

trait Preloads {
	/**
	 * Add preload links from the widget for improving the performance.
	 * Hooks into filters of `ArtsOptimizer` plugin.
	 *
	 * @return void
	 */
	public function add_preloads() {
		add_filter( 'arts/optimizer/preloads/assets_map', array( $this, 'add_preload_assets' ) );
		add_filter( 'arts/optimizer/preloads/modules_map', array( $this, 'add_preload_modules' ) );
		add_filter( 'arts/optimizer/preloads/images_map', array( $this, 'add_preload_images' ) );
		add_filter( 'arts/optimizer/preloads/prefetch_map', array( $this, 'add_prefetch' ) );
	}

	/**
	 * Add preload assets to the given map.
	 *
	 * @param array $map The existing assets map.
	 * @return array The modified assets map with preload assets.
	 */
	public function add_preload_assets( $map ) {
		return array_merge( $map, $this->get_preload_assets_map() );
	}

	/**
	 * Add preload images to the given map.
	 *
	 * @param array $map The existing images map.
	 * @return array The modified images map with preload images.
	 */
	public function add_preload_images( $map ) {
		return array_merge( $map, $this->get_preload_images_map() );
	}

	/**
	 * Add preload JS modules to the given map.
	 *
	 * @param array $map The existing modules map.
	 * @return array The modified modules map with preload modules.
	 */
	public function add_preload_modules( $map ) {
		return array_merge( $map, $this->get_preload_modules_map() );
	}

	/**
	 * Add prefetch URLs to the given map.
	 *
	 * @param array $map The existing prefetch map.
	 * @return array The modified prefetch map with prefetch URLs.
	 */
	public function add_prefetch( $map ) {
		return array_merge( $map, $this->get_prefetch_map() );
	}

	/**
	 * Get the map of preload assets.
	 *
	 * @return array The preload assets map.
	 */
	protected function get_preload_assets_map() {
		/**
		 * @var \Arts\ElementorExtension\Widgets\BaseSkinComponent $skin
		 */
		$skin = $this->get_current_skin();
		$map  = array();

		if ( $skin && method_exists( $skin, 'get_frontend_files' ) ) {
			$assets = $skin->get_frontend_files();

			foreach ( $assets as $asset ) {
				if ( isset( $asset['id'] ) && isset( $asset['src'] ) ) {
					$map[ $asset['id'] ] = $asset['src'];
				}
			}
		}

		return $map;
	}

	/**
	 * Get the map of preload images.
	 *
	 * @return array The preload images map.
	 */
	protected function get_preload_images_map() {
		return array();
	}

	/**
	 * Get the map of preload JS modules.
	 *
	 * @return array The preload modules map.
	 */
	protected function get_preload_modules_map() {
		return array();
	}

	/**
	 * Get the map of prefetch URLs.
	 *
	 * @return array The prefetch map.
	 */
	protected function get_prefetch_map() {
		$map = array();

		if ( method_exists( $this, 'get_posts_to_display' ) ) {
			$posts = $this->get_posts_to_display();

			if ( ! empty( $posts ) && $posts[0] && $posts[0]['video'] && array_key_exists( 'url', $posts[0]['video'] ) && $posts[0]['video']['url'] ) {
				$map[ 'Widget_Video_' . $this->get_id() ] = $posts[0]['video']['url'];
			}
		}

		return $map;
	}
}
