<?php

namespace Arts\ElementorExtension;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use \Arts\ElementorExtension\Managers\Categories;
use \Arts\ElementorExtension\Managers\Editor;
use \Arts\ElementorExtension\Managers\Tabs;
use \Arts\ElementorExtension\Managers\Widgets;

/**
 * Class Plugin
 *
 * @package Arts\ElementorExtension
 */
class Plugin {
	/**
	 * The instance of this class.
	 *
	 * @var Plugin
	 */
	private static $instance;

	protected static $extension_name;
	protected static $required_elementor_version;
	protected static $required_php_version;

	/**
	 * Managers for the plugin.
	 *
	 * @var Object
	 */
	private static $managers;

	/**
	 * Get the instance of this class.
	 *
	 * @return object The instance of this class.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor for Plugin class.
	 */
	private function __construct() {
		self::$managers = new \stdClass();
		self::init();
	}

	/**
	 * Initializes the plugin by adding managers, filters, and actions.
	 *
	 * @return Plugin Returns the current instance for method chaining.
	 */
	private static function init() {
		$config = apply_filters(
			'arts/elementor_extension/plugin/config',
			array(
				'dir_url'                    => plugin_dir_url( __FILE__ ),
				'required_elementor_version' => '3.18',
				'required_php_version'       => '7.4',
			)
		);

		$strings = apply_filters(
			'arts/elementor_extension/plugin/strings',
			array(
				'extension_name' => esc_html__( 'Arts Elementor Extension', 'arts-elementor-extension' ),
			)
		);

		self::$extension_name             = $strings['extension_name'];
		self::$required_elementor_version = $config['required_elementor_version'];
		self::$required_php_version       = $config['required_php_version'];

		self::add_managers( $config, $strings );

		// Check if Elementor installed and activated
		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'admin_notices', array( self::class, 'admin_notice_missing_main_plugin' ) );
			return self::$instance;
		}

		// Check for required Elementor version
		if ( ! defined( 'ELEMENTOR_VERSION' ) || ! version_compare( ELEMENTOR_VERSION, $config['required_elementor_version'], '>=' ) ) {
			add_action( 'admin_notices', array( self::class, 'admin_notice_minimum_elementor_version' ) );
			return self::$instance;
		}

		// Check for required PHP version
		if ( version_compare( PHP_VERSION, $config['required_php_version'], '<' ) ) {
			add_action( 'admin_notices', array( self::class, 'admin_notice_minimum_php_version' ) );
			return self::$instance;
		}

		self::init_managers();
		self::add_filters( $config, $strings );
		self::add_actions( $config, $strings );

		return self::$instance;
	}

	/**
	 * Adds manager instances to the managers property.
	 *
	 * @param array $config Arguments to pass to the manager classes.
	 * @param array $strings Strings to pass to the manager classes.
	 *
	 * @return Plugin Returns the current instance for method chaining.
	 */
	private static function add_managers( $config, $strings ) {
		$manager_classes = array(
			'categories' => Categories::class,
			'editor'     => Editor::class,
			'tabs'       => Tabs::class,
			'widgets'    => Widgets::class,
		);

		foreach ( $manager_classes as $key => $class ) {
			self::$managers->$key = self::get_manager_instance( $class, $config, $strings );
		}

		return self::$instance;
	}

	/**
	 * Initialize all manager classes by calling their init method if it exists.
	 *
	 * @return Plugin Returns the current instance for method chaining.
	 */
	private static function init_managers() {
		$managers = self::$managers;

		foreach ( $managers as $manager ) {
			if ( method_exists( $manager, 'init' ) ) {
				$manager->init( $managers );
			}
		}

		return self::$instance;
	}

	/**
	 * Helper method to instantiate a manager class.
	 *
	 * @param string $class The manager class to instantiate.
	 * @param array  $config Arguments to pass to the manager class.
	 * @param array  $strings Strings to pass to the manager class.
	 *
	 * @return object The instantiated manager class.
	 */
	private static function get_manager_instance( $class, $config, $strings ) {
		try {
			$reflection = new \ReflectionClass( $class );
			return $reflection->newInstanceArgs( array( $config, $strings ) );
		} catch ( \ReflectionException $e ) {
			return new $class();
		}
	}

	/**
	 * Adds WordPress actions for the plugin.
	 *
	 * @param array $config Arguments to pass to the action hooks.
	 *
	 * @return Plugin Returns the current instance for method chaining.
	 */
	private static function add_actions( $config = array(), $strings = array() ) {
		// Register extra categories for Elementor widgets
		add_action( 'elementor/elements/categories_registered', array( self::$managers->categories, 'register' ) );

		// Register extra Elementor widgets
		add_action( 'elementor/widgets/register', array( self::$managers->widgets, 'register' ) );

		// Register extra Elementor widgets init actions
		add_action( 'init', array( self::$managers->widgets, 'add_init_actions' ) );

		// Register extra tabs in Elementor Site Settings
		add_action( 'elementor/kit/register_tabs', array( self::$managers->tabs, 'register' ) );

		add_action( 'elementor/editor/after_enqueue_scripts', array( self::$managers->editor, 'enqueue_live_settings_script' ) );

		add_action( 'wp_enqueue_scripts', array( self::$managers->editor, 'enqueue_widget_handler_script' ) );

		return self::$instance;
	}

	/**
	 * Adds WordPress filters for the plugin.
	 *
	 * @return Plugin Returns the current instance for method chaining.
	 */
	private static function add_filters( $config = array(), $strings = array() ) {
		return self::$instance;
	}

	/**
	 * Display an admin notice if Elementor is not installed or activated.
	 *
	 * Constructs a warning message indicating that the plugin requires Elementor
	 * to be installed and activated, and displays this message as a dismissible admin notice.
	 *
	 * @return void
	 */
	public static function admin_notice_missing_main_plugin() {
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		$message = sprintf(
			/* translators: 1: Plugin name 2: Elementor */
			esc_html__( '"%1$s" requires "%2$s" to be installed and activated.', 'arts-elementor-extension' ),
			'<strong>' . self::$extension_name . '</strong>',
			'<strong>' . esc_html__( 'Elementor', 'arts-elementor-extension' ) . '</strong>'
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
	}

	/**
	 * Display an admin notice if the installed Elementor version is not compatible with the plugin.
	 *
	 * Constructs a warning message indicating that the plugin requires a newer version of Elementor
	 * to be installed and activated, and displays this message as a dismissible admin notice.
	 *
	 * @return void
	 */
	public static function admin_notice_minimum_elementor_version() {
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		$message = sprintf(
			/* translators: 1: Plugin name 2: Elementor 3: Required Elementor version */
			esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'arts-elementor-extension' ),
			'<strong>' . self::$extension_name . '</strong>',
			'<strong>' . esc_html__( 'Elementor', 'arts-elementor-extension' ) . '</strong>',
			self::$required_elementor_version
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
	}

	/**
	 * Display an admin notice if the installed PHP version is not compatible with the plugin.
	 *
	 * Constructs a warning message indicating that the plugin requires a newer version of PHP
	 * to be installed and activated, and displays this message as a dismissible admin notice.
	 *
	 * @return void
	 */
	public static function admin_notice_minimum_php_version() {
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		$message = sprintf(
			/* translators: 1: Plugin name 2: PHP 3: Required PHP version */
			esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'arts-elementor-extension' ),
			'<strong>' . self::$extension_name . '</strong>',
			'<strong>' . esc_html__( 'PHP', 'arts-elementor-extension' ) . '</strong>',
			'<strong>' . self::$required_php_version . '</strong>'
		);

		printf( '<div class="notice notice-error is-dismissible"><p>%1$s</p></div>', $message );
	}
}
