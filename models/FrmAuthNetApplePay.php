<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

if ( class_exists( 'FrmAuthNetApplePay' ) ) {
	return;
}

/**
 * ApplePay Payment Class (Model)
 *
 * Create ApplePay payment type.
 * {@inheritdoc}
 *
 * @package FrmAuthNet\Models
 */
class FrmAuthNetApplePay extends FrmAuthNetPayment {

	public $type = 'authnet_applepay';

	/**
	 * Get response for ApplePay
	 */
	public function get_response() {
		$transaction = $this->setup_transaction_type( 'authCaptureTransaction' );
		$request = $this->transaction_request( $transaction );
		$this->send_api_request( $request );
	}

	/**
	 * Setup the transaction type and setup the amount
	 *
	 * @param $type
	 * @return $transaction
	 */
	public function setup_transaction_type( $type ) {
		$transaction = array(
			'transactionType' => $type,
			'amount'          => $this->amount,
			'payment'         => array(
				'opaqueData' => array(
					'dataDescriptor' => 'COMMON.APPLE.INAPP.PAYMENT',
					'dataValue'      => '', // TODO: Need the DataValue
				),
			),
		);

		return $transaction;
	}
}
