<?php

class FrmTransCRUDController {

	/**
	 * Show a table of either payments for subscriptions.
	 *
	 * @param int $id
	 * @return void
	 */
	public static function show( $id = 0 ) {
		if ( ! $id ) {
			$id = FrmAppHelper::get_param( 'id', 0, 'get', 'sanitize_text_field' );
			if ( ! $id ) {
				wp_die( esc_html__( 'Please select a payment to view', 'formidable' ) );
			}
		}

		$table_name = self::table_name();

		global $wpdb;
		$payment = $wpdb->get_row( $wpdb->prepare( "SELECT p.*, e.user_id FROM {$wpdb->prefix}frm_" . $table_name . " p LEFT JOIN {$wpdb->prefix}frm_items e ON (p.item_id = e.id) WHERE p.id=%d", $id ) );

		if ( ! $payment ) {
			FrmAppHelper::include_svg();

			$trans_type = $table_name === 'subscriptions' ? __( 'Subscription', 'formidable' ) : __( 'Payment', 'formidable' );
			FrmAppController::show_error_modal(
				array(
					/* translators: %s: Transaction type */
					'title'      => sprintf( __( 'You can\'t view the %s', 'formidable' ), $trans_type ),
					/* translators: %s: Transaction type */
					'body'       => sprintf( __( 'You are trying to view a %s that does not exist', 'formidable' ), $trans_type ),
					/* translators: %s: Transaction table name */
					'cancel_url' => sprintf( admin_url( 'admin.php?page=formidable-payments&trans_type=%ss' ), $table_name ),
				)
			);
			return;
		}
		$date_format = get_option('date_format');
		$user_name   = FrmTransAppHelper::get_user_link( $payment->user_id );

		if ( $table_name !== 'payments' ) {
			$subscription = $payment;
		}

		include FrmTransAppHelper::plugin_path() . '/views/' . $table_name . '/show.php';
	}

	/**
	 * Handle routing for deleting a payment.
	 *
	 * @return void
	 */
	public static function destroy() {
		$nonce = FrmAppHelper::simple_get( '_wpnonce' );

		if ( ! wp_verify_nonce( $nonce ) ) {
			$frm_settings = FrmAppHelper::get_settings();
			wp_die( esc_html( $frm_settings->admin_permission ) );
		}

		FrmAppHelper::permission_check( 'administrator' );

		$message     = '';
		$frm_payment = self::the_class();
		$id          = FrmAppHelper::get_param( 'id', '', 'get', 'absint' );

		if ( $id && $frm_payment->destroy( $id ) ) {
			$message = __( 'Payment was Successfully Deleted', 'formidable' );
		}

		FrmTransListsController::display_list( compact('message') );
	}

	/**
	 * @return void
	 */
	public static function edit() {
		$id = FrmAppHelper::get_param('id');
		self::get_edit_vars( $id );
	}

	/**
	 * Handle routing to update a payment.
	 *
	 * @return void
	 */
	public static function update() {
		FrmAppHelper::permission_check( 'administrator' );

		$id          = FrmAppHelper::get_param('id');
		$message     = '';
		$error       = '';
		$frm_payment = self::the_class();
		if ( $frm_payment->update( $id, $_POST ) ) {
			$message = __( 'Payment was Successfully Updated', 'formidable-payments' );
		} else {
			$error = __( 'There was a problem updating that payment', 'formidable-payments' );
		}

		self::get_edit_vars( $id, $error, $message );
	}

	public static function get_edit_vars( $id, $errors = '', $message = '' ) {
		if ( ! $id ) {
			die( esc_html__( 'Please select a payment to view', 'formidable' ) );
		}

		if ( ! current_user_can( 'frm_edit_entries' ) ) {
			return self::show( $id );
		}

		$table_name = self::table_name();

		global $wpdb;
		$payment = $wpdb->get_row( $wpdb->prepare( "SELECT p.*, e.user_id FROM {$wpdb->prefix}frm_" . $table_name . " p LEFT JOIN {$wpdb->prefix}frm_items e ON (p.item_id = e.id) WHERE p.id=%d", $id ) );

		$currency = FrmTransAppHelper::get_action_setting( 'currency', array( 'payment' => $payment ) );
		$currency = FrmTransAppHelper::get_currency( $currency );

		if ( $_POST && isset( $_POST['receipt_id'] ) ) {
			foreach ( $payment as $var => $val ) {
				if ( $var === 'id' ) {
					continue;
				}
				$var = sanitize_text_field( $var );
				$val = sanitize_text_field( $val );
				$payment->$var = FrmAppHelper::get_param( $var, $val, 'post', 'sanitize_text_field' );
			}
			if ( '' === $payment->test ) {
				$payment->test = null;
			}
		}

		include FrmTransAppHelper::plugin_path() . '/views/' . $table_name . '/edit.php';
	}

	private static function table_name() {
		$allowed = array( 'payments', 'subscriptions' );
		$default = reset( $allowed );
		$name    = FrmAppHelper::get_param( 'type', $default, 'get', 'sanitize_text_field' );

		if ( ! in_array( $name, $allowed ) ) {
			$name = $default;
		}
		return $name;
	}

	private static function the_class() {
		$class_name = ( self::table_name() === 'subscriptions' ) ? 'FrmTransSubscription' : 'FrmTransPayment';
		return new $class_name();
	}
}
