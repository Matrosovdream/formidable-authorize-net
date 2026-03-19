<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

if ( class_exists( 'FrmAuthNetAim' ) ) {
	return;
}

/**
 * AIM Payment Class (Model)
 *
 * Create a payment used by the AIM type
 * {@inheritdoc}
 *
 * @package FrmAuthNet\Models
 */
class FrmAuthNetAim extends FrmAuthNetPayment {

	public $type = 'authnet_aim';

	protected $request_type = 'createTransactionRequest';

	/**
	 * Get the information from the form
	 */
	public function get_response() {

		$transaction = $this->setup_transaction_type( 'authCaptureTransaction' );

		$this->fill_card_info( $transaction );
		$this->fill_order_info( $transaction );
		$this->fill_customer_info( $transaction );
		$this->fill_address_info( $transaction );
		$transaction['customerIP'] = FrmAppHelper::get_ip_address();

		$request = $this->transaction_request( $transaction );

		$this->send_api_request( $request );
	}

	public function payment_for_refund( $atts ) {
		return array(
			'creditCard' => array(
				'cardNumber'     => $atts['cc_number'],
				'expirationDate' => 'XXXX',
			),
		);
	}
}
