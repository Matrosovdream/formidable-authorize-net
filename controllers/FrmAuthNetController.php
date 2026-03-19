<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

if ( class_exists( 'FrmAuthNetController' ) ) {
	return;
}

/**
 * Main Form Class (Controller)
 *
 * @package FrmAuthNet\Controllers
 */
class FrmAuthNetController {

	/*
	 * @var string
	 * @var int
	 * @var string
	 */
	public static $min_version = '2';
	public static $db_version = 9;
	public static $db_opt_name = 'frm_authnet_db_version';

	public static function add_gateways( $gateways ) {
		$gateways['authnet_aim'] = array(
			'label'     => 'Authorize.net',
			'user_label' => __( 'Credit Card', 'frmauthnet' ),
			'class'     => 'AuthNetAim',
			'recurring' => false,
			'include'   => array(
				'credit_card',
				'billing_first_name',
				'billing_last_name',
				'billing_company',
				'billing_address',
				'use_shipping',
				'shipping_first_name',
				'shipping_last_name',
				'shipping_company',
				'shipping_address',
			),
		);

		$gateways['authnet_echeck'] = array(
			'label'     => 'eCheck',
			'user_label' => __( 'eCheck', 'frmauthnet' ),
			'class'     => 'AuthNetEcheck',
			'recurring' => false,
			'include'   => array(
				'bank_account',
				'billing_first_name',
				'billing_last_name',
				'billing_company',
				'billing_address',
				'use_shipping',
				'shipping_first_name',
				'shipping_last_name',
				'shipping_company',
				'shipping_address',
			),
		);

		return $gateways;
	}

	/**
	 * Load Front end CSS for receipt pages.
	 *
	 * @since 1.0
	 *
	 * @param array $css_file
	 * @return array
	 */
	public static function load_css( $css_file ) {
		if ( is_admin() ) {
			$css_file['form_auth'] = FrmAuthNetHelper::get_file_url( 'assets/styles/frman.css' );
		}

		return $css_file;
	}

	/**
	 * Template files.
	 *
	 * @param array $template_files
	 *
	 * @since 1.0
	 */
	public static function custom_templates( $template_files ) {

		$template_files[] = FrmAuthNetHelper::path() . '/assets/forms/Formidable_Authorize-net_AIM_form.xml';
		return $template_files;
	}

	/**
	 * Load the Formidable Authorize.net addon lanugage file.
	 *
	 * @since 1.0
	 */
	public static function load_lang() {

		load_plugin_textdomain( 'frmauthnet', false, 'formidable-authorize-net/languages/' );
	}

	/**
	 * Formidable minimum requirements.
	 *
	 * @since 1.0
	 */
	public static function min_version_notice() {

		$frm_version = is_callable( 'FrmAppHelper::plugin_version' ) ? FrmAppHelper::plugin_version() : 0;

		// check if Formidable meets minimum requirements
		if ( version_compare( $frm_version, self::$min_version, '>=' ) ) {
			return;
		}

		$wp_list_table = _get_list_table( 'WP_Plugins_List_Table' );
		echo '<tr class="plugin-update-tr active"><th colspan="' . esc_attr( $wp_list_table->get_column_count() ) . '" class="check-column plugin-update colspanchange"><div class="update-message">' .
		esc_html__( 'You are running an outdated version of Formidable. This plugin may not work correctly if you do not update Formidable.', 'frmauthnet' ) .
		'</div></td></tr>';
	}

	/**
	 * Global settings tab.
	 *
	 * @param string $links
	 * @param string $file
	 *
	 * @since 1.0
	 */
	public static function settings_link( $links, $file ) {

		$settings = '<a href="' . esc_url( admin_url( 'admin.php?page=formidable-settings&t=authorize_net_settings' ) ) . '">' .
			__( 'Settings', 'frmauthnet' ) .
			'</a>';
		array_unshift( $links, $settings );

		return $links;
	}

	/**
	 * @since 1.0
	 */
	public static function load_updater() {

		if ( class_exists( 'FrmAddon' ) ) {
			FrmAuthNetUpdate::load_hooks();
		}
	}

	/**
	 * @param bool $old_db_version
	 *
	 * @since 1.0
	 */
	public static function install( $old_db_version = false ) {

		$frm_authnet_db = new FrmAuthNetDb();
		$frm_authnet_db->upgrade( $old_db_version );

		FrmTransAppController::install( $old_db_version );
	}

	/**
	 * @since 1.0
	 */
	public static function get_started_headline() {

		// Don't display this error as we're upgrading
		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
		// Return if the action is upgrade and it is not activated
		if ( 'upgrade-plugin' == $action && ! isset( $_GET['activate'] ) ) {
			return;
		}

		$db_version = get_option( self::$db_opt_name );
		if ( (int) $db_version < self::$db_version ) {
			if ( is_callable( 'FrmAppHelper::plugin_url' ) ) {
				$url = FrmAppHelper::plugin_url();
			} else {
				return;
			}
			?>
			<div class="error" id="frmauth_install_message" style="padding:7px;">
				<?php esc_html_e( 'Your Formidable Authorize.Net database needs to be updated. Please deactivate and reactivate the plugin to fix this or', 'frmauthnet' ); ?>
				<a id="frmauth_install_link" href="javascript:frmauth_install_now()">
					<?php esc_html_e( 'Update Now', 'frmauthnet' ); ?>
				</a>
			</div>
			<script type="text/javascript">
				function frmauth_install_now(){
					jQuery( '#frmauth_install_link' ).replaceWith('<img src="<?php echo esc_url_raw( $url ); ?>/images/wpspin_light.gif" alt="<?php esc_attr_e( 'Loading&hellip;' ); ?>" />');
					jQuery.ajax({
						type:'POST',
						url:"<?php echo esc_url_raw( admin_url( 'admin-ajax.php' ) ); ?>",
						data:'action=frmauth_install',
						success:function( msg ){
							jQuery( "#frmauth_install_message" ).fadeOut( 'slow' );
						}
					});
				};
			</script>
			<?php

		}
	}

	// TODO: Remove entry_columns?
	// public static function entry_columns($columns)
	// {
	// if (is_callable('FrmForm::get_current_form_id')) {
	// $form_id = FrmForm::get_current_form_id();
	// }
	// else {
	// $form_id = FrmEntriesHelper::get_current_form_id();
	// }
	// $columns[ $form_id.'_payments' ] = __( 'Payments', 'frmauthnet');
	// $columns[ $form_id.'_current_payment' ] = __( 'Paid', 'frmauthnet');
	// $columns[ $form_id.'_payment_expiration' ] = __( 'Expiration', 'frmauthnet');
	//
	// return $columns;
	// }

	/**
	 * Setup the REST API routes for Auth.net webhooks.
	 *
	 * @since 2.0
	 */
	public static function create_rest_routes() {
		$api = new FrmAuthNetWebhook();
		$api->register_routes();
	}

	/**
	 * Listens for void and refund notifications from Authorize.net
	 * @phpcs:disable WordPress.Security.NonceVerification.Missing
	 *
	 * @since 1.0
	 * @deprecated 2.0
	 */
	public static function authnet_ipn() {
		_deprecated_function( __METHOD__, '2.0', 'FrmAuthNetController::webhook_notifications' );

		$settings = new FrmAuthNetSettings();

		$txt = '';
		foreach ( $_POST as $key => $value ) {
			$txt .= sanitize_text_field( $key . ': ' . $value ) . PHP_EOL;
		}
		FrmTransLog::log_message( $txt );

		$type = FrmAppHelper::get_post_param( 'x_type', '', 'sanitize_text_field' );
		$response_code = FrmAppHelper::get_post_param( 'x_response_reason_code', '', 'sanitize_text_field' );

		if ( ( $type == 'void' || $type == 'credit' ) && $response_code == 1 ) {
			$invoice_num = FrmAppHelper::get_post_param( 'invoice_number', '', 'sanitize_text_field' );
			if ( empty( $invoice_num ) ) {
				FrmTransLog::log_message( __( 'The Authorize.Net IPN invoice is invalid.', 'frmauthnet' ) );
				return;
			}

			$frm_authnet = new FrmTransPayment();
			$invoice = $frm_authnet->get_one_by( $invoice_num, 'invoice_id' );
			if ( ! $invoice ) {
				FrmTransLog::log_message( __( 'The Authorize.Net IPN invoice is invalid.', 'frmauthnet' ) );
				return;
			}

			$trans_id = FrmAppHelper::get_post_param( 'x_trans_id', '', 'sanitize_text_field' );
			$status = ( $type == 'credit' ) ? 'refunded' : 'void';

			/* translators: %1$s: Payment status, %2$s: Transaction ID */
			$note = sprintf( __( 'Payment %1$s: %2$s', 'frmauthnet' ), $status, $trans_id );
			$meta_value = FrmTransAppHelper::add_meta_to_payment( $invoice->meta_value, $note );

			$frm_authnet->update(
				$invoice->id,
				array(
					'meta_value' => $meta_value,
					'status'     => $status,
				)
			);
		}

		wp_die();
	}
}
