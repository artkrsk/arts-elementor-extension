<?php

namespace Arts\ElementorExtension\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Elementor\Skin_Base;
use Arts\ElementorExtension\Widgets\BaseWidget;

abstract class BaseSkin extends Skin_Base {
	/**
	 * Narrows the inherited Skin_Base::$parent property type to BaseWidget for
	 * static analysis. Subclasses must be attached to a BaseWidget descendant.
	 *
	 * @var BaseWidget
	 */
	protected $parent;

	/**
	 * @param \Elementor\Widget_Base $parent The parent widget.
	 */
	public function __construct( \Elementor\Widget_Base $parent ) {
		parent::__construct( $parent );
	}

	abstract public function render_skin(): void;

	/**
	 * Extension point for the `arts/optimizer/preloads/assets_map` filter.
	 *
	 * @param array<string, mixed> $map Existing assets map.
	 * @return array<string, mixed>
	 */
	public function add_filter_preload_assets_map( array $map ): array {
		return $map;
	}

	/**
	 * Extension point for the `arts/optimizer/preloads/images_map` filter.
	 *
	 * @param array<string, mixed> $map Existing images map.
	 * @return array<string, mixed>
	 */
	public function add_filter_preload_images_map( array $map ): array {
		return $map;
	}

	/**
	 * Extension point for the `arts/optimizer/preloads/modules_map` filter.
	 *
	 * @param array<string, mixed> $map Existing modules map.
	 * @return array<string, mixed>
	 */
	public function add_filter_preload_modules_map( array $map ): array {
		return $map;
	}

	/**
	 * Extension point for the `arts/optimizer/preloads/prefetch_map` filter.
	 *
	 * @param array<string, mixed> $map Existing prefetch map.
	 * @return array<string, mixed>
	 */
	public function add_filter_prefetch_map( array $map ): array {
		return $map;
	}

	/**
	 * Returns the parent widget's display values for the listed skin-prefixed
	 * settings. Keys in the returned array are the original (unprefixed) names.
	 *
	 * @param array<int, string> $selected_settings
	 * @return array<string, mixed>
	 */
	public function get_settings_for_display( array $selected_settings = array() ): array {
		$settings = array();

		foreach ( $selected_settings as $setting ) {
			$control_id           = $this->get_control_id( $setting );
			$settings[ $setting ] = $this->parent->get_settings( $control_id );
		}

		return $settings;
	}

	/**
	 * Proxy to BaseWidget::get_option_value() on the attached parent widget.
	 *
	 * @param string $option              Setting key.
	 * @param string $group_control_prefix Prefix prepended to $option (for group controls).
	 * @param bool   $return_size         When true, unwrap `size` from array-shaped values.
	 *
	 * @return mixed
	 */
	public function get_option_value( $option, $group_control_prefix = '', $return_size = true ) {
		return $this->parent->get_option_value( $option, $group_control_prefix, $return_size );
	}

	/**
	 * Reads a single skin-prefixed setting from the parent widget.
	 *
	 * @param string $setting Unprefixed setting key (gets resolved via get_control_id()).
	 * @return mixed
	 */
	public function get_skin_setting( $setting ) {
		$control_id = $this->get_control_id( $setting );

		return $this->parent->get_settings( $control_id );
	}
}
