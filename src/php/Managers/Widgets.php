<?php

namespace Arts\ElementorExtension\Managers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use \Elementor\Widgets_Manager;

/**
 * Class Widgets
 *
 * @package Arts\ElementorExtension\Managers
 */
class Widgets extends BaseManager {
	/**
	 * The categories for the widgets.
	 *
	 * @var array
	 */
	public $categories = array();

	/**
	 * The widgets to register.
	 *
	 * @var array
	 */
	public $widgets = array();

	/**
	 * The widgets instances.
	 *
	 * @var array
	 */
	public $instances = array();

	/**
	 * Files to require manually for the plugin.
	 * Used on `elementor/widgets/register` action.
	 *
	 * @var array
	 */
	protected $require_files = array(
		__DIR__ . '/../Widgets/BaseWidget.php',
		// __DIR__ . '/../Widgets/BaseWidgetComponent.php',
		__DIR__ . '/../Widgets/BaseSkin.php',
		// __DIR__ . '/../Widgets/BaseSkinComponent.php',
	);

	protected function apply_filters() {
		$this->widgets = apply_filters( 'arts/elementor_extension/widgets/widgets', $this->widgets );
	}

	/**
	 * Register the widgets.
	 *
	 * @param Widgets_Manager $widgets_manager The widgets manager.
	 *
	 * @return void
	 */
	public function register( Widgets_Manager $widgets_manager ) {
		$this->instantiate();

		if ( ! is_array( $this->instances ) || empty( $this->instances ) ) {
			return;
		}

		foreach ( $this->instances as $widget ) {
			$widgets_manager->register( $widget );
		}

		// Allow developers to hook into the widgets registration event.
		do_action( 'arts/elementor_extension/widgets/widgets_registered', $this->instances, $this );
	}

	public function instantiate() {
		// If we've already instantiated widgets, don't do it again
		if ( ! empty( $this->instances ) ) {
			return;
		}

		$this->require_files();

		if ( ! is_array( $this->widgets ) || empty( $this->widgets ) ) {
			return;
		}

		foreach ( $this->widgets as $widget ) {
			if ( ! isset( $widget['file'] ) || ! file_exists( $widget['file'] ) || ! isset( $widget['class'] ) ) {
				continue;
			}

			require_once $widget['file'];

			if ( class_exists( $widget['class'] ) ) {
				$instance = $this->get_class_instance( $widget['class'] );

				$this->instances[] = $instance;
			}
		}
	}

	public function add_init_actions() {
		$this->instantiate();

		if ( ! is_array( $this->instances ) || empty( $this->instances ) ) {
			return;
		}

		foreach ( $this->instances as $widget ) {
			if ( method_exists( $widget, 'add_init_action' ) ) {
				$widget->add_init_action();
			}
		}
	}

	/**
	 * Generates JavaScript code to initialize Elementor widget handlers in the editor.
	 *
	 * @return string JavaScript code to be included in the editor.
	 */
	public function get_elementor_editor_js_string() {
		$this->instantiate();

		if ( ! is_array( $this->instances ) || empty( $this->instances ) ) {
			return '';
		}

		$handler_strings = array();
		foreach ( $this->instances as $widget ) {
			if ( method_exists( $widget, 'get_elementor_editor_handler_js_string' ) ) {
				$handler_strings[] = $widget->get_elementor_editor_handler_js_string();
			}
		}

		return "
'use strict';
(function() {
window.addEventListener('elementor/frontend/init', onElementorInit, {once: true});
function onElementorInit() {
" . implode( "\n", $handler_strings ) . '
}
})();';
	}
}