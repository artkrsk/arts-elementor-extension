<?php

namespace Arts\ElementorExtension\Widgets\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

trait WPML {
	/**
	 * Hooks filter_wpml_widgets_to_translate() into WPML's
	 * `wpml_elementor_widgets_to_translate` filter.
	 *
	 * @return void
	 */
	public function add_wpml_compatibility() {
		add_filter( 'wpml_elementor_widgets_to_translate', array( $this, 'filter_wpml_widgets_to_translate' ) );
	}

	/**
	 * Adds (or overwrites) this widget's translation config in the WPML widget map.
	 * Keyed by widget name so WPML can match the entry to widgets at render time.
	 *
	 * @param array<string, mixed> $widgets
	 * @return array<string, mixed>
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
	 * Subclasses override to return the per-field translation descriptors
	 * (`field`, `type`, `editor_type`) consumed by WPML.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	protected function wpml_get_translatable_fields(): array {
		return array();
	}

	/**
	 * Subclasses override to return a WPML integration class name when the
	 * default field-based translation isn't enough (e.g. repeater fields).
	 *
	 * @return string|null
	 */
	protected function wpml_get_integration_class_name() {
		return null;
	}
}
