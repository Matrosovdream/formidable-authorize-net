<?php

/**
 * @since 2.0
 */
class FrmAuthNetWebhook extends WP_REST_Controller {

	protected $status;

	protected $transaction_id = 0;

	protected $response;

	/**
	 * @since 2.0
	 */
	public function register_routes() {
		$route = array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'webhook_notifications' ),
			'permission_callback' => array( $this, 'verify_webhook' ),
		);

		register_rest_route( 'frm-authnet/v1', '/notify', $route );
	}

	/**
	 * @since 2.0
	 * @param WP_REST_Request $request Full details about the request.
	 */
	public function webhook_notifications( $request ) {
		$body   = $request->get_body();
		$params = json_decode( $body, true );
		$this->prepare_params( $params );

		$event_type = str_replace( 'net.authorize.', '', sanitize_text_field( $params['eventType'] ) );

		$this->response = array(
			'response' => 'No action taken',
			'success'  => false,
		);

		$this->transaction_id = $params['payload']['id'];

		$events = array(
			'payment.authcapture.created' => 'complete',
			'payment.capture.created'     => 'complete',
			'payment.fraud.approved'      => 'complete',
			'payment.refund.created'      => 'refunded',
			'payment.void.created'        => 'void',
		);

		$cancel_sub = array(
			'customer.subscription.suspended',
			'customer.subscription.terminated',
			'customer.subscription.cancelled',
		);

		if ( isset( $events[ $event_type ] ) ) {
			if ( $params['payload']['responseCode'] > 1 && $events[ $event_type ] === 'complete' ) {
				$events[ $event_type ] = 'failed';
			}

			$this->status = $events[ $event_type ];
			$this->set_payment_status();
		} elseif ( in_array( $event_type, $cancel_sub ) ) {
			$this->status = 'canceled';
			$this->subscription_canceled();
		} else {
			$this->response['response'] = 'Webhook not processed: ' . $event_type;
			FrmTransLog::log_message( 'Webhook not processed: ' . $event_type );
		}

		return rest_ensure_response( $this->response );
	}

	/**
	 * @since 2.0
	 */
	private function prepare_params( &$params ) {
		foreach ( $params as $key => $value ) {
			if ( is_array( $value ) ) {
				$this->prepare_params( $value );
			} else {
				$params[ $key ] = sanitize_text_field( $value );
			}
		}
	}

	/**
	 * @since 2.0
	 */
	private function set_payment_status() {
		$transaction = $this->get_transaction_details();
		if ( empty( $transaction ) ) {
			$this->response['response'] = 'no transaction found';
			return;
		}

		$frm_payment = new FrmTransPayment();
		$payment     = $frm_payment->get_one_by( $this->transaction_id, 'receipt_id' );

		if ( empty( $payment ) && ( $this->status === 'refunded' || $this->status === 'failed' ) ) {
			// If the refunded payment doesn't exist, stop here.
			FrmTransLog::log_message( 'No action taken. The failed/refunded payment does not exist.', 'frmauthnet' );
			$this->response['response'] = 'no payment exists';
			return;
		}

		$run_triggers = false;

		if ( empty( $payment ) ) {
			$run_triggers = true;
			$payment      = $this->prepare_from_invoice();
			if ( empty( $payment ) ) {
				$this->response['response'] = 'Payment not created';
				return;
			}
		} elseif ( $payment->status != $this->status ) {
			$payment_values           = (array) $payment;
			$payment->status          = $this->status;
			$payment_values['status'] = $this->status;

			/* translators: %s: Payment status */
			$note = sprintf( __( 'Payment %s', 'formidable-payments' ), $payment_values['status'] );
			FrmTransAppHelper::add_note_to_payment( $payment_values, $note );

			$frm_payment->update( $payment->id, $payment_values );

			$this->response = array(
				'response' => 'Payment ' . $payment->id . ' was updated',
				'success'  => true,
			);
			$run_triggers = true;
		} else {
			$this->response = array(
				'response' => 'Payment ' . $payment->id . ' already up to date',
				'success'  => true,
			);
		}

		if ( $run_triggers && $payment && $payment->action_id ) {
			$atts = array(
				'status'  => $this->status,
				'payment' => $payment,
			);
			FrmTransActionsController::trigger_payment_status_change( $atts );
		}
	}

	/**
	 * @since 2.0
	 */
	private function get_transaction_details() {
		$frm_api = new FrmAuthNetTransaction();
		$details = $frm_api->get_transaction_details( $this->transaction_id );

		if ( is_string( $details ) ) {
			FrmTransLog::log_message( 'Transaction details not found for ' . $this->transaction_id );
			$this->response = array(
				'response' => 'Transaction details not found',
				'success'  => false,
			);
			return false;
		}

		return $details;
	}

	/**
	 * @since 2.0
	 */
	private function prepare_from_invoice() {
		$transaction = $this->get_transaction_details();
		if ( is_string( $transaction ) || empty( $transaction->subscription->id ) ) {
			// This isn't a subscription.
			FrmTransLog::log_message( 'No action taken since this is not a subscription.' );
			$this->response = array(
				'response' => 'Invoice missing',
				'success'  => false,
			);
			return false;
		}

		$subscription_id = $transaction->subscription->id;

		$sub = $this->get_subscription( $subscription_id );
		if ( ! $sub ) {
			return false;
		}

		$payment = $this->get_payment_for_sub( $sub->id );

		$payment_values = (array) $payment;
		$this->set_payment_values( $transaction, $payment_values );

		$frm_payment = new FrmTransPayment();

		$is_first_payment = ( $payment->receipt_id == '' );
		if ( $is_first_payment ) {
			// the first payment for the subscription needs to be updated with the receipt id
			$frm_payment->update( $payment->id, $payment_values );
			$payment_id = $payment->id;
		} else {
			// if this isn't the first, create a new payment
			$payment_id = $frm_payment->create( $payment_values );
		}

		$this->update_next_bill_date( $sub, $payment_values );

		$payment = $frm_payment->get_one( $payment_id );

		if ( $payment ) {
			$this->response = array(
				'response' => 'Payment ' . $payment->id . ' was recorded.',
				'success'  => true,
			);
		}

		return $payment;
	}

	/**
	 * @since 2.0
	 */
	private function get_subscription( $sub_id ) {
		$frm_sub = new FrmTransSubscription();
		$sub = $frm_sub->get_one_by( $sub_id, 'sub_id' );
		if ( ! $sub ) {
			// If this isn't an existing subscription, it must be a charge for another site/plugin
			FrmTransLog::log_message( 'No action taken since there is not a matching subscription for ' . $sub_id );
			$this->response = array(
				'response' => 'Invoice missing',
				'success'  => false,
			);
		}

		return $sub;
	}

	/**
	 * @since 2.0
	 */
	private function get_payment_for_sub( $sub_id ) {
		$frm_payment = new FrmTransPayment();
		return $frm_payment->get_one_by( $sub_id, 'sub_id' );
	}

	/**
	 * @since 2.0
	 *
	 * @param object $transation The transaction details fetched from Auth.net.
	 * @param array $payment_values
	 */
	private function set_payment_values( $transation, &$payment_values ) {
		$payment_values['begin_date']  = date( 'Y-m-d' );
		$payment_values['expire_date'] = $this->get_end_date( $payment_values );
		$payment_values['amount']      = number_format( ( $transation->settleAmount / 100 ), 2, '.', '' );
		$payment_values['receipt_id']  = $this->transaction_id;
		$payment_values['status']      = $this->status;
		$payment_values['meta_value']  = array();
		$payment_values['created_at']  = current_time( 'mysql', 1 );

		FrmTransAppHelper::add_note_to_payment( $payment_values );
	}


	/**
	 * Get the date from the settings or from the connected entry.
	 *
	 * @since 2.0
	 *
	 * @param array $payment_values
	 *
	 * @return string The next bill date
	 */
	private function get_end_date( $payment_values ) {
		$action   = FrmTransAction::get_single_action_type( $payment_values['action_id'], 'payment' );
		$settings = $action->post_content;

		if ( ! isset( $settings['interval_count'] ) || empty( $settings['interval_count'] ) ) {
			return '0000-00-00';
		}

		$interval = $settings['interval'];
		$count    = $settings['interval_count'];

		if ( ! is_numeric( $count ) ) {
			$count = FrmTransAppHelper::process_shortcodes(
				array(
					'value' => $count,
					'entry' => FrmEntry::getOne( $payment_values['item_id'] ),
				)
			);
		}

		return date( 'Y-m-d', strtotime( '+' . absint( $count ) . ' ' . $interval ) );
	}

	/**
	 * @since 2.0
	 */
	private function subscription_canceled() {
		$transation      = $this->get_transaction_details();
		$subscription_id = $transation->subscription->id;
		$sub             = $this->get_subscription( $subscription_id );
		if ( ! $sub ) {
			return;
		}

		if ( $sub->status == $this->status ) {
			FrmTransLog::log_message( 'No action taken since the subscription is already canceled.' );
			$this->response = array(
				'response' => 'Already canceled',
				'success'  => true,
			);
			return;
		}

		FrmTransSubscriptionsController::change_subscription_status(
			array(
				'status' => $this->status,
				'sub'    => $sub,
			)
		);

		$this->response = array(
			'response' => 'Canceled subscription',
			'success'  => true,
		);
	}

	/**
	 * @since 2.0
	 *
	 * @param object $sub
	 * @param array $payment
	 */
	private function update_next_bill_date( $sub, $payment ) {
		$frm_sub = new FrmTransSubscription();
		if ( $payment['status'] == 'complete' ) {
			$frm_sub->update( $sub->id, array( 'next_bill_date' => $payment['expire_date'] ) );
		} elseif ( $payment['status'] == 'refunded' ) {
			$frm_sub->update( $sub->id, array( 'next_bill_date' => $payment['begin_date'] ) );
		}
	}

	/**
	 * Use HMAC-SHA512 to compare the signature key to ensure
	 * the request is coming from Auth.net
	 *
	 * @since 2.0
	 * @param WP_REST_Request $request Full details about the request.
	 */
	public function verify_webhook( $request ) {
		$settings         = new FrmAuthNetSettings();
		$secret           = $settings->settings->signature_key;
		$sent_signature   = FrmAppHelper::get_server_value( 'HTTP_X_ANET_SIGNATURE' );
		if ( ! empty( $sent_signature ) ) {
			$sent_signature = explode( '=', $sent_signature );
			$sent_signature = $sent_signature[1];
		}
		$actual_signature = hash_hmac( 'sha512', $request->get_body(), $secret );

		if ( strtoupper( $sent_signature ) != strtoupper( $actual_signature ) ) {
			return new WP_Error( 'rest_forbidden_context', __( 'Signature verification failed.', 'frmauthnet' ), array( 'status' => 403 ) );
		}

		return true;
	}
}
