<?php

namespace Arts\ElementorExtension\Widgets\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

trait Preloads {
	/**
	 * Wires this widget's get_preload_* maps into the ArtsOptimizer filter chain
	 * for assets, modules, images and prefetch URLs.
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
	 * Filter callback that merges get_preload_assets_map() into the assets map.
	 *
	 * @param array<string, mixed> $map
	 * @return array<string, mixed>
	 */
	public function add_preload_assets( array $map ): array {
		return array_merge( $map, $this->get_preload_assets_map() );
	}

	/**
	 * Filter callback that merges get_preload_images_map() into the images map.
	 *
	 * @param array<string, mixed> $map
	 * @return array<string, mixed>
	 */
	public function add_preload_images( array $map ): array {
		return array_merge( $map, $this->get_preload_images_map() );
	}

	/**
	 * Filter callback that merges get_preload_modules_map() into the modules map.
	 *
	 * @param array<string, mixed> $map
	 * @return array<string, mixed>
	 */
	public function add_preload_modules( array $map ): array {
		return array_merge( $map, $this->get_preload_modules_map() );
	}

	/**
	 * Filter callback that merges get_prefetch_map() into the prefetch map.
	 *
	 * @param array<string, mixed> $map
	 * @return array<string, mixed>
	 */
	public function add_prefetch( array $map ): array {
		return array_merge( $map, $this->get_prefetch_map() );
	}

	/**
	 * Builds the preload assets map from the active skin's get_frontend_files()
	 * if it exposes one. Returns an empty map when no skin is active or the
	 * skin doesn't define frontend files.
	 *
	 * @return array<string, mixed>
	 */
	protected function get_preload_assets_map(): array {
		$skin = $this->get_current_skin();
		/** @var array<string, mixed> $map */
		$map = array();

		if ( $skin && method_exists( $skin, 'get_frontend_files' ) ) {
			$assets = $skin->get_frontend_files();

			if ( is_array( $assets ) ) {
				foreach ( $assets as $asset ) {
					if ( is_array( $asset ) &&
						isset( $asset['id'] ) &&
						( is_string( $asset['id'] ) || is_int( $asset['id'] ) ) &&
						isset( $asset['src'] ) ) {
						$map[ $asset['id'] ] = $asset['src'];
					}
				}
			}
		}

		/** @var array<string, mixed> $map */
		return $map;
	}

	/**
	 * Extension point — subclasses override to declare preloaded images.
	 *
	 * @return array<string, mixed>
	 */
	protected function get_preload_images_map(): array {
		return array();
	}

	/**
	 * Extension point — subclasses override to declare preloaded JS modules.
	 *
	 * @return array<string, mixed>
	 */
	protected function get_preload_modules_map(): array {
		return array();
	}

	/**
	 * Default prefetch map: when the widget exposes get_posts_to_display() and
	 * the first post carries a non-empty `video.url`, prefetch that URL keyed by
	 * "Widget_Video_<widget-id>".
	 *
	 * @return array<string, mixed>
	 */
	protected function get_prefetch_map(): array {
		$map = array();

		if ( method_exists( $this, 'get_posts_to_display' ) ) {
			$posts = $this->get_posts_to_display();

			if ( is_array( $posts ) && ! empty( $posts ) &&
				is_array( $posts[0] ) &&
				isset( $posts[0]['video'] ) &&
				is_array( $posts[0]['video'] ) &&
				array_key_exists( 'url', $posts[0]['video'] ) &&
				! empty( $posts[0]['video']['url'] ) ) {
				$map[ 'Widget_Video_' . $this->get_id() ] = $posts[0]['video']['url'];
			}
		}

		return $map;
	}
}
