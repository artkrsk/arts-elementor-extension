<?php

namespace Arts\ElementorExtension\Tabs;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Elementor\Core\Kits\Documents\Tabs\Tab_Base;
use Elementor\Core\Base\Document;
use Elementor\Controls_Manager;

abstract class BaseTab extends Tab_Base {
	/** @var string */
	const TAB_ID = 'arts-elementor-extension-custom-tab';

	/**
	 * Control types whose values get synced to the database via before_save().
	 */
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
	 * Controls that opt into the editor live-settings change callback.
	 *
	 * @var array<int, string>
	 */
	const EDITOR_CHANGE_CALLBACK_CONTROLS = array();

	/**
	 * @return string
	 */
	public function get_id() {
		return static::TAB_ID;
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Custom Tab', 'arts-elementor-extension' );
	}

	/**
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-theme-style';
	}

	/**
	 * Returns the group the tab is rendered under in the Elementor kit panel.
	 * Elementor registers these groups by default:
	 *
	 *   "Design System"   - `global`
	 *   "Theme Style"     - `theme-style`
	 *   "Settings"        - `settings`
	 *
	 * @return string 'global' | 'theme-style' | 'settings'
	 */
	public function get_group() {
		return 'settings';
	}

	/**
	 * Elementor entry point for control registration.
	 * Wraps register_tab() and register_tab_controls() with before/after extension hooks.
	 *
	 * @return void
	 */
	public function register_controls() {
		$this->before_register_tab();
		$this->register_tab();
		$this->register_tab_controls();
		$this->after_register_tab();
	}

	/**
	 * Extension point invoked before register_tab_controls(). Override in subclasses.
	 */
	protected function before_register_tab(): void {
	}

	/**
	 * Extension point invoked after register_tab_controls(). Override in subclasses.
	 */
	protected function after_register_tab(): void {
	}

	/**
	 * Hooked by Elementor on document save. For each control declared with
	 * `save_db => 'option'` and whose type is in SYNC_CONTROL_TYPES, copies the
	 * submitted value into the matching WordPress option via update_option().
	 * Skips work entirely when the document is not transitioning to `publish`.
	 *
	 * @param array<string, mixed> $data The data to be saved.
	 *
	 * @return array<string, mixed> The (unmodified) data, returned so Elementor can continue the save pipeline.
	 */
	public function before_save( array $data ): array {
		if ( ! isset( $data['settings'] ) ||
			! is_array( $data['settings'] ) ||
			( isset( $data['settings']['post_status'] ) && Document::STATUS_PUBLISH !== $data['settings']['post_status'] ) ) {
			return $data;
		}

		if ( ! empty( $data['settings'] ) ) {
			$current_tab_controls = $this->get_current_tab_controls();

			if ( ! empty( $current_tab_controls ) ) {
				$parent_settings = $this->parent->get_settings();
				assert( is_array( $parent_settings ) );
				$settings = $this->parent->get_active_settings( $parent_settings, $current_tab_controls );

				foreach ( $current_tab_controls as $control ) {
					$save_method = $this->get_control_save_method( $control );

					if ( ! $save_method || ! isset( $control['name'] ) || ! is_string( $control['name'] ) ) {
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
	 * @param array<string, mixed> $control The control data.
	 *
	 * @return string|false The save method ("option") or false if no saving needed.
	 */
	private function get_control_save_method( array $control ) {
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
	 * @param array<string, mixed> $data    The data containing settings.
	 * @param array<string, mixed> $control The control data.
	 *
	 * @return mixed The control value.
	 */
	private function get_control_value( array $data, array $control ) {
		if ( ! isset( $control['name'] ) || ! is_string( $control['name'] ) ) {
			return isset( $control['default'] ) ? $control['default'] : '';
		}

		if ( isset( $data['settings'] ) &&
			is_array( $data['settings'] ) &&
			isset( $data['settings'][ $control['name'] ] ) ) {
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
	private function save_control_value( string $key, $value, string $save_method ): void {
		if ( $save_method === 'option' ) {
			update_option( $key, $value );
		}
	}

	/**
	 * Get the controls registered in the current tab.
	 *
	 * @return array<int, array<string, mixed>> The array of controls.
	 */
	private function get_current_tab_controls(): array {
		$controls = $this->parent->get_controls();
		/** @var array<int, array<string, mixed>> $current_tab_controls */
		$current_tab_controls = array();

		if ( ! is_array( $controls ) ) {
			return $current_tab_controls;
		}

		foreach ( $controls as $control ) {
			if ( ! is_array( $control ) ) {
				continue;
			}

			if ( isset( $control['tab'] ) &&
				$control['tab'] === $this->get_id() &&
				isset( $control['type'] ) &&
				in_array( $control['type'], self::SYNC_CONTROL_TYPES, true ) ) {
				/** @var array<string, mixed> $control */
				$current_tab_controls[] = $control;
			}
		}

		return $current_tab_controls;
	}

	/**
	 * Converts an Elementor control value into the shape stored in the Customizer.
	 * Maps Switcher 'yes'/'' → bool, and unwraps Slider arrays to their `size`.
	 *
	 * @param mixed $val The value to filter.
	 *
	 * @return mixed
	 */
	public function filter_value_elementor_to_customizer( $val ) {
		if ( $val === 'yes' ) {
			return true;
		}

		if ( $val === '' ) {
			return false;
		}

		if ( is_array( $val ) ) {
			if ( array_key_exists( 'size', $val ) ) {
				return $val['size'];
			}
		}

		return $val;
	}

	/**
	 * Inverse of filter_value_elementor_to_customizer(): re-shapes a value coming
	 * from the Customizer for use by an Elementor control of the given type.
	 *
	 * @param mixed  $val  The value to filter.
	 * @param string $type The control type.
	 *
	 * @return mixed
	 */
	public function filter_value_from_customizer_to_elementor( $val, $type ) {
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
	 * Adds a RAW_HTML notice with a "Save and Reload" link that triggers
	 * `document/save/default` and then `preview/reload` from the editor.
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
					esc_html__( 'Save and Reload', 'arts-elementor-extension' )
				),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);
	}
}
