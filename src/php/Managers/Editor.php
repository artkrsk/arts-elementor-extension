<?php

namespace Arts\ElementorExtension\Managers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use \Arts\Utilities\Utilities;

/**
 * Class Editor
 *
 * @package Arts\ElementorExtension\Managers
 */
class Editor extends BaseManager {
	public function enqueue_live_settings_script() {
		$script_id     = 'arts-elementor-extension-editor-live-settings';
		$live_settings = apply_filters( 'arts/elementor_extension/editor/live_settings', array() );

		if ( empty( $live_settings ) ) {
			return;
		}

		wp_enqueue_script(
			$script_id,
			$this->args['dir_url'] . 'libraries/arts-elementor-extension/index.umd.js',
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

	public function enqueue_widget_handler_script() {
		if ( ! Utilities::is_elementor_editor_active() ) {
			return;
		}

		$script_id = 'arts-elementor-extension-widget-handler';
		$inline_js = $this->managers->widgets->get_elementor_editor_js_string();

		if ( ! empty( $inline_js ) ) {
			wp_enqueue_script(
				$script_id,
				$this->args['dir_url'] . 'libraries/arts-elementor-widget-handler/index.umd.js',
				array(),
				false,
				true
			);
		}

		if ( ! empty( $inline_js ) ) {
			wp_add_inline_script( $script_id, $inline_js );
		}
	}
}
