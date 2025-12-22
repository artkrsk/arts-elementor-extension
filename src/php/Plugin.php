<?php

namespace Arts\ElementorExtension;

use Arts\Base\Plugins\BasePlugin;
use Arts\ElementorExtension\Containers\ManagersContainer;
use Arts\ElementorExtension\Managers\Categories;
use Arts\ElementorExtension\Managers\Editor;
use Arts\ElementorExtension\Managers\Tabs;
use Arts\ElementorExtension\Managers\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Plugin
 *
 * @package Arts\ElementorExtension
 * @extends BasePlugin<ManagersContainer>
 */
class Plugin extends BasePlugin {

	/**
	 * Get default configuration.
	 * Called during initialization.
	 *
	 * @return array<string, mixed>
	 */
	protected function get_default_config(): array {
		return array(
			'required_elementor_version' => '3.18',
			'required_php_version'       => '7.4',
		);
	}

	/**
	 * Get default strings.
	 * Called during initialization.
	 *
	 * @return array<string, string>
	 */
	protected function get_default_strings(): array {
		return array(
			'extension_name' => esc_html__( 'Arts Elementor Extension', 'arts-elementor-extension' ),
		);
	}

	/**
	 * Get manager classes to initialize.
	 *
	 * @return array<string, class-string>
	 */
	protected function get_managers_classes(): array {
		return array(
			'categories' => Categories::class,
			'editor'     => Editor::class,
			'tabs'       => Tabs::class,
			'widgets'    => Widgets::class,
		);
	}

	/**
	 * Get the WordPress action hook on which to run the plugin.
	 * Return empty string to run immediately.
	 *
	 * @return string
	 */
	protected function get_default_run_action(): string {
		return 'elementor/loaded';
	}

	/**
	 * Initialize the managers container.
	 * Uses custom typed container for type safety.
	 *
	 * @return void
	 */
	protected function init_managers_container(): void {
		$this->managers = new ManagersContainer();
	}

	/**
	 * Hook called after managers are initialized.
	 * Used for validation checks.
	 *
	 * @return void
	 */
	protected function do_after_init_managers(): void {
		// Check if Elementor is installed and activated
		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_missing_main_plugin' ) );
			return;
		}

		// Check for required Elementor version
		$required_elementor_version = $this->config['required_elementor_version'] ?? '3.18';
		assert( is_string( $required_elementor_version ) );
		if ( ! defined( 'ELEMENTOR_VERSION' ) ||
			! version_compare( ELEMENTOR_VERSION, $required_elementor_version, '>=' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_minimum_elementor_version' ) );
			return;
		}

		// Check for required PHP version
		$required_php_version = $this->config['required_php_version'] ?? '7.4';
		assert( is_string( $required_php_version ) );
		if ( version_compare( PHP_VERSION, $required_php_version, '<' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_minimum_php_version' ) );
			return;
		}
	}

	/**
	 * Add WordPress actions.
	 * Called when run() action fires (after elementor/loaded).
	 *
	 * @return void
	 */
	protected function add_actions(): void {
		// Register extra categories for Elementor widgets
		add_action( 'elementor/elements/categories_registered', array( $this->managers->categories, 'register' ) );

		// Register extra Elementor widgets
		add_action( 'elementor/widgets/register', array( $this->managers->widgets, 'register' ) );

		// Register extra Elementor widgets init actions
		add_action( 'init', array( $this->managers->widgets, 'add_init_actions' ) );

		// Register extra tabs in Elementor Site Settings
		add_action( 'elementor/kit/register_tabs', array( $this->managers->tabs, 'register' ) );

		// Enqueue scripts
		add_action( 'elementor/editor/after_enqueue_scripts', array( $this->managers->editor, 'enqueue_live_settings_script' ) );
		add_action( 'wp_enqueue_scripts', array( $this->managers->editor, 'enqueue_widget_handler_script' ) );
	}

	/**
	 * Display an admin notice if Elementor is not installed or activated.
	 *
	 * Constructs a warning message indicating that the plugin requires Elementor
	 * to be installed and activated, and displays this message as a dismissible admin notice.
	 *
	 * @return void
	 */
	public function admin_notice_missing_main_plugin(): void {
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		$message = sprintf(
			/* translators: 1: Plugin name 2: Elementor */
			esc_html__( '"%1$s" requires "%2$s" to be installed and activated.', 'arts-elementor-extension' ),
			'<strong>' . $this->strings['extension_name'] . '</strong>',
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
	public function admin_notice_minimum_elementor_version(): void {
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		$required_version = $this->config['required_elementor_version'] ?? '3.18';
		assert( is_string( $required_version ) );
		$message = sprintf(
			/* translators: 1: Plugin name 2: Elementor 3: Required Elementor version */
			esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'arts-elementor-extension' ),
			'<strong>' . $this->strings['extension_name'] . '</strong>',
			'<strong>' . esc_html__( 'Elementor', 'arts-elementor-extension' ) . '</strong>',
			$required_version
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
	public function admin_notice_minimum_php_version(): void {
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		$required_version = $this->config['required_php_version'] ?? '7.4';
		assert( is_string( $required_version ) );
		$message = sprintf(
			/* translators: 1: Plugin name 2: PHP 3: Required PHP version */
			esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'arts-elementor-extension' ),
			'<strong>' . $this->strings['extension_name'] . '</strong>',
			'<strong>' . esc_html__( 'PHP', 'arts-elementor-extension' ) . '</strong>',
			'<strong>' . $required_version . '</strong>'
		);

		printf( '<div class="notice notice-error is-dismissible"><p>%1$s</p></div>', $message );
	}
}
