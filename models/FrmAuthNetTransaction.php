<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

if ( class_exists( 'FrmAuthNetTransaction' ) ) {
	return;
}

/**
 *
 * @package FrmAuthNet\Models
 */
class FrmAuthNetTransaction extends FrmAuthNetPayment {

	/**
	 * Setup attributes used across payments
	 *
	 * @param $atts
	 */
	public function __construct( $atts = array() ) {
		// Don't use the parent constructor.
	}

	/**
	 * Get Transaction Details
	 *
	 * @param int $id
	 * @return mixed
	 */
	public function get_transaction_details( $id ) {
		$request = array(
			'merchantAuthentication' => $this->setup_api(),
			'transId'                => $id,
		);

		$this->request_type = 'getTransactionDetailsRequest';
		$this->send_api_request( $request );

		$response = $this->translate_response();
		if ( $response === true ) {
			$response = $this->response->transaction;
		}

		return $response;
	}

	/**
	 * Get Transaction List
	 *
	 * @param int $batch_id
	 * @return mixed
	 */
	public function get_transaction_list( $batch_id ) {
		$request = array(
			'merchantAuthentication' => $this->setup_api(),
			'batchId'                => $batch_id,
		);

		$this->request_type = 'getTransactionListRequest';
		$this->send_api_request( $request );

		$response = $this->translate_response();
		if ( $response === true ) {
			$response = $this->response->transactions;
		}

		return $response;
	}


	/**
	 * Get settled batch
	 *
	 * @param $startdate
	 * @param $enddate
	 * @return
	 */
	public function get_settled_batch( $startdate = '', $enddate = '' ) {
		$request = array(
			'merchantAuthentication' => $this->setup_api(),
			'includeStatistics'      => false,
		);

		if ( ! empty( $enddate ) ) {
			$request['firstSettlementDate'] = date( 'Y-m-d\TH:i:s', strtotime( $startdate ) );
			$request['lastSettlementDate']  = date( 'Y-m-d\TH:i:s', strtotime( $enddate ) );
		}

		$this->request_type = 'getSettledBatchListRequest';
		$this->send_api_request( $request );

		$message = $this->translate_response();
		if ( $message === true && isset( $this->response->batchList ) ) {
			$message = $this->response->batchList;
		}

		return $message;
	}

	/**
	 * Get unsettled list
	 *
	 * @return
	 * @since  1.0.0
	 */
	public function get_unsettled_list() {
		$request = array(
			'merchantAuthentication' => $this->setup_api(),
		);

		$this->request_type = 'getUnsettledTransactionListRequest';
		$this->send_api_request( $request );

		$response = $this->translate_response();
		if ( $response === true ) {
			if ( $this->response->totalNumInResultSet > 0 ) {
				$response = $this->response->transactions;
			} else {
				$response = array();
			}
		}

		return $response;
	}

	public function is_payment_successful() {
		return ! empty( $this->response ) && $this->response->messages->resultCode == 'Ok';
	}

	public function get_error_message() {
		$status = '';
		if ( empty( $this->response ) ) {
			$status = __( 'There was a problem with your API call.', 'frmauthnet' );
		} else {
			$errors = $this->response->messages->message;
			foreach ( $errors as $error ) {
				$status .= str_replace( "\r\n", '<br/>', $error->code . ' ' . $error->text ) . '<br/>';
			}
		}

		return $status;
	}
}
