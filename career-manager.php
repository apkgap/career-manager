<?php
/**
 * Plugin Name:  Career & Job Vacancies Manager (ระบบร่วมงานกับเรา)
 * Plugin URI:   https://example.com
 * Description:  ระบบประกาศรับสมัครงานและร่วมงานกับเราแบบ Standalone ครบวงจร (Custom Post Type, หมวดหมู่ประเภทงาน, แนบไฟล์เอกสารหลายไฟล์ + ปุ่มดูตัวอย่าง PDF, วันหมดอายุอัตโนมัติ, Shortcode [career_tabs] และ Elementor Widget) พร้อมนำไปใช้ในทุกโปรเจกต์ได้ทันที
 * Version:      1.0.0
 * Author:       your-plans
 * Text Domain:  career-manager
 * Domain Path:  /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package Career_Manager
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'CAREER_VERSION', '1.0.0' );
define( 'CAREER_FILE', __FILE__ );
define( 'CAREER_PATH', plugin_dir_path( __FILE__ ) );
define( 'CAREER_URL', plugin_dir_url( __FILE__ ) );

// Load plugin modules.
require_once CAREER_PATH . 'inc/helpers.php';
require_once CAREER_PATH . 'inc/post-types.php';
require_once CAREER_PATH . 'inc/metaboxes.php';
require_once CAREER_PATH . 'inc/admin-columns-filters.php';
require_once CAREER_PATH . 'inc/shortcodes-modals.php';
require_once CAREER_PATH . 'inc/applications.php';
require_once CAREER_PATH . 'inc/template-loader.php';

/**
 * Main Plugin Controller (Singleton).
 */
final class Career_Manager_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Career_Manager_Plugin|null
	 */
	private static ?Career_Manager_Plugin $instance = null;

	/**
	 * Retrieve singleton instance.
	 *
	 * @return Career_Manager_Plugin
	 */
	public static function instance(): Career_Manager_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_and_enqueue_frontend_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Elementor integration.
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_elementor_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_elementor_widgets' ) );
		add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'enqueue_elementor_editor_styles' ) );
	}

	/**
	 * Register frontend CSS/JS and conditionally enqueue on Career pages.
	 */
	public function register_and_enqueue_frontend_assets(): void {
		$fe_css_rel = 'assets/css/career-manager-frontend.css';
		$fe_js_rel  = 'assets/js/career-manager-frontend.js';
		$fe_css_ver = file_exists( CAREER_PATH . $fe_css_rel ) ? (string) filemtime( CAREER_PATH . $fe_css_rel ) : CAREER_VERSION;
		$fe_js_ver  = file_exists( CAREER_PATH . $fe_js_rel ) ? (string) filemtime( CAREER_PATH . $fe_js_rel ) : CAREER_VERSION;

		wp_register_style(
			'career-manager-frontend',
			CAREER_URL . $fe_css_rel,
			array(),
			$fe_css_ver
		);

		wp_register_script(
			'career-manager-frontend',
			CAREER_URL . $fe_js_rel,
			array(),
			$fe_js_ver,
			true
		);

		wp_localize_script(
			'career-manager-frontend',
			'careerManagerConfig',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'career_apply_nonce' ),
			)
		);

		// Inject configured primary colors as CSS variables.
		$settings     = career_get_settings();
		$primary      = sanitize_hex_color( (string) ( $settings['primary_color'] ?? '#800020' ) ) ?: '#800020';
		$primary_dark = sanitize_hex_color( (string) ( $settings['primary_dark_color'] ?? '#5c0017' ) ) ?: '#5c0017';
		$inline_vars  = ":root { --career-primary: {$primary}; --career-primary-dark: {$primary_dark}; }";
		wp_add_inline_style( 'career-manager-frontend', $inline_vars );

		// Register Elementor widget assets.
		$w_css_rel = 'widgets/career-archive-tabs/career-archive-tabs.css';
		$w_js_rel  = 'widgets/career-archive-tabs/career-archive-tabs.js';
		$w_css_ver = file_exists( CAREER_PATH . $w_css_rel ) ? (string) filemtime( CAREER_PATH . $w_css_rel ) : CAREER_VERSION;
		$w_js_ver  = file_exists( CAREER_PATH . $w_js_rel ) ? (string) filemtime( CAREER_PATH . $w_js_rel ) : CAREER_VERSION;

		wp_register_style(
			'career-manager-widget-tabs',
			CAREER_URL . $w_css_rel,
			array( 'career-manager-frontend' ),
			$w_css_ver
		);

		wp_register_script(
			'career-manager-widget-tabs',
			CAREER_URL . $w_js_rel,
			array( 'career-manager-frontend' ),
			$w_js_ver,
			true
		);

		if ( is_post_type_archive( 'career' ) || is_tax( 'career_category' ) || is_singular( 'career' ) ) {
			wp_enqueue_style( 'career-manager-frontend' );
			wp_enqueue_script( 'career-manager-frontend' );
		}
	}

	/**
	 * Enqueue admin CSS/JS on Career post editing & settings screens.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( string $hook ): void {
		$screen    = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$post_type = $screen ? (string) $screen->post_type : '';
		$is_career = ( 'career' === $post_type ) || ( false !== strpos( $hook, 'career-manager-settings' ) );

		if ( ! $is_career ) {
			return;
		}

		if ( 'post.php' === $hook || 'post-new.php' === $hook ) {
			wp_enqueue_media();
		}

		$adm_css_rel = 'assets/css/career-manager-admin.css';
		$adm_js_rel  = 'assets/js/career-manager-admin.js';
		$adm_css_ver = file_exists( CAREER_PATH . $adm_css_rel ) ? (string) filemtime( CAREER_PATH . $adm_css_rel ) : CAREER_VERSION;
		$adm_js_ver  = file_exists( CAREER_PATH . $adm_js_rel ) ? (string) filemtime( CAREER_PATH . $adm_js_rel ) : CAREER_VERSION;

		wp_register_style(
			'career-manager-admin',
			CAREER_URL . $adm_css_rel,
			array(),
			$adm_css_ver
		);
		wp_enqueue_style( 'career-manager-admin' );

		wp_register_script(
			'career-manager-admin',
			CAREER_URL . $adm_js_rel,
			array( 'jquery' ),
			$adm_js_ver,
			true
		);
		wp_enqueue_script( 'career-manager-admin' );
	}

	/**
	 * Register Elementor Widget Category (`career-widgets`).
	 *
	 * @param object $elements_manager Elementor elements manager.
	 */
	public function register_elementor_category( $elements_manager ): void {
		if ( ! is_object( $elements_manager ) || ! method_exists( $elements_manager, 'add_category' ) ) {
			return;
		}

		$elements_manager->add_category(
			'career-widgets',
			array(
				'title' => esc_html__( 'Career Widgets', 'career-manager' ),
				'icon'  => 'fa fa-plug',
			)
		);
	}

	/**
	 * Register Career Archive Tabs Elementor Widget.
	 *
	 * @param object $widgets_manager Elementor widgets manager.
	 */
	public function register_elementor_widgets( $widgets_manager ): void {
		$widget_file = CAREER_PATH . 'widgets/career-archive-tabs/career-archive-tabs.php';
		if ( ! file_exists( $widget_file ) ) {
			return;
		}

		require_once $widget_file;

		if ( class_exists( 'CAREER_Widget_Archive_Tabs' ) && is_object( $widgets_manager ) && method_exists( $widgets_manager, 'register' ) ) {
			$widgets_manager->register( new \CAREER_Widget_Archive_Tabs() );
		}
	}

	/**
	 * Enqueue styles inside Elementor Editor preview.
	 */
	public function enqueue_elementor_editor_styles(): void {
		$this->register_and_enqueue_frontend_assets();
		wp_enqueue_style( 'career-manager-frontend' );
		wp_enqueue_style( 'career-manager-widget-tabs' );
	}
}

Career_Manager_Plugin::instance();

/**
 * Plugin Activation Hook.
 */
function career_activate(): void {
	career_register_cpt();
	career_register_taxonomy();
	career_register_application_cpt();
	career_seed_default_terms();

	if ( ! wp_next_scheduled( 'career_check_expired_cron' ) ) {
		wp_schedule_event( time(), 'hourly', 'career_check_expired_cron' );
	}

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'career_activate' );

/**
 * Plugin Deactivation Hook.
 */
function career_deactivate(): void {
	wp_clear_scheduled_hook( 'career_check_expired_cron' );
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'career_deactivate' );
