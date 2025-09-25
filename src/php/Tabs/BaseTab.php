<?php

namespace Arts\ElementorExtension\Tabs;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use \Elementor\Core\Kits\Documents\Tabs\Tab_Base;
use \Elementor\Core\Base\Document;
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
	 * Handle the save action for Elementor settings and syncs the data to the database.
	 * This method is called when the user clicks the "Save & Close" button in the Elementor settings.
	 *
	 * @param array $data The data to be saved.
	 *
	 * @return array $data The data to be saved.
	 */
	public function before_save( $data ) {
		if ( ! isset( $data['settings'] ) || ( isset( $data['settings']['post_status'] ) && Document::STATUS_PUBLISH !== $data['settings']['post_status'] ) ) {
			return $data;
		}

		if ( ! empty( $data['settings'] ) ) {
			$current_tab_controls = $this->get_current_tab_controls();

			if ( ! empty( $current_tab_controls ) ) {
				$settings = $this->parent->get_active_settings( $this->parent->get_settings(), $current_tab_controls );

				foreach ( $current_tab_controls as $control ) {
					$save_method = $this->get_control_save_method( $control );

					if ( ! $save_method || ! isset( $control['name'] ) ) {
						continue;
					}

					$value = $this->get_control_value( $data, $control );
					$value = $this->filter_value_elementor_to_customizer( $value );

					$this->save_control_value( $control['name'], $value, $save_method );
				}
			}
		}

		return $data;
	}

	/**
	 * Get the method to save the control value.
	 *
	 * @param array $control The control data.
	 *
	 * @return string|false The save method ("option") or false if no saving needed.
	 */
	private function get_control_save_method( $control ) {
		if ( ! isset( $control['save_db'] ) ) {
			return false;
		}

		if ( $control['save_db'] === 'option' ) {
			return $control['save_db'];
		}

		return false;
	}

	/**
	 * Get the value for a control.
	 *
	 * @param array $data    The data containing settings.
	 * @param array $control The control data.
	 *
	 * @return mixed The control value.
	 */
	private function get_control_value( $data, $control ) {
		if ( isset( $data['settings'][ $control['name'] ] ) ) {
			return $data['settings'][ $control['name'] ];
		} elseif ( isset( $control['default'] ) ) {
			return $control['default'];
		} else {
			return '';
		}
	}

	/**
	 * Save the control value to the database.
	 *
	 * @param string $key         The control name/key.
	 * @param mixed  $value       The value to save.
	 * @param string $save_method The method to use for saving ("option").
	 */
	private function save_control_value( $key, $value, $save_method ) {
		if ( $save_method === 'option' ) {
			update_option( $key, $value );
		}
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
