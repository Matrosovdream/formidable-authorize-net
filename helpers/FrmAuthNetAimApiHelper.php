<?php

class FrmAuthNetAimApiHelper {

	public static function refund_payment( $trans_id, $atts ) {

		// Delay for 1 second
		//sleep(1);

		$payment = $atts['payment'];
		$entry   = FrmEntry::getOne( $payment->item_id, true );
		$action  = FrmTransAction::get_single_action_type( $payment->action_id, 'payment' );

		$payment_atts = [
			'entry'      => $entry,
			'action'     => $action,
			'form'       => $entry->form_id,
			'invoice_id' => $payment->id,
		];
	
		$aim = new FrmAuthNetAim( $payment_atts );
	
		$cc_field = $entry->metas[ $action->post_content['credit_card'] ] ?? '';
		if ( empty( $cc_field ) || empty( $cc_field['cc'] ) ) {
			return new WP_Error('authnet_error', 'Missing credit card data');
		}
	
		$cc_number = substr( $cc_field['cc'], -4 );
		if ( empty( $cc_number ) ) {
			return new WP_Error('authnet_error', 'Invalid card number');
		}
	
		$amount = $payment->amount;


		$refData = [
			'trans_id' => $trans_id,
			'amount'   => $amount,
			'cc_number'=> $cc_number,
		];
	
		// 1. TRY REFUND
		$refund = $aim->process_refund([
			'trans_id' => $trans_id,
			'amount'   => $amount,
			'cc_number'=> $cc_number,
		]);
		if ( $refund == 1 ) { return true; }

		// 2. TRY VOID
		$void = $aim->process_void([
			'trans_id' => $trans_id,
		]);

		if ( $void == 1 ) { return true; }
	
		// 3. BOTH FAILED
		$error = is_string($void) ? $void : (is_string($refund) ? $refund : 'Refund and void failed');
		$error = strip_tags($error);

		// For debugging
		//$error .= ' (Transaction ID: ' . $trans_id . '; Refund result: ' . (is_string($refund) ? $refund : 'unknown') . '; Void result: ' . (is_string($void) ? $void : 'unknown') . ')';
		
		return new WP_Error('authnet_error', $error);
	}

}


