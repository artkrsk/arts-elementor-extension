<?php

namespace Arts\ElementorExtension\Managers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Elementor\Core\Kits\Documents\Kit;

/**
 * Class Tabs
 *
 * @package Arts\ElementorExtension\Managers
 */
class Tabs extends BaseManager {
	/**
	 * The tabs to register.
	 *
	 * @var array<int, array{file?: string, class: class-string}>
	 */
	public $tabs = array();

	/**
	 * The tabs references.
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
	 * Register the tabs under `Site Settings` menu in the Elementor editor.
	 *
	 * @param Kit $kit The Elementor document instance.
	 *
	 * @return void
	 */
	public function register( Kit $kit ) {
		if ( ! is_array( $this->tabs ) || empty( $this->tabs ) ) {
			return;
		}

		$this->require_files();

		foreach ( $this->tabs as $tab ) {
			if ( isset( $tab['file'] ) && file_exists( $tab['file'] ) ) {
				require_once $tab['file'];
			}

			if ( ! isset( $tab['class'] ) || ! class_exists( $tab['class'] ) ) {
				continue;
			}

			$this->references[] = $tab['class'];

			$kit->register_tab( $tab['class']::TAB_ID, $tab['class'] );
		}

		// Allow developers to hook into the tabs registered event.
		do_action( 'arts/elementor_extension/tabs/tabs_registered', $this->references, $this );
		add_filter( 'arts/elementor_extension/editor/live_settings', array( $this, 'add_tab_live_settings' ) );
	}

	/**
	 * Add tab live settings to the existing live settings array.
	 *
	 * @param array<int, string> $live_settings The existing live settings.
	 * @return array<int, string> The modified live settings with tab controls.
	 */
	public function add_tab_live_settings( array $live_settings ): array {
		$tab_live_controls = $this->get_tabs_live_controls();

		$live_settings = array_merge( $live_settings, $tab_live_controls );

		return $live_settings;
	}

	/**
	 * Get live controls from all registered tabs.
	 *
	 * @return array<int, string> The array of live control IDs.
	 */
	public function get_tabs_live_controls(): array {
		/** @var array<int, string> $tabs_live_controls */
		$tabs_live_controls = array();

		foreach ( $this->tabs as $tab ) {
			// Check if the constant exists and is an array
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
