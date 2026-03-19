<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

if ( class_exists( 'FrmAuthNetSubscriptionsHelper' ) ) {
	return;
}
/**
 * Subscriptions Class (Helper)
 *
 * @package FrmAuthNet\Helpers
 */
class FrmAuthNetSubscriptionsHelper {

	/**
	 * Get the customers subscriptions
	 * Call query to get the list of subscriptions if there user_id exists.
	 * If not return false.
	 *
	 * @return mixed Either false or the users ID.
	 */
	public static function get_customer_subscriptions() {
		$user_id = self::get_customer_id();
		if ( $user_id ) {
			$sub  = new FrmTransSubscription();
			$subs = $sub->get_all_for_user( $user_id );
		} else {
			$subs = false;
		}

		return $subs;

	}

	/**
	 * Get the customer ID
	 * Retrieve the customer ID meta field if it exists. If not return false.
	 *
	 * @return mixed Either false or the users ID.
	 */
	public static function get_customer_id() {
		$user_id = false;
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		}
		return $user_id;
	}

}
