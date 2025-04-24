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
		$inline_js     = $this->managers->widgets->get_elementor_editor_js_string();

		if ( ! empty( $live_settings ) || ! empty( $inline_js ) ) {
			wp_enqueue_script(
				$script_id,
				$this->args['dir_url'] . '/libraries/arts-elementor-extension/index.umd.js',
				array( 'elementor-editor' ),
				false,
				true
			);
		}

		if ( ! empty( $live_settings ) ) {
			wp_localize_script(
				$script_id,
				'artsElementorExtensionEditorLiveSettings',
				$live_settings
			);
		}

		if ( ! empty( $inline_js ) ) {
			wp_add_inline_script( $script_id, $inline_js );
		}
	}
}
