<?php

class FrmTransSubscriptionsController extends FrmTransCRUDController {

	/**
	 * @param object $subscription
	 * @return void
	 */
	public static function load_sidebar_actions( $subscription ) {
		$date_format = __( 'M j, Y @ G:i' );

		FrmTransActionsController::actions_js();

		$frm_payment = new FrmTransPayment();
		$payments    = $frm_payment->get_all_by( $subscription->id, 'sub_id' );

		include FrmTransAppHelper::plugin_path() . '/views/subscriptions/sidebar_actions.php';
	}

	/**
	 * @since 2.05
	 *
	 * @param object $subscription
	 *
	 * @return void
	 */
	public static function show_receipt_link( $subscription ) {
		$link = esc_html( $subscription->sub_id );
		if ( $subscription->sub_id !== 'None' ) {
			/**
			 * Filter a receipt link for a specific gateway.
			 * For example, Stripe uses frm_sub_stripe_receipt.
			 *
			 * @since 2.05
			 *
			 * @param string $link
			 */
			$link = apply_filters( 'frm_sub_' . $subscription->paysys . '_receipt', $link );
		}

		echo FrmAppHelper::kses( $link, array( 'a' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	
	/**
	 * Outputs a cancellation link for a given subscription.
	 *
	 * @since 2.06 Added $show_modal parameter.
	 *
	 * @param object $sub        The subscription object. Must at least contain 'user_id' and 'item_id' properties.
	 * @param bool   $show_modal Whether to show a modal dialog when canceling the subscription. Defaults to true.
	 *
	 * @return void
	 */
	public static function show_cancel_link( $sub, $show_modal = true ) {
		if ( ! isset( $sub->user_id ) ) {
			global $wpdb;
			$sub->user_id = $wpdb->get_var( $wpdb->prepare( 'SELECT user_id FROM ' . $wpdb->prefix . 'frm_items WHERE id=%d', $sub->item_id ) );
		}

		$link = self::cancel_link( $sub, $show_modal );
		FrmTransAppHelper::echo_confirmation_link( $link );
	}

	/**
	 * @param object $sub
	 * @since 2.06 added $show_modal parameter.
	 *
	 * @return string
	 */
	public static function cancel_link( $sub, $show_modal = true ) {
		if ( $sub->status == 'active' ) {
			$html_atts      = array();
			if ( $show_modal ) {
				$html_atts = array(
					'data-frmverify'     => esc_attr__( 'Are you sure you want to cancel that subscription?', 'formidable' ),
					'data-frmverify-btn' => 'frm-button-red',
				);
			}

			$html_atts['href']  = esc_url( admin_url( 'admin-ajax.php?action=frm_trans_cancel&sub=' . $sub->id . '&nonce=' . wp_create_nonce( 'frm_trans_ajax' ) ) );
			$html_atts['class'] = 'frm_trans_ajax_link';

			$link  = '<a ' . FrmAppHelper::array_to_html_params( $html_atts ) . '>';
			$link .= esc_html__( 'Cancel', 'formidable' );
			$link .= '</a>';
		} else {
			$link = esc_html__( 'Canceled', 'formidable' );
		}
		$link = apply_filters( 'frm_pay_' . $sub->paysys . '_cancel_link', $link, $sub );

		return $link;
	}

	/**
	 * Handle routing to cancel a subscription.
	 *
	 * @return void
	 */
	public static function cancel_subscription() {
		check_ajax_referer( 'frm_trans_ajax', 'nonce' );

		$sub_id = FrmAppHelper::get_param( 'sub', '', 'get', 'sanitize_text_field' );
		if ( $sub_id ) {
			$frm_sub = new FrmTransSubscription();
			$sub     = $frm_sub->get_one( $sub_id );
			if ( $sub ) {
				$class_name = FrmTransAppHelper::get_setting_for_gateway( $sub->paysys, 'class' );
				$class_name = 'Frm' . $class_name . 'ApiHelper';
				$canceled   = $class_name::cancel_subscription( $sub->sub_id );
				if ( $canceled ) {
					self::change_subscription_status( array(
						'status' => 'future_cancel',
						'sub'    => $sub,
					) );

					$message = __( 'Canceled', 'formidable' );
				} else {
					$message = __( 'Failed', 'formidable' );
				}
			} else {
				$message = __( 'That subscription was not found', 'formidable' );
			}

		} else {
			$message = __( 'Oops! No subscription was selected for cancelation.', 'formidable' );
		}

		echo esc_html( $message );
		wp_die();
	}

	/**
	 * @since 1.12
	 *
	 * @param array $atts
	 * @return void
	 */
	public static function change_subscription_status( $atts ) {
		$frm_sub = new FrmTransSubscription();
		$frm_sub->update( $atts['sub']->id, array( 'status' => $atts['status'] ) );
		$atts['sub']->status = $atts['status'];

		FrmTransActionsController::trigger_subscription_status_change( $atts['sub'] );
	}

	public static function list_subscriptions_shortcode() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		wp_enqueue_style(
			'frm-trans-responsive-table',
			FrmTransAppHelper::plugin_url() . '/css/responsive-tables.css',
			array(),
			FrmTransAppHelper::plugin_version()
		);

		$frm_sub = new FrmTransSubscription();
		$subscriptions = $frm_sub->get_all_for_user( get_current_user_id() );
		if ( empty( $subscriptions ) ) {
			return;
		}

		FrmTransActionsController::actions_js();

		ob_start();
		include FrmTransAppHelper::plugin_path() . '/views/subscriptions/list_shortcode.php';
		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}
}
