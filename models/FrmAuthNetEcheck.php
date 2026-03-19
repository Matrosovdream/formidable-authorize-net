<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

if ( class_exists( 'FrmAuthNetEcheck' ) ) {
	return;
}

/**
 * Echeck Payment Class (Model)
 *
 * Create a payment used by the Echeck type
 * {@inheritdoc}
 *
 * @package FrmAuthNet\Models
 */
class FrmAuthNetEcheck extends FrmAuthNetPayment {

	public $type = 'authnet_echeck';

	protected $request_type = 'createTransactionRequest';

	public function process_payment() {
		$this->get_response();
		$is_complete = $this->is_payment_successful();

		$this->update_invoice( $is_complete ? 'pending' : 'failed' );
		return $is_complete ? $this->invoice_id : 0;
	}

	/**
	 * Get the information from the form
	 */
	public function get_response() {

		$transaction = $this->setup_transaction_type( 'authCaptureTransaction' );

		$this->fill_bank_info( $transaction );
		$this->fill_order_info( $transaction );
		$this->fill_customer_info( $transaction );
		$this->fill_address_info( $transaction );

		$transaction['customerIP'] = FrmAppHelper::get_ip_address();

		$request = $this->transaction_request( $transaction );
		$this->send_api_request( $request );
	}

	public function fill_bank_info( &$transaction ) {
		$bank_account = array(
			'accountType' => 'checking', // checking, businessChecking, or savings
		);

		$mapping = $this->get_bank_mapping();
		$this->fill_info_from_map( $mapping, $bank_account );
		$this->fill_name( $bank_account );

		$bank_account['echeckType'] = 'WEB';
		$this->fill_bank_name( $bank_account );

		$transaction['payment']['bankAccount'] = $bank_account;
	}

	public function get_bank_mapping() {
		$mapping = array(
			'routingNumber' => $this->action->post_content['routing_num'],
			'accountNumber' => $this->action->post_content['account_num'],
		);
		return $mapping;
	}

	public function fill_bank_name( &$bank_account ) {
		$bank_name = $this->get_value_from_field(
			array(
				'field_id' => $this->action->post_content['bank_name'],
			)
		);

		$bank_account['bankName'] = $bank_name;
	}

	public function fill_name( &$bank_account ) {
		$full_name = $this->get_value_from_field(
			array(
				'field_id'    => $this->action->post_content['billing_first_name'],
				'mapping_key' => 'firstName',
			)
		);
		$full_name .= ' ' . $this->get_value_from_field(
			array(
				'field_id'    => $this->action->post_content['billing_last_name'],
				'mapping_key' => 'lastName',
			)
		);
		$bank_account['nameOnAccount'] = $full_name;
	}

	public function payment_for_refund( $atts ) {
		$bank_info = $atts['bank_info'];

		return array(
			'bankAccount' => array(
				'routingNumber' => $bank_info['routing_num'],
				'accountNumber' => $bank_info['account_num'],
				'nameOnAccount' => $bank_info['billing_first_name'] . ' ' . $bank_info['billing_last_name'],
			),
		);
	}
}
