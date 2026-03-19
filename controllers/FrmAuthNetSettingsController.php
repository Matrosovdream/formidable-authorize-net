<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

if ( class_exists( 'FrmAuthNetSettingsController' ) ) {
	return;
}
/**
 * Settings Class (Controller)
 *
 * @package FrmAuthNet\Controllers
 */
class FrmAuthNetSettingsController {

	/**
	 * Add the settings section.
	 *
	 * @param $sections
	 * @return array
	 * @since 1.0
	 */
	public static function add_settings_section( $sections ) {
		$sections['authorize_net'] = array(
			'class'    => 'FrmAuthNetSettingsController',
			'function' => 'route',
			'name'     => 'Authorize.Net',
		);
		return $sections;
	}

	/**
	 * Display the settings form
	 *
	 * @param array  $errors
	 * @param string $message
	 * @since 1.0
	 */
	public static function display_form( $errors = array(), $message = '' ) {

		$settings = new FrmAuthNetSettings();
		self::load_css();

		if ( ! FrmAuthNetHelper::is_ssl() ) {
			$ssl_message = ' &mdash; ' . __( 'Please enable SSL in order to use live mode.', 'frmauthnet' );
			$ssl_disabled = true;
		} else {
			$ssl_message = '';
			$ssl_disabled = false;
		}

		require FrmAuthNetHelper::path() . '/views/settings/form.php';
	}

	/**
	 * Process the form.
	 *
	 * @since 1.0
	 */
	public static function process_form() {

		$errors   = array();
		$message  = '';

		$process_form = FrmAppHelper::get_post_param( 'process_form', '', 'sanitize_text_field' );
		if ( ! wp_verify_nonce( $process_form, 'process_form_nonce' ) ) {
			wp_die( 'You do not have permission to do that.' );
		}

		$settings = new FrmAuthNetSettings();

		$settings->update( $_POST );
		if ( empty( $errors ) ) {
			$settings->store();
			$message = __( 'Settings Saved', 'frmauthnet' );

			if ( ! empty( $settings->settings->transaction_key ) && ! empty( $settings->settings->login_id ) ) {
				self::maybe_create_webhook();
			}
		}

		self::display_form( $errors, $message );
	}

	/**
	 * Route (display or process the form)
	 *
	 * @since 1.0
	 */
	public static function route() {
		$action = isset( $_REQUEST['frm_action'] ) ? 'frm_action' : 'action';
		$action = FrmAppHelper::get_param( $action, '', 'get', 'sanitize_text_field' );

		if ( $action == 'process-form' ) {
			// Process the form
			return self::process_form();
		} else {
			// Display the form
			return self::display_form();
		}
	}

	/**
	 * When the settings change, check if a webhook needs to be (re)created.
	 *
	 * @since 2.0
	 */
	private static function maybe_create_webhook() {
		if ( self::has_webhook() ) {
			return;
		}

		$request = array(
			'method' => 'POST',
			'body'   => array(
				'name'       => 'Formidable',
				'url'        => self::webhook_url(),
				'eventTypes' => array(
					'net.authorize.payment.authcapture.created',
					'net.authorize.payment.authorization.created',
					'net.authorize.payment.capture.created',
					'net.authorize.payment.priorAuthCapture.created',
					'net.authorize.payment.void.created',
					'net.authorize.payment.refund.created',
					'net.authorize.customer.paymentProfile.created',
					'net.authorize.customer.paymentProfile.deleted',
					'net.authorize.customer.paymentProfile.updated',
					'net.authorize.customer.subscription.cancelled',
					'net.authorize.customer.subscription.created',
					'net.authorize.customer.subscription.expiring',
					'net.authorize.customer.subscription.suspended',
					'net.authorize.customer.subscription.terminated',
					'net.authorize.customer.subscription.updated',
				),
				'status'     => 'active',
			),
		);

		$api = new FrmAuthNetApi(
			array(
				'endpoint' => 'rest/v1/webhooks',
				'request'  => $request,
			)
		);

		$webhook = $api->remote_request();

		if ( is_object( $webhook ) && ! empty( $webhook->webhookId ) ) {
			self::set_webhook_created( $webhook->webhookId );
		}
	}

	/**
	 * Check if the webhook is already registered.
	 *
	 * @since 2.0
	 * @return bool True if webhook exists.
	 */
	private static function has_webhook() {
		$settings = new FrmAuthNetSettings();
		return ! empty( $settings->settings->webhook_created );
	}

	/**
	 * If the webhook was created, update the settings so it isn't created again.
	 *
	 * @since 2.0
	 */
	private static function set_webhook_created( $id ) {
		$settings = new FrmAuthNetSettings();
		$settings->settings->webhook_created = sanitize_text_field( $id );
		$settings->store();
	}

	/**
	 * @since 2.0
	 * @return string
	 */
	private static function webhook_url() {
		return set_url_scheme( rest_url( 'frm-authnet/v1/notify' ), 'https' );
	}

	/**
	 * Enqueue the admin scripts
	 *
	 * @since 1.0
	 */
	public static function load_css() {
		wp_register_style( 'frman_admin_css', FrmAuthNetHelper::get_file_url( 'assets/styles/frman.css' ), array(), 2 );
		wp_enqueue_style( 'frman_admin_css' );
	}

	/**
	 * Show a tooltip icon with the message passed.
	 *
	 * @since x.x
	 *
	 * @param string $message The message to be displayed in the tooltip.
	 * @param array  $atts    The attributes to be added to the tooltip.
	 *
	 * @return void
	 */
	public static function show_svg_tooltip( $message, $atts = array() ) {
		if ( ! is_callable( 'FrmAppHelper::tooltip_icon' ) ) {
			return;
		}
		FrmAppHelper::tooltip_icon( $message, $atts );
	}
}
