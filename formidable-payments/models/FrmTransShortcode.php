<?php

/**
 * A class for handling the [frm-payment] shortcode.
 */
class FrmTransShortcode {

	/**
	 * @var int|null
	 */
	private $entry_id;

	/**
	 * @var string|null
	 */
	private $show;

	/**
	 * @var array
	 */
	private $atts;

	/**
	 * @param array $atts
	 */
	public function __construct( $atts ) {
		$this->atts = $atts;
		$this->set_entry_id_from_atts();
		$this->set_show_value_from_atts();
	}

	/**
	 * $atts['entry'] may be an ID or an entry key.
	 * This function will convert an entry key to an entry ID if one is passed.
	 *
	 * @since 2.08
	 *
	 * @return void
	 */
	private function set_entry_id_from_atts() {
		if ( ! isset( $this->atts['entry'] ) ) {
			return;
		}

		if ( is_numeric( $this->atts['entry'] ) ) {
			$this->entry_id = (int) $this->atts['entry'];
			return;
		}

		$entry_id = FrmEntry::get_id_by_key( $this->atts['entry'] );
		if ( is_numeric( $entry_id ) ) {
			$this->entry_id = (int) $entry_id;
		}
	}

	/**
	 * Set the show property if the atts include a valid value.
	 *
	 * @since 2.08
	 *
	 * @return void
	 */
	private function set_show_value_from_atts() {
		if ( ! empty( $this->atts['show'] ) && $this->has_valid_show_option() ) {
			$this->show = $this->atts['show'];
		}
	}

	/**
	 * Validate if the show option is defined and matches a supported key.
	 *
	 * @since 2.08
	 *
	 * @return bool
	 */
	private function has_valid_show_option() {
		if ( empty( $this->atts['show'] ) ) {
			return false;
		}
		if ( in_array( $this->atts['show'], self::get_show_columns_for_payments_table(), true ) ) {
			return true;
		}
		if ( in_array( $this->atts['show'], self::get_show_columns_for_subscriptions_table(), true ) ) {
			return true;
		}
		return false;
	}

	/**
	 * These options will query the payments table when used.
	 *
	 * @since 2.08
	 *
	 * @return array
	 */
	private static function get_show_columns_for_payments_table() {
		return array(
			'status',
			'amount',
			'receipt_id',
			'sub_id',
			'created_at',
			'begin_date',
			'expire_date',
			'paysys',
			'test',
			'action_id',
		);
	}

	/**
	 * These options will query the subscriptions table when used.
	 * These do not match the subscriptions table column exactly. These include a subscription_ prefix.
	 *
	 * @since 2.08
	 *
	 * @return array
	 */
	private static function get_show_columns_for_subscriptions_table() {
		return array(
			'subscription_next_bill_date',
			'subscription_status',
		);
	}

	/**
	 * A shortcode is only considered valid if it includes a valid "entry" and "show" option.
	 *
	 * @since 2.08
	 *
	 * @return bool True if the required options are valid.
	 */
	public function is_valid() {
		return ! empty( $this->entry_id ) && ! empty( $this->show );
	}

	/**
	 * Get the value to use for the [frm-payment] shortcode.
	 *
	 * @since 2.08
	 *
	 * @return string
	 */
	public function get_value() {
		if ( in_array( $this->show, self::get_show_columns_for_payments_table(), true ) ) {
			$value = $this->get_payments_table_column();
		} elseif ( in_array( $this->show, self::get_show_columns_for_subscriptions_table(), true ) ) {
			$value = $this->get_subscriptions_table_column();
		} else {
			$value = '';
		}
		$value = $this->finalize_value( $value );
		return $value;
	}

	/**
	 * Apply any additional formatting to the value before returning it as output.
	 *
	 * @since 2.08
	 *
	 * @param string $value The value from the database.
	 * @return string
	 */
	private function finalize_value( $value ) {
		if ( $this->is_a_datetime_key() ) {
			return $this->format_datetime( $value );
		}
		if ( $this->is_a_date_key() ) {
			return $this->format_date( $value );
		}
		if ( 'amount' === $this->show ) {
			return $this->maybe_format_as_currency( $value );
		}
		return $value;
	}

	/**
	 * Check if the show property is a date column, with no time.
	 *
	 * @since 2.08
	 *
	 * @return bool
	 */
	private function is_a_date_key() {
		return in_array( $this->show, self::get_date_value_keys(), true );
	}

	/**
	 * Check if the show property is a datetime column (a column date and time).
	 *
	 * @since 2.08
	 *
	 * @return bool
	 */
	private function is_a_datetime_key() {
		return in_array( $this->show, self::get_datetime_value_keys(), true );
	}

	/**
	 * These columns all have a date and no time.
	 *
	 * @since 2.08
	 *
	 * @return array
	 */
	private static function get_date_value_keys() {
		return array( 'begin_date', 'expire_date', 'subscription_next_bill_date' );
	}

	/**
	 * These columns include a date and a time.
	 *
	 * @since 2.08
	 *
	 * @return array
	 */
	private static function get_datetime_value_keys() {
		return array( 'created_at' );
	}

	/**
	 * Format a value as a string with date and time.
	 *
	 * @since 2.08
	 *
	 * @param string $value
	 * @return string
	 */
	private function format_datetime( $value ) {
		if ( ! empty( $this->atts['format'] ) ) {
			$format = $this->atts['format'];
		} else {
			$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		}
		return FrmAppHelper::get_localized_date( $format, $value );
	}

	/**
	 * Format a value with a date (no time).
	 *
	 * @since 2.08
	 *
	 * @param string $value
	 * @return string
	 */
	private function format_date( $value ) {
		if ( ! empty( $this->atts['format'] ) ) {
			$format = $this->atts['format'];
		} else {
			$format = get_option( 'date_format' );
		}
		return FrmAppHelper::get_localized_date( $format, $value );
	}

	/**
	 * Unless using format="number", expect "amount" to include a currency symbol.
	 *
	 * @since 2.08
	 *
	 * @param string $value
	 * @return string
	 */
	private function maybe_format_as_currency( $value ) {
		$show_as_currency = empty( $this->atts['format'] ) || 'currency' === $this->atts['format'];
		if ( ! $show_as_currency ) {
			// When format="number" is passed, do not add the currency symbol.
			return $value;
		}

		$action_id = FrmDb::get_var( 'frm_payments', array( 'item_id' => $this->entry_id ), 'action_id' );
		if ( ! $action_id ) {
			return $value;
		}

		$action_control = FrmFormActionsController::get_form_actions( 'payment' );
		$action         = $action_control->get_single_action( $action_id );
		if ( ! $action || empty( $action->post_content['currency'] ) ) {
			return $value;
		}

		$currency = FrmTransAppHelper::get_currency( $action->post_content['currency'] );
		$value    = (float) $value;
		FrmTransAppHelper::format_amount_for_currency( $currency, $value );
		return (string) $value;
	}

	/**
	 * Get a value from the payments table.
	 *
	 * @since 2.08
	 *
	 * @return string|null
	 */
	private function get_payments_table_column() {
		return FrmDb::get_var( 'frm_payments', array( 'item_id' => $this->entry_id ), $this->show );
	}

	/**
	 * Get a value from the subscriptions table.
	 *
	 * @since 2.08
	 *
	 * @return string|null
	 */
	private function get_subscriptions_table_column() {
		$show = str_replace( 'subscription_', '', $this->show );
		return FrmDb::get_var( 'frm_subscriptions', array( 'item_id' => $this->entry_id ), $show );
	}
}
