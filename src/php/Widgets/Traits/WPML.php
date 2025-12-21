<?php

namespace Arts\ElementorExtension\Widgets\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

trait WPML {
	/**
	 * Add ability to translate the widget text fields using WPML plugin.
	 *
	 * @return void
	 */
	public function add_wpml_compatibility() {
		add_filter( 'wpml_elementor_widgets_to_translate', array( $this, 'filter_wpml_widgets_to_translate' ) );
	}

	/**
	 * Filter the Elementor widgets for translation in WPML.
	 *
	 * @param array<string, mixed> $widgets The existing widgets.
	 * @return array<string, mixed> The modified widgets with translatable fields and integration class.
	 */
	public function filter_wpml_widgets_to_translate( array $widgets ): array {
		$name = $this->get_name();

		$widgets[ $name ] = array(
			'conditions'        => array( 'widgetType' => $name ),
			'fields'            => $this->wpml_get_translatable_fields(),
			'integration-class' => $this->wpml_get_integration_class_name(),
		);

		return $widgets;
	}

	/**
	 * Get an array of translatable widget fields.
	 *
	 * @return array<int, array<string, mixed>> An array of translatable fields.
	 */
	protected function wpml_get_translatable_fields(): array {
		// Override this method in the widget class.

		return array();
	}

	/**
	 * Get the integration class name for the widget.
	 * Can be used to translate `\Elementor\Repeater` controls and other complex fields.
	 *
	 * @return string|null The integration class name.
	 */
	protected function wpml_get_integration_class_name() {
		// Override this method in the widget class.

		return null;
	}
}
