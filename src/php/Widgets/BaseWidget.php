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
	 * Per-subclass instance cache used by instance().
	 *
	 * @var array<class-string, static>
	 */
	private static $instances = array();

	/**
	 * Field names rendered by get_static_fields_repeater_controls(). Override
	 * in subclasses to restrict the set of pre-built repeater fields.
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
	 * Lazily populated by instance(): the wp_kses allowed-HTML map used by wp_kses_e().
	 *
	 * @var array<string, array<string, bool|string>>
	 */
	public static $allowed_html = array();

	/**
	 * Returns (and lazily creates) a singleton instance per concrete widget class,
	 * and on first access populates self::$allowed_html from wp_kses_allowed_html('post').
	 *
	 * @return static
	 */
	public static function instance() {
		$cls = static::class;

		if ( ! isset( self::$instances[ $cls ] ) ) {
			/** @phpstan-ignore-next-line */
			self::$instances[ $cls ] = new static();
		}

		if ( is_null( self::$allowed_html ) ) {
			/** @var array<string, array<string, bool|string>> $allowed_html */
			$allowed_html       = wp_kses_allowed_html( 'post' );
			self::$allowed_html = $allowed_html;
		}

		return self::$instances[ $cls ];
	}

	/**
	 * Called from before_render(). Registers preload filters and the subclass
	 * extension point add_custom_actions().
	 *
	 * @return void
	 */
	public function add_actions() {
		$this->add_preloads();
		$this->add_custom_actions();
	}

	/**
	 * Called from Widgets manager on `init`. Wires WPML compatibility and the
	 * subclass extension point add_custom_init_actions().
	 */
	public function add_init_action(): void {
		$this->add_wpml_compatibility();
		$this->add_custom_init_actions();
	}

	/** Extension point invoked on `init`. Override in subclasses. */
	public function add_custom_init_actions(): void {
	}

	/**
	 * Extension point invoked before render. Override in subclasses.
	 *
	 * @return void
	 */
	public function add_custom_actions() {
	}

	/**
	 * Subclasses must override to return their Elementor widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return '';
	}

	/**
	 * Subclasses must override to return the human-readable widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return '';
	}

	/**
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-plug';
	}

	/**
	 * @return array<int, string>
	 */
	public function get_categories(): array {
		return array( 'custom-category' );
	}

	/**
	 * @return string
	 */
	public function get_current_skin_id() {
		return parent::get_current_skin_id();
	}

	/**
	 * @param string $setting_key
	 * @param string $repeater_key
	 * @param int    $index
	 * @return string
	 */
	public function get_repeater_setting_key( $setting_key, $repeater_key, $index ) {
		return parent::get_repeater_setting_key( $setting_key, $repeater_key, $index );
	}

	/**
	 * Elementor checks this to decide whether the rendered HTML can be cached.
	 * Returning true forces fresh output on every render.
	 *
	 * @return bool
	 */
	protected function is_dynamic_content(): bool {
		return true;
	}

	/**
	 * Whether Elementor should add the inner `.elementor-widget-container` wrapper.
	 * Disabled automatically when the `e_optimized_markup` experiment is on.
	 *
	 * @return bool
	 */
	public function has_widget_inner_wrapper(): bool {
		return ! Utilities::is_elementor_feature_active( 'e_optimized_markup' );
	}

	/**
	 * Builds a Repeater control set for the subset of $data_static_fields the
	 * subclass opted into. Field-specific defaults match Elementor placeholders
	 * (e.g. Utils::get_placeholder_image_src() for image fields).
	 *
	 * @return array<string, mixed>
	 */
	protected function get_static_fields_repeater_controls(): array {
		$repeater   = new Repeater();
		$fields_set = $this->data_static_fields;

		if ( in_array( 'title', $fields_set, true ) ) {
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

		if ( in_array( 'date', $fields_set, true ) ) {
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

		if ( in_array( 'category', $fields_set, true ) ) {
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

		if ( in_array( 'year', $fields_set, true ) ) {
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

		if ( in_array( 'description', $fields_set, true ) ) {
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

		if ( in_array( 'link', $fields_set, true ) ) {
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

		if ( in_array( 'image', $fields_set, true ) ) {
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

		if ( in_array( 'secondary_image', $fields_set, true ) ) {
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

		if ( in_array( 'video', $fields_set, true ) ) {
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

		/** @var array<string, mixed> $controls */
		$controls = $repeater->get_controls();
		return $controls;
	}

	/**
	 * Looks up a setting in get_settings_for_display(). If the value is an array
	 * with a 'size' key (Slider-style controls), $return_size unwraps it.
	 *
	 * @param string $option              Setting key.
	 * @param string $group_control_prefix Prefix prepended to $option (for group controls).
	 * @param bool   $return_size         When true, unwrap `size` from array-shaped values.
	 *
	 * @return mixed The setting value, or `false` if the key is missing.
	 */
	public function get_option_value( $option, $group_control_prefix = '', $return_size = true ) {
		$settings = $this->get_settings_for_display();
		assert( is_array( $settings ) );
		$setting = $group_control_prefix . $option;

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
	 * Splits the standard Elementor control registration into four per-tab
	 * extension points so subclasses don't need to interleave tabs.
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
	 * Extension point for the Content tab. Override in subclasses.
	 *
	 * @param string $tab Tab ID to pass into start_controls_section().
	 * @return void
	 */
	protected function register_controls_content( string $tab ): void {
	}

	/**
	 * Extension point for the Settings tab. Override in subclasses.
	 *
	 * @param string $tab Tab ID to pass into start_controls_section().
	 * @return void
	 */
	protected function register_controls_settings( string $tab ): void {
	}

	/**
	 * Extension point for the Layout tab. Override in subclasses.
	 *
	 * @param string $tab Tab ID to pass into start_controls_section().
	 * @return void
	 */
	protected function register_controls_layout( string $tab ): void {
	}

	/**
	 * Extension point for the Style tab. Override in subclasses.
	 *
	 * @param string $tab Tab ID to pass into start_controls_section().
	 * @return void
	 */
	protected function register_controls_style( string $tab ): void {
	}

	/**
	 * Elementor render entry point. Runs add_actions() (so subclasses can wire
	 * preloads/filters just before output) and opens the widget wrapper element.
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
	 * Echoes $text through wp_kses() with the static $allowed_html map populated
	 * by instance(). Requires instance() to have been called first.
	 *
	 * @param string $text The text to be sanitized and echoed.
	 */
	public static function wp_kses_e( string $text ): void {
		echo wp_kses( $text, static::$allowed_html );
	}
}
