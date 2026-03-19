<?php

class FrmTransPaymentsController extends FrmTransCRUDController {

	public static function menu() {
		if ( ! class_exists('FrmAppHelper') ) {
			return;
		}

		$frm_settings    = FrmAppHelper::get_settings();
		$payments_string = esc_html__( 'Payments', 'formidable-payments' );

		remove_action( 'admin_menu', 'FrmPaymentsController::menu', 26 );
		add_submenu_page( 'formidable', $frm_settings->menu . ' | ' . $payments_string, $payments_string, 'frm_view_entries', 'formidable-payments', 'FrmTransPaymentsController::route' );
	}

	public static function route() {
		$action = isset( $_REQUEST['frm_action'] ) ? 'frm_action' : 'action';
		$action = FrmAppHelper::get_param( $action, '', 'get', 'sanitize_title' );
		$type = FrmAppHelper::get_param( 'type', '', 'get', 'sanitize_title' );

		$class_name = ( $type == 'subscriptions' ) ? 'FrmTransSubscriptionsController' : 'FrmTransPaymentsController';
		if ( $action == 'new' ) {
			self::new_payment();
		} elseif ( method_exists( $class_name, $action ) ) {
			$class_name::$action();
		} else {
			FrmTransListsController::route( $action );
		}
	}

	private static function new_payment(){
		self::get_new_vars();
	}

	private static function create() {
		$frm_payment = new FrmTransPayment();
		if ( $id = $frm_payment->create( $_POST ) ) {
			$message = __( 'Payment was Successfully Created', 'formidable-payments' );
			self::get_edit_vars( $id, '', $message );
		} else {
			$message = __( 'There was a problem creating that payment', 'formidable-payments' );
			self::get_new_vars( $message );
		}
	}

	private static function get_new_vars( $error = '' ) {
		global $wpdb;

		$frm_payment = new FrmTransPayment();
		$get_defaults = $frm_payment->get_defaults();
		$defaults = array();
		foreach ( $get_defaults as $name => $values ) {
			$defaults[ $name ] = $values['default'];
		}
		$defaults['paysys'] = 'manual';

		$payment = (object) array();
		foreach ( $defaults as $var => $default ) {
			$payment->$var = FrmAppHelper::get_param( $var, $default, 'post', 'sanitize_text_field' );
		}

		$currency = FrmTransAppHelper::get_currency( 'usd' );

		include( FrmTransAppHelper::plugin_path() . '/views/payments/new.php' );
	}

	public static function load_sidebar_actions( $payment ) {
		$icon = ( $payment->status == 'complete' ) ? 'yes' : 'no-alt';
		$date_format = __( 'M j, Y @ G:i' );
		$created_at = FrmAppHelper::get_localized_date( $date_format, $payment->created_at );

		FrmTransActionsController::actions_js();

		include( FrmTransAppHelper::plugin_path() . '/views/payments/sidebar_actions.php' );
	}

	/**
	 * Echo a receipt link.
	 *
	 * @param object $payment
	 * @return void
	 */
	public static function show_receipt_link( $payment ) {
		$link = esc_html( $payment->receipt_id );
		if ( $payment->receipt_id !== 'None' ) {
			/**
			 * Filter a receipt link for a specific gateway.
			 * For example, Stripe uses frm_pay_stripe_receipt.
			 *
			 * @param string $link
			 */
			$link = apply_filters( 'frm_pay_' . $payment->paysys . '_receipt', $link );
		}

		echo FrmAppHelper::kses( $link, array( 'a' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Show a link to a payment entry (unless it is deleted).
	 *
	 * @param object $payment
	 * @return void
	 */
	public static function show_entry_link( $payment ) {
		$entry = FrmDb::get_col( 'frm_items', array( 'id' => $payment->item_id ) );

		if ( ! $entry ) {
			echo esc_html( sprintf( __( '%d (Deleted)', 'formidable' ), $payment->item_id ) );
			return;
		}

		?>
		<a href="?page=formidable-entries&amp;action=show&amp;frm_action=show&amp;id=<?php echo absint( $payment->item_id ); ?>">
			<?php echo absint( $payment->item_id ); ?>
		</a>
		<?php
	}

	/**
	 * Echo a refund link.
	 *
	 * @param object $payment
	 * @return void
	 */
	public static function show_refund_link( $payment ) {
		$link = self::refund_link( $payment );
		FrmTransAppHelper::echo_confirmation_link( $link );
	}

	/**
	 * Get a refund link.
	 *
	 * @param object $payment
	 * @return string
	 */
	public static function refund_link( $payment ) {
		if ( $payment->status === 'refunded' ) {
			$link = __( 'Refunded', 'formidable' );
		} else {
			$link = admin_url( 'admin-ajax.php?action=frm_trans_refund&payment_id=' . $payment->id . '&nonce=' . wp_create_nonce( 'frm_trans_ajax' ) );
			$link  = '<a href="' . esc_url( $link ) . '" class="frm_trans_ajax_link" title="' . esc_attr__( 'Refund', 'formidable' ) . '" data-frmverify="' . esc_attr__( 'Are you sure you want to refund that payment?', 'formidable' ) . '" >';
			$link .= __( 'Refund', 'formidable' );
			$link .= '</a>';
		}

		/**
		 * Filter the refund link for a specific gateway.
		 * For example, Stripe uses frm_pay_stripe_refund_link.
		 *
		 * @param string $link
		 * @param object $payment
		 */
		$link = apply_filters( 'frm_pay_' . $payment->paysys . '_refund_link', $link, $payment );

		return $link;
	}

	/**
	 * Process the ajax request to refund a payment.
	 *
	 * @return void
	 */
	public static function refund_payment() {
		FrmAppHelper::permission_check( 'frm_edit_entries' );
		check_ajax_referer( 'frm_trans_ajax', 'nonce' );

		$payment_id = FrmAppHelper::get_param( 'payment_id', '', 'get', 'absint' );
		if ( $payment_id ) {
			$frm_payment = new FrmTransPayment();
			$payment     = $frm_payment->get_one( $payment_id );

			$class_name = FrmTransAppHelper::get_setting_for_gateway( $payment->paysys, 'class' );
			$class_name = 'Frm' . $class_name . 'ApiHelper';
			$refunded   = $class_name::refund_payment( $payment->receipt_id, compact( 'payment' ) );
			if ( $refunded ) {
				self::change_payment_status( $payment, 'refunded' );
				$message = __( 'Refunded', 'formidable' );
			} else {
				$message = __( 'Failed', 'formidable' );
			}
		} else {
			$message = __( 'Oops! No payment was selected for refund.', 'formidable' );
		}

		echo esc_html( $message );
		wp_die();
	}

	/**
	 * Update the status of a payment.
	 *
	 * @param object $payment
	 * @param string $status
	 */
	public static function change_payment_status( $payment, $status ) {
		$frm_payment = new FrmTransPayment();
		if ( $status != $payment->status ) {
			$frm_payment->update( $payment->id, array( 'status' => $status ) );
			FrmTransActionsController::trigger_payment_status_change( compact( 'status', 'payment' ) );
		}
	}

	/**
	 * Process a [frm-receipt-id] shortcode.
	 * Get the receipt ID for a given entry ID.
	 *
	 * @since 1.09
	 *
	 * @param array $atts
	 * @return string
	 */
	public static function do_frm_receipt_id_shortcode( $atts ) {
		$atts['show'] = 'receipt_id';
		return self::payment_shortcode( $atts );
	}

	/**
	 * Process a [frm-payment] shortcode.
	 * This is also used for [frm-receipt-id] shortcodes.
	 *
	 * @since 2.08
	 *
	 * @param array $atts
	 * @return string
	 */
	public static function payment_shortcode( $atts ) {
		$shortcode = new FrmTransShortcode( $atts );
		return $shortcode->is_valid() ? $shortcode->get_value() : '';
	}

}
