<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Handle Recurring Payments.
 *
 * @since 2.0
 */
class FrmAuthNetRecur extends FrmAuthNetPayment {

	public $type = 'authnet_aim';

	protected $request_type = 'ARBCreateSubscriptionRequest';

	/**
	 * Get the information from the form
	 */
	public function get_response() {

		$transaction = $this->setup_transaction_type( $this->request_type );

		$this->fill_card_info( $transaction );
		// $this->fill_bank_info( $transaction ); // Use a bank account instead of card.

		// TODO: is opaqueData really required in the payment array?

		$this->fill_order_info( $transaction );
		$this->fill_customer_info( $transaction );
		$this->fill_address_info( $transaction );

		$this->send_api_request( $transaction );
	}

	/**
	 * Setup the subscription with the amount
	 *
	 * @param $type
	 * @return array
	 */
	public function setup_transaction_type( $type ) {
		$transaction = array(
			'name'                 => $this->action->post_content['description'],
			'paymentSchedule'      => array(
				'interval'         => $this->payment_interval(),
				'startDate'        => $this->get_date_in_mst(),
				'totalOccurrences' => 9999,
			),
			'amount'               => $this->amount,
		);

		$this->add_trial( $transaction );

		return $transaction;
	}

	/**
	 * Set the payment interval on the subscription.
	 * Allowed: 7-365 days or 1-12 months.
	 *
	 * @since 2.0
	 */
	private function payment_interval() {
		$length = $this->action->post_content['interval_count'];
		$unit   = $this->action->post_content['interval'];

		if ( $unit === 'weeks' ) {
			$length = $length * 7;
			$unit   = 'days';
		} elseif ( $unit === 'years' ) {
			$length = $length * 12;
			$unit   = 'months';
		}

		return array(
			'length' => $length,
			'unit'   => $unit,
		);
	}

	/**
	 * Auth.net has issues if the date isn't in MST.
	 *
	 * @since 2.0
	 */
	private function get_date_in_mst() {
		$date = new DateTime( 'now', new DateTimeZone( 'America/Denver' ) );
		return $date->format( 'Y-m-d' );
	}

	/**
	 * If there is a trial, trigger the trial payment and adjust the
	 * subscription start date.
	 *
	 * @since 2.0
	 */
	private function add_trial( &$transaction ) {
		//$transaction['paymentSchedule']['trialOccurrences'] = 1;
		//$transaction['trialAmount'] = 0;
	}
}
