<?php

namespace Arts\ElementorExtension\Managers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Arts\Utilities\Utilities;

/**
 * @package Arts\ElementorExtension\Managers
 */
class Editor extends BaseManager {
	/**
	 * Hooked on `elementor/editor/after_enqueue_scripts`. Enqueues the live-settings
	 * bridge script and localizes the list of control IDs that should re-emit
	 * change events in the editor. No-ops when no controls opted in.
	 */
	public function enqueue_live_settings_script(): void {
		$script_id = 'arts-elementor-extension-editor-live-settings';
		/** @var array<int, string> $live_settings */
		$live_settings = apply_filters( 'arts/elementor_extension/editor/live_settings', array() );

		if ( empty( $live_settings ) ) {
			return;
		}

		$dir_url = $this->args['dir_url'] ?? '';
		assert( is_string( $dir_url ) );

		wp_enqueue_script(
			$script_id,
			$dir_url . 'libraries/arts-elementor-extension/index.umd.js',
			array( 'elementor-editor' ),
			false,
			true
		);

		wp_localize_script(
			$script_id,
			'artsElementorExtensionEditorLiveSettings',
			$live_settings
		);
	}

	/**
	 * Hooked on `wp_enqueue_scripts`. In the editor preview frame, enqueues the
	 * widget-handler runtime and appends a per-widget handler bootstrap as an
	 * inline `after` script.
	 *
	 * Because the same package can be loaded multiple times under Strauss-prefixed
	 * namespaces by sibling plugins/the theme, each instance runs this method.
	 * The wp_scripts->registered[$id]->extra['after'] array is inspected so the
	 * exact same inline payload is not appended twice — wp_add_inline_script()
	 * would otherwise concatenate it and Elementor would invoke each handler
	 * registration repeatedly.
	 */
	public function enqueue_widget_handler_script(): void {
		if ( ! Utilities::is_elementor_editor_active() ) {
			return;
		}

		if ( ! isset( $this->managers->widgets ) ) {
			return;
		}

		$script_id = 'arts-elementor-extension-widget-handler';
		$inline_js = $this->managers->widgets->get_elementor_editor_js_string();

		if ( empty( $inline_js ) ) {
			return;
		}

		$dir_url = $this->args['dir_url'] ?? '';

		if ( ! is_string( $dir_url ) ) {
			return;
		}

		wp_enqueue_script(
			$script_id,
			$dir_url . 'libraries/arts-elementor-widget-handler/index.umd.js',
			array(),
			false,
			true
		);

		global $wp_scripts;
		if ( $wp_scripts instanceof \WP_Scripts &&
			isset( $wp_scripts->registered[ $script_id ] ) &&
			isset( $wp_scripts->registered[ $script_id ]->extra['after'] ) ) {
			$existing_scripts = (array) $wp_scripts->registered[ $script_id ]->extra['after'];
			if ( in_array( $inline_js, $existing_scripts, true ) ) {
				return;
			}
		}

		wp_add_inline_script( $script_id, $inline_js, 'after' );
	}
}
