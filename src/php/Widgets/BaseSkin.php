<?php

namespace Arts\ElementorExtension\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use \Elementor\Skin_Base;
use \Arts\ElementorExtension\Widgets\BaseWidget;

abstract class BaseSkin extends Skin_Base {
	/**
	 * The parent widget instance.
	 *
	 * @var BaseWidget
	 */
	protected $parent;

	abstract public function render_skin();

	/**
	 * Filter the preload assets map from a skin.
	 *
	 * @param array $map The existing assets map.
	 * @return array The modified assets map with preload assets.
	 */
	public function add_filter_preload_assets_map( $map ) {
		return $map;
	}

	/**
	 * Filter the preload images map from a skin.
	 *
	 * @param array $map The existing images map.
	 * @return array The modified images map with preload images.
	 */
	public function add_filter_preload_images_map( $map ) {
		return $map;
	}

	/**
	 * Filter the preload JS modules map from a skin.
	 *
	 * @param array $map The existing modules map.
	 * @return array The modified modules map with preload modules.
	 */
	public function add_filter_preload_modules_map( $map ) {
		return $map;
	}

	/**
	 * Filter the prefetch URLs map from a skin.
	 *
	 * @param array $map The existing prefetch map.
	 * @return array The modified prefetch map with prefetch URLs.
	 */
	public function add_filter_prefetch_map( $map ) {
		return $map;
	}

	/**
	 * Retrieves settings for display.
	 * Proxy to the parent widget's `get_settings` method.
	 *
	 * @param array $selected_settings Array of selected settings.
	 * @return array Array of settings for display.
	 */
	public function get_settings_for_display( $selected_settings = array() ) {
		$settings = array();

		foreach ( $selected_settings as $setting ) {
			$control_id           = $this->get_control_id( $setting );
			$settings[ $setting ] = $this->parent->get_settings( $control_id );
		}

		return $settings;
	}

	/**
	 * Retrieve the value of a specified option.
	 * Proxy to the parent widget's `get_option_value` method.
	 *
	 * @param string $option The option name.
	 * @param string $group_control_prefix Optional. The prefix for group control. Default is ''.
	 * @param bool   $return_size Optional. Whether to return the size if available. Default is true.
	 *
	 * @return mixed The option value or false if not found.
	 */
	public function get_option_value( $option, $group_control_prefix = '', $return_size = true ) {
		return $this->parent->get_option_value( $option, $group_control_prefix, $return_size );
	}

	/**
	 * Retrieve the skin setting value.
	 * Proxy to the parent widget's `get_settings` method.
	 *
	 * @param string $setting The setting name.
	 * @return mixed The setting value.
	 */
	public function get_skin_setting( $setting ) {
		$control_id = $this->get_control_id( $setting );

		return $this->parent->get_settings( $control_id );
	}
}
