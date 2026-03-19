<?php

class FrmTransAppHelper {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public static $plug_version = '2.11';

	/**
	 * Get plugin version.
	 *
	 * @since 2.08
	 * @return string
	 */
	public static function plugin_version() {
		return self::$plug_version;
	}

	public static function plugin_path() {
		return dirname( dirname( __FILE__ ) );
	}

	public static function plugin_url() {
		return plugins_url( '', self::plugin_path() . '/formidable-payments.php' );
	}

	public static function plugin_folder() {
		return basename( self::plugin_path() );
	}

	public static function get_gateways() {
		$gateways = array(
			'manual' => array(
				'label' => __( 'Manual', 'formidable-payments' ),
				'user_label' => __( 'Manual', 'formidable-payments' ),
				'class' => 'Trans',
				'recurring' => true,
			),
		);
		$gateways = apply_filters( 'frm_payment_gateways', $gateways );
		return $gateways;
	}

	/**
	 * @param string $gateway
	 * @param string $setting
	 */
	public static function get_setting_for_gateway( $gateway, $setting ) {
		$gateways = self::get_gateways();
		$value = '';
		if ( isset( $gateways[ $gateway ] ) ) {
			$value = $gateways[ $gateway ][ $setting ];
		}
		return $value;
	}

	/**
	 * Get a status payment status label.
	 *
	 * @param string $status The lowercase payment status value.
	 * @return string
	 */
	public static function show_status( $status ) {
		$statuses = array_merge( self::get_payment_statuses(), self::get_subscription_statuses() );
		return isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status;
	}

	/**
	 * @return array<string,string>
	 */
	public static function get_payment_statuses() {
		return array(
			'authorized' => __( 'Authorized', 'formidable' ),
			'pending'    => __( 'Pending', 'formidable' ),
			'complete'   => __( 'Completed', 'formidable' ),
			'failed'     => __( 'Failed', 'formidable' ),
			'refunded'   => __( 'Refunded', 'formidable' ),
			'canceled'   => __( 'Canceled', 'formidable' ),
			'processing' => __( 'Processing', 'formidable' )
		);
	}

	/**
	 * @return array<string,string>
	 */
	public static function get_subscription_statuses() {
		return array(
			'pending'       => __( 'Pending', 'formidable' ),
			'active'        => __( 'Active', 'formidable' ),
			'future_cancel' => __( 'Canceled', 'formidable' ),
			'canceled'      => __( 'Canceled', 'formidable' ),
			'void'          => __( 'Void', 'formidable' ),
		);
	}

	/**
	 * Add a note to payment data that will get saved to the payment meta.
	 * This is called when processing events in the Stripe add on.
	 *
	 * @param array  $payment_values
	 * @param string $message
	 * @return void
	 */
	public static function add_note_to_payment( &$payment_values, $message = '' ) {
		if ( empty( $message ) ) {
			$message = sprintf( __( 'Payment %s', 'formidable' ), $payment_values['status'] );
		}
		$payment_values['meta_value'] = isset( $payment_values['meta_value'] ) ? $payment_values['meta_value'] : array();
		$payment_values['meta_value'] = self::add_meta_to_payment( $payment_values['meta_value'], $message );
	}

	public static function add_meta_to_payment( $meta_value, $note ) {
		$meta_value = (array) maybe_unserialize( $meta_value );
		$meta_value[] = array(
			'message' => $note,
			'date'    => date( 'Y-m-d H:i:s' ),
		);
		return $meta_value;
	}

	public static function get_currency( $currency ) {
		if ( is_callable( 'FrmCurrencyHelper::get_currency' ) ) {
			return FrmCurrencyHelper::get_currency( $currency );
		}

		$currencies = self::get_currencies();
		if ( isset( $currencies[ $currency ] ) ) {
			$currency = $currencies[ $currency ];
		} else {
			$currency = $currencies['usd'];
		}
		return $currency;
	}

	/**
	 * Get an array of currency data indexed by three-letter currency codes.
	 *
	 * @return array<string,array>
	 */
	public static function get_currencies() {
		// This was added in v6.5 of Lite (Sep 12, 2023).
		if ( is_callable( 'FrmCurrencyHelper::get_currencies' ) ) {
			return FrmCurrencyHelper::get_currencies();
		}

		$pro_currencies = array();
		// This was added in v4.04 of Pro (Feb 4, 2020).
		if ( is_callable( 'FrmProCurrencyHelper::get_currencies' ) ) {
			return FrmProCurrencyHelper::get_currencies();
		}

		return array();
	}

	/**
	 * @param string $option
	 * @param array $atts
	 */
	public static function get_action_setting( $option, $atts ) {
		$settings = self::get_action_settings( $atts );
		$value = isset( $settings[ $option ] ) ? $settings[ $option ] : '';

		return $value;
	}

	public static function get_action_settings( $atts ) {
		$settings = array();
		if ( isset( $atts['payment'] ) ) {
			$atts['payment'] = (array) $atts['payment'];
			if ( ! empty( $atts['payment']['action_id'] ) ) {
				$form_action = FrmTransAction::get_single_action_type( $atts['payment']['action_id'], 'payment' );
				if ( $form_action ) {
					$settings = $form_action->post_content;
				}
			}
		}

		return $settings;
	}

	public static function get_action_description( $action_id ) {
		_deprecated_function( __FUNCTION__, '1.11', 'FrmTransAppHelper::get_payment_description' );

		$atts = array( 'payment' => compact( 'action_id' ) );
		return self::get_action_setting( 'description', $atts );
	}

	/**
	 * Return the filtered payment description
	 *
	 * @since 1.11
	 *
	 * @param object $subscription
	 *
	 * @return string
	 */
	public static function get_payment_description( $subscription ) {
		$atts = array( 'payment' => array( 'action_id' => $subscription->action_id ) );
		$description = self::get_action_setting( 'description', $atts );

		if ( empty( $description ) ) {
			return '';
		}

		$entry = FrmEntry::getOne( $subscription->item_id, true );

		if ( ! $entry ) {
			return $description;
		}

		$form = FrmForm::getOne( $entry->form_id );

		if ( ! $form ) {
			return $description;
		}

		return self::process_shortcodes( array(
			'value' => $description,
			'form'  => $form,
			'entry' => $entry,
		) );
	}

	/**
	 * Allow entry values, default values, and other shortcodes
	 *
	 * @param array $atts - Includes value (required), form, entry
	 * @return string|int
	 */
	public static function process_shortcodes( $atts ) {
		$value = $atts['value'];
		if ( strpos( $value, '[' ) === false ) {
			return $value;
		}

		if ( is_callable( 'FrmProFieldsHelper::replace_non_standard_formidable_shortcodes' ) ) {
			FrmProFieldsHelper::replace_non_standard_formidable_shortcodes( array(), $value );
		}

		if ( isset( $atts['entry'] ) && ! empty( $atts['entry'] ) ) {
			if ( ! isset( $atts['form'] ) ) {
				$atts['form'] = FrmForm::getOne( $atts['entry']->form_id );
			}
			$value = apply_filters( 'frm_content', $value, $atts['form'], $atts['entry'] );
		}

		$value = do_shortcode( $value );
		return $value;
	}

	/**
	 * @param object $sub
	 * @return string
	 */
	public static function format_billing_cycle( $sub ) {
		$amount = FrmTransAppHelper::formatted_amount( $sub );
		$interval = self::get_repeat_label_from_value( $sub->time_interval, $sub->interval_count );
		if ( $sub->interval_count == 1 ) {
			$amount = $amount . '/' . $interval;
		} else {
			$amount = $amount . ' every ' . $sub->interval_count . ' ' . $interval;
	}
		return $amount;
	}

	/**
	 * @return array
	 */
	public static function get_repeat_times() {
		return array(
			'day'   => __( 'day(s)', 'formidable' ),
			'week'  => __( 'week(s)', 'formidable' ),
			'month' => __( 'month(s)', 'formidable' ),
			'year'  => __( 'year(s)', 'formidable' ),
		);
	}

	/**
	 * @since 1.16
	 *
	 * @param int $number
	 * @return array
	 */
	private static function get_plural_repeat_times( $number ) {
		return array(
			'day'   => _n( 'day', 'days', $number, 'formidable' ),
			'week'  => _n( 'week', 'weeks', $number, 'formidable' ),
			'month' => _n( 'month', 'months', $number, 'formidable' ),
			'year'  => _n( 'year', 'years', $number, 'formidable' ),
		);
	}

	/**
	 * @since 1.16
	 *
	 * @param string $value
	 * @param int $number
	 * @return string
	 */
	public static function get_repeat_label_from_value( $value, $number ) {
		$times = self::get_plural_repeat_times( $number );
		if ( isset( $times[ $value ] ) ) {
			$value = $times[ $value ];
		}
		return $value;
	}

	/**
	 * @return string
	 */
	public static function formatted_amount( $payment ) {
		$currency = 'usd';
		$amount = $payment;

		if ( is_object( $payment ) || is_array( $payment ) ) {
			$payment = (array) $payment;
			$amount = $payment['amount'];
			$currency = self::get_action_setting( 'currency', array( 'payment' => $payment ) );
		}

		$currency = self::get_currency( $currency );

		self::format_amount_for_currency( $currency, $amount );

		return $amount;
	}

	/**
	 * @param array $currency
	 * @param float $amount
	 * @return string
	 */
	public static function format_amount_for_currency( $currency, &$amount ) {
		$amount       = number_format( $amount, $currency['decimals'], $currency['decimal_separator'], $currency['thousand_separator'] );
		$left_symbol  = $currency['symbol_left'] . $currency['symbol_padding'];
		$right_symbol = $currency['symbol_padding'] . $currency['symbol_right'];
		$amount       = $left_symbol . $amount . $right_symbol;
	}

	/**
	 * @return string
	 */
	public static function get_date_format() {
		$date_format = 'm/d/Y';
		if ( class_exists('FrmProAppHelper') ){
			$frmpro_settings = FrmProAppHelper::get_settings();
			if ( $frmpro_settings ) {
				$date_format = $frmpro_settings->date_format;
			}
		} else {
			$date_format = get_option('date_format');
		}

		return $date_format;
	}

	/**
	 * @param string $date
	 * @param string $format
	 *
	 * @return string
	 */
	public static function format_the_date( $date, $format = '' ) {
		if ( empty( $format ) ) {
			$format = self::get_date_format();
		}
		return date_i18n( $format, strtotime( $date ) );
	}

	/**
	 * Get a human readable translated 'Test' or 'Live' string if the column value is defined.
	 * Old payments will just output an empty string.
	 *
	 * @param stdClass $payment
	 * @return string
	 */
	public static function get_test_mode_display_string( $payment ) {
		if ( ! isset( $payment->test ) ) {
			return '';
		}
		return $payment->test ? __( 'Test', 'formidable' ) : __( 'Live', 'formidable' );
	}

	/**
	 * When a user is created at the same time payment is made,
	 * they won't be logged in yet. The user ID is in $_POST['frm_user_id']
	 *
	 * @return int
	 */
	public static function get_user_id_for_current_payment() {
		$user_id = 0;
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		} elseif ( $_POST && isset( $_POST['frm_user_id'] ) ) {
			// the user may have just been registered, but we need extra checks
			$registration_submitted = isset( $_POST['frm_register'] ) && ! empty( $_POST['frm_register'] ) && isset( $_POST['form_id'] ) && is_numeric( $_POST['form_id'] ) && is_numeric( $_POST['frm_user_id'] );
			if ( $registration_submitted ) {
				$user_id = absint( $_POST['frm_user_id'] );
			}
		}
		return $user_id;
	}

	/**
	 * @param int $user_id
	 *
	 * @return string
	 */
	public static function get_user_link( $user_id ) {
		$user_link = __( 'Guest', 'formidable' );
		if ( $user_id ) {
			$user = get_userdata( $user_id );
			if ( $user ) {
				$user_link = '<a href="' . esc_url( admin_url('user-edit.php?user_id=' . $user_id ) ) . '">' . $user->display_name . '</a>';
			}
		}
		return $user_link;
	}

	public static function show_in_table( $value, $label ) {
		if ( ! empty( $value ) ) { ?>
			<tr valign="top">
				<th scope="row"><?php echo esc_html( $label ); ?>:</th>
				<td>
					<?php echo esc_html( $value ); ?>
				</td>
			</tr>
			<?php
		}
	}

	/**
	 * Echo a link that includes a data-deleteconfirm attribute.
	 * This includes refund links and links to cancel a subscription.
	 *
	 * @since 2.04
	 *
	 * @param string $link
	 * @return void
	 */
	public static function echo_confirmation_link( $link ) {
		$filter = __CLASS__ . '::allow_deleteconfirm_data_attribute';
		add_filter( 'frm_striphtml_allowed_tags', $filter );
		echo FrmAppHelper::kses( $link, array( 'a' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		remove_filter( 'frm_striphtml_allowed_tags', $filter );
	}

	/**
	 * Allow the data-deleteconfirm attribute for confirmation links.
	 * The attribute is used for the confirmation message.
	 *
	 * @since 2.04
	 * @since 2.05 Allowed `data-frmverify` and `data-frmverify-btn`.
	 *
	 * @param array $allowed
	 * @return array
	 */
	public static function allow_deleteconfirm_data_attribute( $allowed ) {
		$allowed['a']['data-deleteconfirm'] = true;
		$allowed['a']['data-frmverify']     = true;
		$allowed['a']['data-frmverify-btn'] = true;

		return $allowed;
	}

	/**
	 * Formats non zero-decimal currencies.
	 *
	 * @since 2.05
	 *
	 * @param string|int $amount
	 * @param WP_Post    $action
	 *
	 * @return string
	 */
	public static function get_formatted_amount_for_currency( $amount, $action ) {
		if ( ! isset( $action->post_content['currency'] ) ) {
			return $amount;
		}

		$currency = FrmTransAppHelper::get_currency( $action->post_content['currency'] );
		if ( ! empty( $currency['decimals'] ) ) {
			$amount = number_format( ( $amount / 100 ), 2, '.', '' );
		}

		return $amount;
	}

	/**
	 * Get Payment status from a payment with support for PayPal backward compatibility.
	 *
	 * @param stdClass $payment
	 * @return string
	 */
	public static function get_payment_status( $payment ) {
		if ( $payment->status ) {
			return $payment->status;
		}
		// PayPal fallback.
		return ! empty( $payment->completed ) ? 'complete' : 'pending';
	}
}
