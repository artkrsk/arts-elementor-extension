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
 * @package Arts\ElementorExtension
 * @extends BasePlugin<ManagersContainer>
 */
class Plugin extends BasePlugin {

	/**
	 * @return array<string, mixed>
	 */
	protected function get_default_config(): array {
		return array(
			'required_elementor_version' => '3.18',
			'required_php_version'       => '7.4',
		);
	}

	/**
	 * @return array<string, string>
	 */
	protected function get_default_strings(): array {
		return array(
			'extension_name' => esc_html__( 'Arts Elementor Extension', 'arts-elementor-extension' ),
		);
	}

	/**
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
	 * WordPress action hook that gates plugin execution.
	 *
	 * @return string
	 */
	protected function get_default_run_action(): string {
		return 'elementor/loaded';
	}

	/**
	 * Override to install the typed ManagersContainer (gives properties type-safe access).
	 *
	 * @return void
	 */
	protected function init_managers_container(): void {
		$this->managers = new ManagersContainer();
	}

	/**
	 * Runs after managers are initialized but before run() is hooked.
	 * Validates Elementor presence, Elementor version and PHP version; on failure
	 * queues an admin notice and short-circuits the remaining checks.
	 *
	 * @return void
	 */
	protected function do_after_init_managers(): void {
		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_missing_main_plugin' ) );
			return;
		}

		$required_elementor_version = $this->config['required_elementor_version'] ?? '3.18';
		assert( is_string( $required_elementor_version ) );
		if ( ! defined( 'ELEMENTOR_VERSION' ) ||
			! version_compare( ELEMENTOR_VERSION, $required_elementor_version, '>=' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_minimum_elementor_version' ) );
			return;
		}

		$required_php_version = $this->config['required_php_version'] ?? '7.4';
		assert( is_string( $required_php_version ) );
		if ( version_compare( PHP_VERSION, $required_php_version, '<' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_minimum_php_version' ) );
			return;
		}
	}

	/**
	 * Registers WordPress actions for the plugin.
	 * Invoked from BasePlugin::run() once the run_action hook (elementor/loaded) fires.
	 *
	 * @return void
	 */
	protected function add_actions(): void {
		add_action( 'elementor/elements/categories_registered', array( $this->managers->categories, 'register' ) );
		add_action( 'elementor/widgets/register', array( $this->managers->widgets, 'register' ) );
		add_action( 'init', array( $this->managers->widgets, 'add_init_actions' ) );
		add_action( 'elementor/kit/register_tabs', array( $this->managers->tabs, 'register' ) );
		add_action( 'elementor/editor/after_enqueue_scripts', array( $this->managers->editor, 'enqueue_live_settings_script' ) );
		add_action( 'wp_enqueue_scripts', array( $this->managers->editor, 'enqueue_widget_handler_script' ) );
	}

	/**
	 * Echoes a dismissible admin notice warning that Elementor is missing.
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
	 * Echoes a dismissible admin notice warning that the installed Elementor version is below the required minimum.
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
	 * Echoes a dismissible admin notice warning that the installed PHP version is below the required minimum.
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
