<?php

class FrmAuthNetReceiptsController {

	public static function receipt_shortcode( $atts ) {
		$defaults = array(
			'id'     => '',
			'layout' => 'top',
		);
		$atts = shortcode_atts( $defaults, $atts );

		$content = '';
		if ( ! empty( $atts['id'] ) && is_numeric( $atts['id'] ) ) {
			$content = self::get_receipt_for_entry( $atts['id'], $atts['layout'] );
		}

		return $content;
	}

	private static function get_receipt_for_entry( $entry_id, $layout = 'right' ) {
		$message = '';
		$receipt_values = self::get_mapped_field_values( $entry_id );
		$receipt_values['layout'] = $layout;
		$message = self::add_global_receipt_to_message( $message, $receipt_values );

		wp_enqueue_style( 'frm_auth_flex', FrmAuthNetHelper::get_file_url( 'assets/styles/frmflexbox.css' ), array(), 2 );

		return $message;
	}

	/**
	 * Build fields
	 * Get the mapped field values from custom fields
	 *
	 * @param int    $entry_id
	 * @return array $field_values
	 */
	private static function get_mapped_field_values( $entry_id ) {

		$entry = FrmEntry::getOne( $entry_id, true );

		$receipt_values = self::get_action_and_payment_for_receipt( $entry );
		$field_values = array(
			'amount'         => $receipt_values['payment']->amount,
			'invoice_number' => $receipt_values['payment']->invoice_id,
			'transaction_id' => $receipt_values['payment']->receipt_id,
			'status'         => $receipt_values['payment']->status,
		);

		$settings = $receipt_values['form_action']->post_content;
		self::add_customer_info( compact( 'settings', 'entry' ), $field_values );

		return $field_values;
	}

	/**
	 * Get the action and payment for the reciept
	 *
	 * @param $entry
	 * @return array
	 */
	private static function get_action_and_payment_for_receipt( $entry ) {
		global $frm_vars;

		$frm_payment = new FrmTransPayment();
		$payment_for_entry = $frm_payment->get_all_for_entry( $entry->id );
		if ( count( $payment_for_entry ) > 1 ) {
			$type = $frm_vars['frman']['type'];
			foreach ( $payment_for_entry as $check_payment ) {
				$form_action_id = $check_payment->action_id;
				$check_form_action = FrmTransAction::get_single_action_type( $check_payment->action_id, 'payment' );
				if ( $check_form_action->post_content[ 'authnet_' . $type ] ) {
					$payment = $check_payment;
					$form_action = $check_form_action;
					break;
				}
				unset( $check_payment, $check_form_action );
			}
		} else {
			$payment = reset( $payment_for_entry );
			$form_action = FrmTransAction::get_single_action_type( $payment->action_id, 'payment' );
		}
		return compact( 'payment', 'form_action' );
	}

	/**
	 * Add the customer info
	 *
	 * @param string            $atts
	 * @param string            $field_values
	 */
	private static function add_customer_info( $atts, &$field_values ) {
		$fill_fields = array(
			'billing_company',
			'billing_first_name',
			'billing_last_name',
			'billing_address',
			'email',
		);

		self::maybe_add_shipping_fields( $atts, $fill_fields );

		foreach ( $fill_fields as $field ) {
			$setting_field_id = $atts['settings'][ $field ];
			if ( $setting_field_id && isset( $atts['entry']->metas[ $setting_field_id ] ) ) {
				$field_values[ $field ] = maybe_unserialize( $atts['entry']->metas[ $setting_field_id ] );
			}
		}
	}

	/**
	 * Add shipping fields if shipping address is not the same as billing address
	 *
	 * @param string $atts
	 * @param string $fill_fields
	 */
	private static function maybe_add_shipping_fields( $atts, &$fill_fields ) {
		$use_shipping_address = $atts['settings']['use_shipping'];
		if ( $use_shipping_address ) {
			$shipping_fields = array(
				'shipping_first_name',
				'shipping_last_name',
				'shipping_company',
				'shipping_address',
			);
			$fill_fields = array_merge( $shipping_fields, $fill_fields );
		}
	}

	/**
	 * Build the recipt and add it to the message
	 *
	 * @param string $message
	 * @param string $receipt_values
	 */
	private static function add_global_receipt_to_message( $message, $receipt_values ) {
		$settings = new FrmAuthNetSettings();

		$reciept_heading_text = $settings->settings->reciept_heading_text;
		$reciept_footer_text = $settings->settings->reciept_footer_text;
		$receipt_placement = $receipt_values['layout'];

		$classes = FrmAuthNetHelper::get_receipt_classes();
		if ( isset( $classes[ $receipt_placement ] ) ) {
			$order_summary_class = $classes[ $receipt_placement ]['summary'];
			$receipt_class = $classes[ $receipt_placement ]['receipt'];
			$receipt_item_class = $classes[ $receipt_placement ]['receipt_item'];
			$receipt_item_wrap_class = $classes[ $receipt_placement ]['receipt_wrap'];
		}
		$receipt_item_dt_class = $classes['dt_class'];
		$receipt_item_dd_class = $classes['dd_class'];

		ob_start();
		require FrmAuthNetHelper::path() . '/views/receipt/receipt.php';
		$message .= ob_get_contents();
		ob_end_clean();
		return $message;
	}
}
