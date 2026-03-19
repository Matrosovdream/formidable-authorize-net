<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

if ( class_exists( 'FrmAuthNetEcheckActionsController' ) ) {
	return;
}

/**
 *
 * @package FrmAuthNet\Controllers
 */
class FrmAuthNetEcheckActionsController extends FrmTransActionsController {

	/**
	 *
	 * @param $action
	 * @param $entry
	 * @param $form
	 *
	 * @since 1.0
	 */
	public static function trigger_gateway( $action, $entry, $form ) {
		$atts = compact( 'action', 'entry', 'form' );
		$payment = new FrmAuthNetEcheck( $atts );

		$response = array(
			'success'      => false,
			'run_triggers' => true,
			'show_errors'  => true,
		);

		$created = $payment->process_payment();
		if ( ! empty( $created ) ) {
			$response['success'] = true;
			$response['id']      = $created;
		}

		if ( ! $response['success'] ) {
			$response['error'] = $payment->get_error_message();
		}

		return $response;
	}

	/**
	 * Check for cleared eChecks
	 */
	public static function run_payment_cron() {
		$transactions = self::get_recent_echecks();
		if ( empty( $transactions ) ) {
			FrmTransLog::log_message( 'No new Authorize.net eChecks found.' );
			return;
		}

		FrmTransLog::log_message( count( $transactions ) . ' eChecks found for processing.' );

		$frm_payment = new FrmTransPayment();
		foreach ( $transactions as $transaction ) {
			$t          = $transaction['transaction'];
			$new_status = $t->transactionStatus;
			$status     = FrmAuthNetHelper::get_status_from_auth( $new_status );
			$payment    = $transaction['payment'];

			if ( $payment->status == $status ) {
				FrmTransLog::log_message( 'Payment #' . $payment->id . ' already marked as ' . $status . '.' );
			} else {
				$frm_payment->update( $payment->id, array( 'status' => $status ) );
				FrmTransLog::log_message( 'Payment #' . $payment->id . ' marked as ' . $status . ' and actions run.' );
				FrmTransActionsController::trigger_payment_status_change( compact( 'status', 'payment' ) );
			}
		}
	}

	/**
	 * Check Authorize for any updates to pending eChecks
	 */
	private static function get_recent_echecks() {
		$frm_payment = new FrmTransPayment();
		$pending_echecks = $frm_payment->get_all_by_multiple(
			array(
				'status' => 'pending',
				'paysys' => 'authnet_echeck',
			)
		);
		if ( empty( $pending_echecks ) ) {
			return array();
		}

		$frm_api = new FrmAuthNetTransaction();

		FrmTransLog::log_message( 'Checking ' . count( $pending_echecks ) . ' pending eChecks.' );

		$echecks = array();
		foreach ( $pending_echecks as $payment ) {
			$days_old = self::get_echeck_days_old( $payment->created_at );
			if ( empty( $payment->receipt_id ) || $days_old > 15 || $days_old < 2 ) {
				// don't check for payment after 15 days, or before we expect the payment
				continue;
			}

			$transaction = $frm_api->get_transaction_details( $payment->receipt_id );
			if ( ! empty( $transaction ) ) {
				$echecks[] = compact( 'transaction', 'payment' );
			}
		}

		return $echecks;
	}

	private static function get_echeck_days_old( $date ) {
		$date = strtotime( $date );
		$datediff = time() - $date;
		return floor( $datediff / ( 60 * 60 * 24 ) );
	}
}
