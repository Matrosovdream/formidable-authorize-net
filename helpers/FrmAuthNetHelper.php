<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

if ( class_exists( 'FrmAuthNetHelper' ) ) {
	return;
}

/**
 * Main Helper Class (Helper)
 *
 * @package FrmAuthNet\Helpers
 */
class FrmAuthNetHelper {

	/**
	 * Define the plugin path.
	 *
	 * @since 1.0
	 */
	public static function path() {

		return dirname( dirname( __FILE__ ) );
	}

	/**
	 * Get the url of a file inside the plugin.
	 *
	 * @since 1.0
	 */
	public static function get_file_url( $file = '' ) {

		return plugins_url( $file, dirname( __FILE__ ) );
	}

	/**
	 * Allow local site to see and use Authorize settings
	 *
	 * @since 1.0
	 */
	public static function is_ssl() {
		$is_ssl = is_ssl();
		if ( ! $is_ssl ) {
			$localhost = array( '127.0.0.1', '::1' );
			$is_ssl = in_array( FrmAppHelper::get_server_value( 'REMOTE_ADDR' ), $localhost );

			if ( ! $is_ssl ) {
				$settings = new FrmAuthNetSettings();
				$is_sandbox = ( 'sandbox' == $settings->settings->environment );
				if ( $is_sandbox ) {
					$is_ssl = true;
				}
			}
		}

		return $is_ssl;
	}

	public static function get_classes_for_option( $gateway, $selected ) {
		$classes = 'frm_gateway_setting show_authnet_' . $gateway;
		if ( ! in_array( 'authnet_' . $gateway, $selected ) ) {
			$classes .= ' frm_hidden';
		}
		return $classes;
	}

	/**
	 * @since 1.0
	 */
	public static function get_invoice_pattern( $entry_id ) {
		$invoice_num = $entry_id . date( 'mdY' );
		return apply_filters( 'frm_authnet_invoice_num', $invoice_num, compact( 'entry_id' ) );
	}

	public static function get_status_from_auth( $status ) {
		$status_options = array(
			'settledSuccessfully'       => 'complete',
			'refundSettledSuccessfully' => 'refuned',
			'refundPendingSettlement'   => 'refunded',
			'voided'                    => 'refunded',
			'returnedItem'              => 'refunded',
			'declined'                  => 'failed',
			'failedReview'              => 'failed',
			'expired'                   => 'failed',
			'communicationError'        => 'failed',
			'settlementError'           => 'failed',
			'generalError'              => 'failed',
		);
		return ( isset( $status_options[ $status ] ) ? $status_options[ $status ] : 'pending' );
	}

	/**
	 * Return array of reciept classes
	 *
	 * @return array
	 * @since 1.0
	 */
	public static function get_receipt_classes() {
		$placement_classes = array(
			'top'    => array(
				'summary'      => ' frm_col_xs_12 frm_last_xs',
				'receipt'      => ' frm_col_xs_12 frm_first_xs',
				'receipt_item' => ' frm_card_deck',
				'receipt_wrap' => '',
			),
			'right'  => array(
				'summary'      => ' frm_col_xs_7 frm_first_xs',
				'receipt'      => ' frm_col_xs_5 frm_last_xs',
				'receipt_item' => ' frm_col_xs_12',
				'receipt_wrap' => ' frm_row',
			),
			'bottom' => array(
				'summary'      => ' frm_col_xs_12 frm_first_xs',
				'receipt'      => ' frm_col_xs_12 frm_last_xs',
				'receipt_item' => ' frm_card_deck',
				'receipt_wrap' => '',
			),
			'left'   => array(
				'summary'      => ' frm_col_xs_7 frm_last_xs',
				'receipt'      => ' frm_col_xs_5 frm_first_xs',
				'receipt_item' => ' frm_col_xs_12',
				'receipt_wrap' => ' frm_row',
			),
			'dt_class' => ' frm_col_xs_12 frm_col_md_5 frm_text_left_xs frm_text_right_md frm_m_b_1',
			'dd_class' => ' frm_col_xs_12 frm_col_md_7 frm_text_left_xs frm_m_b_1',
		);

		return $placement_classes;
	}

	/**
	 * Converts price string to value.
	 *
	 * @since 2.02
	 *
	 * @param string $price_str Price string.
	 * @return float|string
	 */
	public static function convert_price_string_to_value( $price_str, $form ) {
		if ( is_numeric( $price_str ) || ! method_exists( 'FrmProCurrencyHelper', 'prepare_price' ) ) {
			return $price_str;
		}

		$currency = FrmProCurrencyHelper::get_currency( $form );

		return FrmProCurrencyHelper::prepare_price( html_entity_decode( $price_str ), $currency );
	}
}
