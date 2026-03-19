<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

if ( class_exists( 'FrmAuthNetHooksController' ) ) {
	return;
}
/**
 * Hooks Class (Controller)
 *
 * @package FrmAuthNet\Controllers
 */
class FrmAuthNetHooksController {

	/**
	 * Load the FrmAuthNet Hooks.
	 *
	 * @since 1.0
	 */
	public static function load_hooks() {

		self::load_admin_hooks();

		register_activation_hook( dirname( dirname( __FILE__ ) ) . '/formidable-authorize-net.php', 'FrmAuthNetController::install' );

		add_action( 'init', 'FrmAuthNetController::load_lang', 0 );
		add_action( 'frm_payment_gateways', 'FrmAuthNetController::add_gateways' );
		add_action( 'rest_api_init', 'FrmAuthNetController::create_rest_routes', 0 );

		add_action( 'frm_payment_cron', 'FrmAuthNetEcheckActionsController::run_payment_cron' );
		add_shortcode( 'frm_payment_receipt', 'FrmAuthNetReceiptsController::receipt_shortcode' );

		add_filter( 'frm_include_credit_card', '__return_true' );
	}

	/**
	 * Load the admin hooks
	 */
	private static function load_admin_hooks() {
		// Make sure we are logged into the Admin area.
		if ( ! is_admin() ) {
			return;
		}

		self::load_ajax_hooks();

		add_action( 'after_plugin_row_formidable-authorize-net/formidable-authorize-net.php', 'FrmAuthNetController::min_version_notice' );
		add_action( 'admin_notices', 'FrmAuthNetController::get_started_headline' );
		add_action( 'admin_init', 'FrmAuthNetController::load_updater' );

		// Add Global Settings section
		add_action( 'frm_add_settings_section', 'FrmAuthNetSettingsController::add_settings_section' );
		add_action( 'frm_add_form_option_section', 'FrmAuthNetSettingsController::load_css' );

		// Load Authnet CSS
		add_filter( 'get_frm_stylesheet', 'FrmAuthNetController::load_css', 30 );
		add_filter( 'plugin_action_links_formidable-authorize-net/formidable-authorize-net.php', 'FrmAuthNetController::settings_link', 10, 2 );

		// Load default templates.
		//add_filter( 'frm_default_templates_files', 'FrmAuthNetController::custom_templates', 30 );
	}

	/**
	 * Load ajax hooks
	 */
	private static function load_ajax_hooks() {
		$doing_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;
		if ( ! $doing_ajax ) {
			return;
		}

		add_action( 'wp_ajax_frmauth_install', 'FrmAuthNetController::install' );
	}
}
