<?php

namespace Arts\ElementorExtension\Managers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Elementor\Core\Kits\Documents\Kit;

/**
 * @package Arts\ElementorExtension\Managers
 */
class Tabs extends BaseManager {
	/**
	 * Tab definitions to register, each as { file?, class }.
	 *
	 * @var array<int, array{file?: string, class: class-string}>
	 */
	public $tabs = array();

	/**
	 * Class names of successfully registered tabs, in registration order.
	 *
	 * @var array<int, class-string>
	 */
	public $references = array();

	protected $require_files = array(
		__DIR__ . '/../Tabs/BaseTab.php',
	);

	protected function apply_filters(): void {
		/**
		 * @var array<int, array{file?: string, class: class-string}> $filtered
		 */
		$filtered = apply_filters( 'arts/elementor_extension/tabs/tabs', $this->tabs );
		if ( is_array( $filtered ) ) {
			$this->tabs = $filtered;
		}
	}

	/**
	 * Hooked on `elementor/kit/register_tabs`. Requires each tab file, calls
	 * Kit::register_tab() with the tab's TAB_ID, and finally wires
	 * add_tab_live_settings() into the editor live-settings filter.
	 *
	 * @param Kit $kit The Elementor kit document.
	 *
	 * @return void
	 */
	public function register( Kit $kit ) {
		if ( ! is_array( $this->tabs ) || empty( $this->tabs ) ) {
			return;
		}

		// Guard against fatal "cannot redeclare class" when multiple Strauss-prefixed
		// copies of the package are loaded by sibling plugins or the theme.
		if ( ! class_exists( 'Arts\ElementorExtension\Tabs\BaseTab' ) ) {
			$this->require_files();
		}

		foreach ( $this->tabs as $tab ) {
			if ( isset( $tab['file'] ) && file_exists( $tab['file'] ) ) {
				require_once $tab['file'];
			}

			if ( ! isset( $tab['class'] ) ) {
				continue;
			}

			if ( ! class_exists( $tab['class'] ) ) {
				continue;
			}

			$this->references[] = $tab['class'];

			$kit->register_tab( $tab['class']::TAB_ID, $tab['class'] );
		}

		do_action( 'arts/elementor_extension/tabs/tabs_registered', $this->references, $this );
		add_filter( 'arts/elementor_extension/editor/live_settings', array( $this, 'add_tab_live_settings' ) );
	}

	/**
	 * Filter callback that appends every tab's live-update control IDs to the
	 * editor live-settings array.
	 *
	 * @param array<int, string> $live_settings
	 * @return array<int, string>
	 */
	public function add_tab_live_settings( array $live_settings ): array {
		$tab_live_controls = $this->get_tabs_live_controls();

		$live_settings = array_merge( $live_settings, $tab_live_controls );

		return $live_settings;
	}

	/**
	 * Collects EDITOR_CHANGE_CALLBACK_CONTROLS from every configured tab class
	 * and returns the de-duplicated union.
	 *
	 * @return array<int, string>
	 */
	public function get_tabs_live_controls(): array {
		/** @var array<int, string> $tabs_live_controls */
		$tabs_live_controls = array();

		foreach ( $this->tabs as $tab ) {
			if ( defined( $tab['class'] . '::EDITOR_CHANGE_CALLBACK_CONTROLS' ) &&
			is_array( $tab['class']::EDITOR_CHANGE_CALLBACK_CONTROLS ) &&
			! empty( $tab['class']::EDITOR_CHANGE_CALLBACK_CONTROLS ) ) {
				/** @var array<int, string> $tab_controls */
				$tab_controls       = $tab['class']::EDITOR_CHANGE_CALLBACK_CONTROLS;
				$tabs_live_controls = array_merge( $tabs_live_controls, $tab_controls );
			}
		}

		/** @var array<int, string> $unique_controls */
		$unique_controls = array_unique( $tabs_live_controls );

		return $unique_controls;
	}
}
