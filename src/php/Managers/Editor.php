<?php

namespace Arts\ElementorExtension\Managers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Arts\Utilities\Utilities;

/**
 * Class Editor
 *
 * @package Arts\ElementorExtension\Managers
 */
class Editor extends BaseManager {
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

		// Enqueue the main script file
		wp_enqueue_script(
			$script_id,
			$dir_url . 'libraries/arts-elementor-widget-handler/index.umd.js',
			array(),
			false,
			true
		);

		// Guard: Check if this exact content was already added by another vendor copy
		// Multiple plugins may bundle arts/elementor-extension, each calling this method
		global $wp_scripts;
		if ( $wp_scripts instanceof \WP_Scripts &&
			isset( $wp_scripts->registered[ $script_id ] ) &&
			isset( $wp_scripts->registered[ $script_id ]->extra['after'] ) ) {
			$existing_scripts = (array) $wp_scripts->registered[ $script_id ]->extra['after'];
			if ( in_array( $inline_js, $existing_scripts, true ) ) {
				// This exact content already added - skip to prevent duplication
				return;
			}
		}

		wp_add_inline_script( $script_id, $inline_js, 'after' );
	}
}
