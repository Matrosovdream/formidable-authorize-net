<?php

class FrmAuthNetEcheckApiHelper {

	public static function refund_payment( $trans_id, $atts ) {

		$payment = $atts['payment'];
		$entry = FrmEntry::getOne( $payment->item_id, true );
		$action = FrmTransAction::get_single_action_type( $payment->action_id, 'payment' );

		$success = false;
		$bank_info = self::get_bank_info( $entry, $action );
		if ( empty( $bank_info ) ) {
			return;
		}

		$payment_atts = array(
			'entry'      => $entry,
			'action'     => $action,
			'form'       => $entry->form_id,
			'invoice_id' => $payment->id,
		);

		$api = new FrmAuthNetEcheck( $payment_atts );

		$amount = $payment->amount;
		$response = $api->process_refund( compact( 'trans_id', 'amount', 'bank_info' ) );
		if ( $response === true ) {
			$success = true;
		} else {
			// If the refund fails, attempt to void instead
			$response = $api->process_void( compact( 'trans_id' ) );
			$success = ( $response === true );
		}

		return $success;
	}

	private static function get_bank_info( $entry, $action ) {
		$bank_info = array();
		foreach ( array( 'bank_name', 'account_num', 'routing_num', 'billing_first_name', 'billing_last_name' ) as $info ) {
			$bank_info[ $info ] = isset( $entry->metas[ $action->post_content[ $info ] ] ) ? $entry->metas[ $action->post_content[ $info ] ] : '';
			if ( empty( $bank_info[ $info ] ) ) {
				return false;
			}
		}
		return $bank_info;
	}
}
