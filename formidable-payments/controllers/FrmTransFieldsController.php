<?php

class FrmTransFieldsController {

	/**
	 * @param array $fields
	 * @return array
	 */
	public static function add_gateway_field_type( $fields ) {
		$fields['gateway'] = __( 'Gateway', 'formidable-payments' );
		return $fields;
	}

	/**
	 * Add a new gateway field if one doesn't already exist.
	 */
	public static function auto_add_gateway_field( $settings, $action ) {
		$form_id       = $action['menu_order'];
		$gateway_field = FrmField::getAll( array( 'fi.form_id' => $form_id, 'type' => 'gateway' ) );
		if ( ! $gateway_field ) {
			$new_values         = FrmFieldsHelper::setup_new_vars( 'gateway', $form_id );
			$new_values['name'] = __( 'Payment Method', 'formidable' );
			FrmField::create( $new_values );
		}
		return $settings;
	}

	/**
	 * @param array    $values
	 * @param stdClass $field
	 * @return array
	 */
	public static function add_gateway_options( $values, $field ) {
		if ( $field->type !== 'gateway' ) {
			return $values;
		}

		$values['options'] = self::get_options_for_field( $field );
		$values['use_key'] = true;
		$values['value']   = self::get_first_value( $values['options'] );
		if ( count( $values['options'] ) < 2 && ! FrmAppHelper::is_admin_page( 'formidable' ) ) {
			do_action( 'frm_enqueue_' . $values['value'] . '_scripts', array( 'form_id' => $field->form_id ) );
			$values['type'] = 'hidden';
		}

		return $values;
	}

	/**
	 * @param stdClass|array $field
	 * @return array
	 */
	public static function get_options_for_field( $field ) {
		$form_id          = is_object( $field ) ? $field->form_id : $field['form_id'];
		$gateways         = self::get_gateways_for_form( $form_id );
		$gateway_settings = FrmTransAppHelper::get_gateways();

		$options = array();
		foreach ( $gateways as $gateway ) {
			if ( isset( $gateway_settings[ $gateway ] ) ) {
				$options[ $gateway ] = $gateway_settings[ $gateway ]['user_label'];
			}
		}

		return $options;
	}

	/**
	 * @param string|int $form_id
	 * @return array
	 */
	public static function get_gateways_for_form( $form_id ) {
		$payment_actions = FrmTransActionsController::get_actions_for_form( $form_id );
		if ( empty( $payment_actions ) ) {
			return array();
		}

		$payment_action = reset( $payment_actions );
		$gateways       = $payment_action->post_content['gateway'];
		return $gateways;
	}

	/**
	 * Show the gateway field in the builder with the instructions not to delete the field.
	 *
	 * @param array $field
	 * @return void
	 */
	public static function show_in_form_builder( $field ) {
		// Generate field name and HTML id.
		$field_name = 'item_meta[' . $field['id'] . ']';
		$html_id    = 'field_' . $field['field_key'];

		$field['options'] = self::get_options_for_field( $field );
		if ( empty( $field['value'] ) ) {
			$field['value'] = self::get_first_value( $field['options'] );
		}

		include FrmTransAppHelper::plugin_path() . '/views/fields/gateway-back-end.php';
	}

	/**
	 * Include a hidden gateway field in a form.
	 *
	 * @param array  $field
	 * @param string $field_name
	 * @param array  $atts
	 * @return void
	 */
	public static function show_in_form( $field, $field_name, $atts ) {
		$errors  = isset( $atts['errors'] ) ? $atts['errors'] : array();
		$html_id = $atts['html_id'];

		echo '<input type="hidden" name="frm_gateway" value="' . esc_attr( $field['id'] ) . '" />';

		foreach ( $field['options'] as $gateway => $label ) {
			do_action( 'frm_enqueue_' . $gateway . '_scripts', array( 'form_id' => $field['form_id'] ) );
		}

		$field['type'] = 'radio';

		$field_obj = FrmFieldFactory::get_field_type( $field['type'], $field );
		$form      = FrmForm::getOne( $field['form_id'] );
		echo $field_obj->include_front_field_input( compact( 'errors', 'form', 'html_id', 'field_name' ), $atts ); // WPCS: XSS ok
	}

	private static function get_first_value( $options ) {
		reset( $options );
		return key( $options );
	}

	/**
	 * @param string $type
	 * @return string
	 */
	public static function field_type_for_logic( $type ) {
		return ( $type === 'gateway' ) ? 'radio' : $type;
	}

	/**
	 * Change the name of the Pro credit card field to Payment if it exists.
	 * In future versions this field will no longer be a Pro field as it will be included in Lite.
	 * For now, we're filtering this in Payments so we can update other "Credit Card" strings used in Payment actions at the same time.
	 *
	 * @since 2.04
	 *
	 * @param array<array> $fields
	 * @return array
	 */
	public static function filter_credit_card_field( $fields ) {
		if ( isset( $fields['credit_card'] ) && is_array( $fields['credit_card'] ) ) {
			$fields['credit_card']['name'] = __( 'Payment', 'formidable' );
		}
		return $fields;
	}
}
