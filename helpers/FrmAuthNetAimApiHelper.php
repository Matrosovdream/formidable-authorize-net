<?php

class FrmAuthNetAimApiHelper {

	public static function refund_payment( $trans_id, $atts ) {

		$payment = $atts['payment'];
		$entry   = FrmEntry::getOne( $payment->item_id, true );
		$action  = FrmTransAction::get_single_action_type( $payment->action_id, 'payment' );

		$payment_atts = array(
			'entry'      => $entry,
			'action'     => $action,
			'form'       => $entry->form_id,
			'invoice_id' => $payment->id,
		);

		$aim = new FrmAuthNetAim( $payment_atts );
		$cc_field = isset( $entry->metas[ $action->post_content['credit_card'] ] ) ? $entry->metas[ $action->post_content['credit_card'] ] : '';
		if ( empty( $cc_field ) ) {
			return false;
		}

		$cc_number = substr( $cc_field['cc'], strlen( $cc_field['cc'] ) - 4 );
		if ( empty( $cc_number ) ) {
			return false;
		}
		

		$amount   = $payment->amount;
		$response = $aim->process_refund( compact( 'trans_id', 'amount', 'cc_number' ) );

		// Check if it's an error
		foreach ( AUTHNET_ERROR_CODES as $code => $message ) {
			if ( strpos( $message, trim($code) ) !== false ) {
				return new WP_Error( 'authnet_error', $message );
			}
		}

		if ( $response === true ) {
			$success = true;
		} else {
			// If the refund fails, attempt to void instead
			$response = $aim->process_void( compact( 'trans_id' ) );
			$success = ( $response === true );
		}

		return $success;
	}
}
