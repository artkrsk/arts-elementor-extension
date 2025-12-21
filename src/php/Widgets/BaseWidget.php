<?php

namespace Arts\ElementorExtension\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Utils;
use Arts\Utilities\Utilities;

abstract class BaseWidget extends Widget_Base {
	use Traits\Preloads;
	use Traits\WPML;

	/**
	 * Instances of the class.
	 *
	 * @var array<class-string, static>
	 */
	private static $instances = array();

	/**
	 * Static fields
	 *
	 * @var array<int, string>
	 */
	protected $data_static_fields = array(
		'title',
		'category',
		'year',
		'description',
		'link',
		'image',
		'secondary_image',
		'video',
	);

	/**
	 * Allowed HTML tags for wp_kses.
	 *
	 * @var array<string, mixed>
	 */
	public static $allowed_html = array();

	/**
	 * Get the instance of this class.
	 *
	 * @return static The instance of this class.
	 */
	public static function instance() {
		$cls = static::class;

		if ( ! isset( self::$instances[ $cls ] ) ) {
			/** @phpstan-ignore-next-line */
			self::$instances[ $cls ] = new static();
		}

		if ( is_null( self::$allowed_html ) ) {
			self::$allowed_html = wp_kses_allowed_html( 'post' );
		}

		return self::$instances[ $cls ];
	}

	/**
	 * Add actions before rendering the widget.
	 *
	 * @return void
	 */
	public function add_actions() {
		$this->add_preloads();
		$this->add_custom_actions();
	}

	public function add_init_action(): void {
		$this->add_wpml_compatibility();
		$this->add_custom_init_actions();
	}

	public function add_custom_init_actions(): void {
		// Override this method in the widget class
	}

	/**
	 * Add custom actions before rendering the widget.
	 *
	 * @return void
	 */
	public function add_custom_actions() {
		// Override this method in the widget class
	}

	/**
	 * Get widget name.
	 *
	 * @return string The widget name.
	 */
	public function get_name() {
		// Override this method in the widget class

		return '';
	}

	/**
	 * Get widget title.
	 *
	 * @return string The widget title.
	 */
	public function get_title() {
		// Override this method in the widget class

		return '';
	}

	/**
	 * Get widget icon.
	 *
	 * @return string The widget icon.
	 */
	public function get_icon() {
		return 'eicon-plug';
	}

	/**
	 * Get widget categories.
	 *
	 * @return array<int, string> The widget categories.
	 */
	public function get_categories(): array {
		return array( 'custom-category' );
	}

	/**
	 * Get current skin ID.
	 *
	 * @return string The current skin ID.
	 */
	public function get_current_skin_id() {
		return parent::get_current_skin_id();
	}

	/**
	 * Get repeater setting key.
	 *
	 * @param string $setting_key  The setting key.
	 * @param string $repeater_key The repeater key.
	 * @param int    $index        The item index.
	 * @return string The unique setting key.
	 */
	public function get_repeater_setting_key( $setting_key, $repeater_key, $index ) {
		return parent::get_repeater_setting_key( $setting_key, $repeater_key, $index );
	}

	/**
	 * Whether the element returns dynamic content.
	 * Set to determine whether to cache the element output or not.
	 *
	 * @return bool Whether to cache the element output.
	 */
	protected function is_dynamic_content(): bool {
		return true;
	}

	/**
	 * Whether the element has an inner wrapper.
	 * Set to determine whether to wrap the element with an inner wrapper or not.
	 *
	 * @return bool Whether the element has an inner wrapper.
	 */
	public function has_widget_inner_wrapper(): bool {
		return ! Utilities::is_elementor_feature_active( 'e_optimized_markup' );
	}

	/**
	 * Get static fields repeater controls.
	 *
	 * This method initializes and returns the repeater controls based on the static fields set.
	 *
	 * @return array<string, mixed> Repeater controls.
	 */
	protected function get_static_fields_repeater_controls(): array {
		$repeater   = new Repeater();
		$fields_set = $this->data_static_fields;

		if ( in_array( 'title', $fields_set ) ) {
			$repeater->add_control(
				'title',
				array(
					'label'       => esc_html__( 'Title', 'arts-elementor-extension' ),
					'type'        => Controls_Manager::TEXT,
					'default'     => esc_html__( 'Item...', 'arts-elementor-extension' ),
					'label_block' => true,
				)
			);
		}

		if ( in_array( 'date', $fields_set ) ) {
			$repeater->add_control(
				'date',
				array(
					'label'          => esc_html__( 'Date', 'arts-elementor-extension' ),
					'type'           => Controls_Manager::DATE_TIME,
					'default'        => '',
					'label_block'    => true,
					'picker_options' => array(
						'enableTime' => false,
						'dateFormat' => get_option( 'date_format', null ),
					),
				)
			);
		}

		if ( in_array( 'category', $fields_set ) ) {
			$repeater->add_control(
				'category',
				array(
					'label'       => esc_html__( 'Category', 'arts-elementor-extension' ),
					'type'        => Controls_Manager::TEXT,
					'default'     => '',
					'label_block' => true,
				)
			);
		}

		if ( in_array( 'year', $fields_set ) ) {
			$repeater->add_control(
				'year',
				array(
					'label'       => esc_html__( 'Year', 'arts-elementor-extension' ),
					'type'        => Controls_Manager::TEXT,
					'default'     => '',
					'label_block' => true,
				)
			);
		}

		if ( in_array( 'description', $fields_set ) ) {
			$repeater->add_control(
				'description',
				array(
					'label'       => esc_html__( 'Description', 'arts-elementor-extension' ),
					'type'        => Controls_Manager::TEXTAREA,
					'default'     => '',
					'label_block' => true,
				)
			);
		}

		if ( in_array( 'link', $fields_set ) ) {
			$repeater->add_control(
				'link',
				array(
					'label'         => esc_html__( 'Link', 'arts-elementor-extension' ),
					'type'          => Controls_Manager::URL,
					'placeholder'   => 'https://...',
					'show_external' => false,
					'default'       => array(
						'url'         => '#',
						'is_external' => false,
						'nofollow'    => false,
					),
					'label_block'   => true,
					'dynamic'       => array(
						'active' => true,
					),
				)
			);
		}

		if ( in_array( 'image', $fields_set ) ) {
			$repeater->add_control(
				'image',
				array(
					'label'       => esc_html__( 'Choose Image', 'arts-elementor-extension' ),
					'type'        => Controls_Manager::MEDIA,
					'default'     => array(
						'url' => Utils::get_placeholder_image_src(),
					),
					'label_block' => true,
				)
			);
		}

		if ( in_array( 'secondary_image', $fields_set ) ) {
			$repeater->add_control(
				'secondary_image',
				array(
					'label'       => esc_html__( 'Choose Secondary Image', 'arts-elementor-extension' ),
					'type'        => Controls_Manager::MEDIA,
					'default'     => array(
						'url' => Utils::get_placeholder_image_src(),
					),
					'label_block' => true,
				)
			);
		}

		if ( in_array( 'video', $fields_set ) ) {
			$repeater->add_control(
				'video',
				array(
					'label'       => esc_html__( 'Choose Video', 'arts-elementor-extension' ),
					'type'        => Controls_Manager::MEDIA,
					'media_type'  => 'video',
					'label_block' => true,
					'conditions'  => array(
						'relation' => 'and',
						'terms'    => array(
							array(
								'name'     => 'image',
								'operator' => '!==',
								'value'    => array(
									'id'  => '',
									'url' => '',
								),
							),
							array(
								'name'     => 'image',
								'operator' => '!==',
								'value'    => array(
									'id'  => '',
									'url' => Utils::get_placeholder_image_src(),
								),
							),
						),
					),
				)
			);
		}

		return $repeater->get_controls();
	}

	/**
	 * Retrieve the value of a specified option.
	 *
	 * @param string $option The option name.
	 * @param string $group_control_prefix Optional. The prefix for group control. Default is ''.
	 * @param bool   $return_size Optional. Whether to return the size if available. Default is true.
	 *
	 * @return mixed The option value or false if not found.
	 */
	public function get_option_value( $option, $group_control_prefix = '', $return_size = true ) {
		$settings = $this->get_settings_for_display();
		$setting  = $group_control_prefix . $option;

		if ( array_key_exists( $setting, $settings ) ) {
			if ( $return_size && is_array( $settings[ $setting ] ) && array_key_exists( 'size', $settings[ $setting ] ) ) {
				return $settings[ $setting ]['size'];
			} else {
				return $settings[ $setting ];
			}
		}

		return false;
	}

	/**
	 * Register custom controls for the widget.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->register_controls_content( Controls_Manager::TAB_CONTENT );
		$this->register_controls_settings( Controls_Manager::TAB_SETTINGS );
		$this->register_controls_layout( Controls_Manager::TAB_LAYOUT );
		$this->register_controls_style( Controls_Manager::TAB_STYLE );
	}

	/**
	 * Register the widget controls under `Content` tab.
	 *
	 * @param string $tab The tab ID.
	 * @return void
	 */
	protected function register_controls_content( string $tab ): void {
		// Override this method in the widget class
	}

	/**
	 * Register the widget controls under `Settings` tab.
	 *
	 * @param string $tab The tab ID.
	 * @return void
	 */
	protected function register_controls_settings( string $tab ): void {
		// Override this method in the widget class
	}

	/**
	 * Register the widget controls under `Layout` tab.
	 *
	 * @param string $tab The tab ID.
	 * @return void
	 */
	protected function register_controls_layout( string $tab ): void {
		// Override this method in the widget class
	}

	/**
	 * Register the widget controls under `Style` tab.
	 *
	 * @param string $tab The tab ID.
	 * @return void
	 */
	protected function register_controls_style( string $tab ): void {
		// Override this method in the widget class
	}

	/**
	 * Add actions before rendering the widget.
	 *
	 * @return void
	 */
	public function before_render() {
		$this->add_actions();
		?>
<div <?php $this->print_render_attribute_string( '_wrapper' ); ?>>
		<?php
	}

	/**
	 * Outputs sanitized HTML content using wp_kses.
	 *
	 * @param string $text The text to be sanitized and echoed.
	 */
	public static function wp_kses_e( string $text ): void {
		echo wp_kses( $text, static::$allowed_html );
	}
}
