<?php

namespace Arts\ElementorExtension\Tabs;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use \Elementor\Core\Kits\Documents\Tabs\Tab_Base;
use \Elementor\Controls_Manager;

abstract class BaseTab extends Tab_Base {
	/**
	 * The tab ID.
	 *
	 * @var string
	 */
	const TAB_ID = 'arts-elementor-extension-custom-tab';

	const SYNC_CONTROL_TYPES = array(
		Controls_Manager::TEXT,
		Controls_Manager::TEXTAREA,
		Controls_Manager::NUMBER,
		Controls_Manager::BUTTON,
		Controls_Manager::HIDDEN,
		Controls_Manager::SLIDER,
		Controls_Manager::SWITCHER,
		Controls_Manager::SELECT,
	);

	/**
	 * List of controls that will be available for adding the change callback in the Elementor editor.
	 *
	 * @var array
	 */
	const EDITOR_CHANGE_CALLBACK_CONTROLS = array();

	/**
	 * Get the tab ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return static::TAB_ID;
	}

	/**
	 * Get the tab title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Custom Tab', 'arts-elementor-extension' );
	}

	/**
	 * Get the tab icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-theme-style';
	}

	/**
	 * Get the group of the tab.
	 *
	 * Returns the group to which the tab belongs.
	 * By default Elementor registers the following groups:
	 *
	 * "Design System" - `global`
	 *
	 * "Theme Style" - `theme-style`
	 *
	 * "Site Settings" - `settings`
	 *
	 * @return string 'global' | 'theme-style' | 'settings' The group of the tab.
	 */
	public function get_group() {
		return 'settings';
	}

	/**
	 * Register controls for the tab.
	 *
	 * @return void
	 */
	public function register_controls() {
		$this->before_register_tab();
		$this->register_tab();
		$this->register_tab_controls();
		$this->after_register_tab();
	}

	protected function before_register_tab() {
		// Action to be performed before registering the tab.
	}

	protected function after_register_tab() {
		// Action to be performed before registering the tab.
	}


	/**
	 * Get the controls registered in the current tab.
	 *
	 * @return array The array of controls.
	 */
	private function get_current_tab_controls() {
		$controls             = $this->parent->get_controls();
		$current_tab_controls = array();

		foreach ( $controls as $control ) {
			if ( isset( $control['tab'] ) && $control['tab'] === $this->get_id() && in_array( $control['type'], self::SYNC_CONTROL_TYPES, true ) ) {
				$current_tab_controls[] = $control;
			}
		}

		return $current_tab_controls;
	}

	/**
	 * Filter the value to sync from Elementor to Customizer.
	 *
	 * @param mixed $val The value to filter.
	 *
	 * @return mixed Filtered value.
	 */
	public function filter_value_elementor_to_customizer( $val ) {
		// Switcher control
		if ( $val === 'yes' ) {
			return true;
		}

		// Switcher control
		if ( $val === '' ) {
			return false;
		}

		// Slider control
		if ( is_array( $val ) ) {
			if ( array_key_exists( 'size', $val ) ) {
				return $val['size'];
			}
		}

		return $val;
	}

	/**
	 * Filter the value to sync from Customizer to Elementor.
	 *
	 * @param mixed  $val  The value to filter.
	 * @param string $type The control type.
	 *
	 * @return mixed Filtered value.
	 */
	public function filter_value_from_customizer_to_elementor( $val, $type ) {
		// Switcher control
		if ( $type === Controls_Manager::SWITCHER ) {
			if ( $val === true ) {
				return 'yes';
			} elseif ( $val === false ) {
				return '';
			}
		}

		return $val;
	}

	/**
	 * Add a refresh notice control to the Elementor settings.
	 *
	 * @return void
	 */
	public function add_refresh_notice() {
		$this->add_control(
			$this->get_id() . '_refresh_notice',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => sprintf(
					'%1$s <a href="javascript: $e.run( \'document/save/default\' ).then(() => { $e.run( \'preview/reload\' ) });">%2$s</a>',
					esc_html__( 'To preview changes please click', 'arts-elementor-extension' ),
					esc_html__( 'Save and Reload', 'arts-elementor-extension' ),
					'',
				),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);
	}
}
