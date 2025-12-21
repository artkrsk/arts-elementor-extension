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
	 * @var array
	 */
	public $tabs = array();

	/**
	 * The tabs references.
	 *
	 * @var array
	 */
	public $references = array();

	protected $require_files = array(
		__DIR__ . '/../Tabs/BaseTab.php',
	);

	protected function apply_filters() {
		$this->tabs = apply_filters( 'arts/elementor_extension/tabs/tabs', $this->tabs );
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

	public function add_tab_live_settings( $live_settings ) {
		$tab_live_controls = $this->get_tabs_live_controls();

		$live_settings = array_merge( $live_settings, $tab_live_controls );

		return $live_settings;
	}

	public function get_tabs_live_controls() {
		$tabs_live_controls = array();

		foreach ( $this->tabs as $tab ) {
			// Check if the constant exists and is an array
			if ( defined( $tab['class'] . '::EDITOR_CHANGE_CALLBACK_CONTROLS' ) &&
			is_array( $tab['class']::EDITOR_CHANGE_CALLBACK_CONTROLS ) &&
			! empty( $tab['class']::EDITOR_CHANGE_CALLBACK_CONTROLS ) ) {
				$tabs_live_controls = array_merge( $tabs_live_controls, $tab['class']::EDITOR_CHANGE_CALLBACK_CONTROLS );
			}
		}

		$tabs_live_controls = array_unique( $tabs_live_controls );

		return $tabs_live_controls;
	}
}
