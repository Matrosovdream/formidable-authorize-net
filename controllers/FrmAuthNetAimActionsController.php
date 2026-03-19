<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

if ( class_exists( 'FrmAuthNetAimActionsController' ) ) {
	return;
}

/**
 *
 * @package FrmAuthNet\Controllers
 */
class FrmAuthNetAimActionsController extends FrmTransActionsController {

	/**
	 * @param $action
	 * @param $entry
	 * @param $form
	 *
	 * @since 1.0
	 */
	public static function trigger_gateway( $action, $entry, $form ) {
		$atts = compact( 'action', 'entry', 'form' );
		$payment = new FrmAuthNetAim( $atts );

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
}
