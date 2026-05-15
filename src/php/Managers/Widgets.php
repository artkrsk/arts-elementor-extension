<?php

namespace Arts\ElementorExtension\Managers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Elementor\Widgets_Manager;

/**
 * @package Arts\ElementorExtension\Managers
 */
class Widgets extends BaseManager {
	/**
	 * @var array<string, mixed>
	 */
	public $categories = array();

	/**
	 * Widget definitions to register, each as { file, class }.
	 *
	 * @var array<int, array{file: string, class: class-string<\Elementor\Widget_Base>}>
	 */
	public $widgets = array();

	/**
	 * Cached widget instances built by instantiate().
	 *
	 * @var array<int, \Elementor\Widget_Base>
	 */
	public $instances = array();

	/**
	 * Base class files required up front so widget classes can extend them.
	 *
	 * @var array<int, string>
	 */
	protected $require_files = array(
		__DIR__ . '/../Widgets/BaseWidget.php',
		__DIR__ . '/../Widgets/BaseSkin.php',
	);

	protected function apply_filters(): void {
		/**
		 * @var array<int, array{file: string, class: class-string<\Elementor\Widget_Base>}> $filtered
		 */
		$filtered = apply_filters( 'arts/elementor_extension/widgets/widgets', $this->widgets );
		if ( is_array( $filtered ) ) {
			$this->widgets = $filtered;
		}
	}

	/**
	 * Hooked on `elementor/widgets/register`. Instantiates and registers every
	 * configured widget, then fires arts/elementor_extension/widgets/widgets_registered.
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

		do_action( 'arts/elementor_extension/widgets/widgets_registered', $this->instances, $this );
	}

	/**
	 * Loads widget files and builds one instance per configured widget.
	 * Idempotent: subsequent calls return immediately once $instances is populated.
	 */
	public function instantiate(): void {
		if ( ! empty( $this->instances ) ) {
			return;
		}

		// Guard against fatal "cannot redeclare class" when multiple Strauss-prefixed
		// copies of the package are loaded by sibling plugins or the theme.
		if ( ! class_exists( 'Arts\ElementorExtension\Widgets\BaseWidget' ) || ! class_exists( 'Arts\ElementorExtension\Widgets\BaseSkin' ) ) {
			$this->require_files();
		}

		if ( ! is_array( $this->widgets ) || empty( $this->widgets ) ) {
			return;
		}

		foreach ( $this->widgets as $widget ) {
			if ( ! isset( $widget['file'] ) || ! file_exists( $widget['file'] ) || ! isset( $widget['class'] ) ) {
				continue;
			}

			require_once $widget['file'];

			if ( class_exists( $widget['class'] ) ) {
				/** @var class-string<\Elementor\Widget_Base> $widget_class */
				$widget_class = $widget['class'];
				$instance     = $this->get_class_instance( $widget_class );

				$this->instances[] = $instance;
			}
		}
	}

	/**
	 * Hooked on `init`. Invokes add_init_action() on each widget instance that
	 * implements it (used by BaseWidget to register WPML compatibility, etc.).
	 */
	public function add_init_actions(): void {
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
	 * Returns a self-executing JS snippet that wires up each widget's editor handler
	 * inside a single `elementor/frontend/init` listener.
	 *
	 * Uses a `$GLOBALS['__arts_elementor_widget_handlers']` ledger keyed by widget
	 * name so that Strauss-prefixed sibling copies of this package can't emit
	 * duplicate handler registrations for the same widget.
	 *
	 * @return string JS to inline after the widget-handler script. Empty when no handlers are produced.
	 */
	public function get_elementor_editor_js_string() {
		$this->instantiate();

		if ( ! is_array( $this->instances ) || empty( $this->instances ) ) {
			return '';
		}

		if ( ! isset( $GLOBALS['__arts_elementor_widget_handlers'] ) ) {
			$GLOBALS['__arts_elementor_widget_handlers'] = array();
		}

		$handler_strings = array();
		foreach ( $this->instances as $widget ) {
			if ( ! method_exists( $widget, 'get_elementor_editor_handler_js_string' ) ) {
				continue;
			}

			$name = method_exists( $widget, 'get_name' ) ? $widget->get_name() : '';

			if ( $name && in_array( $name, $GLOBALS['__arts_elementor_widget_handlers'], true ) ) {
				continue;
			}

			$js = $widget->get_elementor_editor_handler_js_string();
			if ( ! empty( $js ) ) {
				$handler_strings[] = $js;
				if ( $name ) {
					$GLOBALS['__arts_elementor_widget_handlers'][] = $name;
				}
			}
		}

		if ( empty( $handler_strings ) ) {
			return '';
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

	/**
	 * @param class-string<\Elementor\Widget_Base> $class The widget class to instantiate.
	 * @return \Elementor\Widget_Base
	 */
	private function get_class_instance( string $class ): object {
		try {
			$reflection = new \ReflectionClass( $class );
			return $reflection->newInstanceArgs();
		} catch ( \ReflectionException $e ) {
			return new $class();
		}
	}
}
