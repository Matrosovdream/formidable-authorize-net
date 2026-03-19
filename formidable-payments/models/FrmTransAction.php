<?php

class FrmTransAction extends FrmFormAction {

	public function __construct() {
		$action_ops = array(
			'classes'  => 'frm_stripe_icon frm_credit_card_alt_icon frm_icon_font',
			'limit'    => 99,
			'active'   => true,
			'priority' => 45, // after user registration
			'event'    => array( 'create' ),
			'color'    => 'var(--green)',
		);

		$this->FrmFormAction( 'payment', __( 'Collect a Payment', 'formidable' ), $action_ops );
		add_action( 'wp_ajax_frmtrans_after_pay', array( $this, 'add_new_pay_row' ) );
	}

	public function form( $form_action, $args = array() ) {
		global $wpdb;

		$list_fields = self::get_defaults();

		$action_control = $this;
		$options        = $form_action->post_content;
		$gateways       = FrmTransAppHelper::get_gateways();
		unset( $gateways['manual'] );

		if ( '' === $form_action->ID ) {
			// When there is no action ID the post name is formatted like 915_payment_3199.
			// Where 915 is the form ID and 3199 is an auto ID for the unsaved action.
			$split           = explode( '_', $form_action->post_name );
			$form_action->ID = end( $split );
		}

		$classes = $this->get_classes_for_fields( $gateways, $form_action );

		$form_fields = $this->get_field_options( $args['form']->id );
		$field_dropdown_atts = compact( 'form_fields', 'form_action' );

		if ( ! isset( $form_action->post_content['payment_limit'] ) ) {
			$form_action->post_content['payment_limit'] = '';
		}

		include FrmTransAppHelper::plugin_path() . '/views/action-settings/options.php';
	}

	public function get_defaults() {
		$defaults = array(
			'description' => '',
			'email'       => '',
			'amount'      => '',
			'type'        => '',
			'interval_count' => 1,
			'interval'    => 'month',
			'payment_count' => 9999,
			'trial_interval_count' => 0,
			'currency'           => $this->default_currency(),
			'gateway'            => array(),

			'credit_card'        => '',
			'billing_first_name' => '',
			'billing_last_name'  => '',
			'billing_company'    => '',
			'billing_address'    => '',

			'use_shipping'        => 0,
			'shipping_first_name' => '',
			'shipping_last_name'  => '',
			'shipping_company'    => '',
			'shipping_address'    => '',

			'change_field' => array(),
		);
		$defaults = apply_filters( 'frm_pay_action_defaults', $defaults );
		return $defaults;
	}

	/**
	 * @since 2.01
	 */
	private function default_currency() {
		if ( ! is_callable( 'FrmProAppHelper::get_settings' ) ) {
			return 'usd';
		}

		$frm_settings = FrmProAppHelper::get_settings();
		$currency     = trim( $frm_settings->currency );
		if ( ! $currency ) {
			$currency = 'USD';
		}
		return strtolower( $currency );
	}

	/**
	 * @return array
	 */
	public function get_conditional_fields() {
		return array(
			'credit_card',
			'bank_account',
			'billing_first_name',
			'billing_last_name',
			'billing_company',
			'billing_address',
			'use_shipping',
			'shipping_first_name',
			'shipping_last_name',
			'shipping_company',
			'shipping_address',
		);
	}

	/**
	 * @param array   $gateways
	 * @param WP_Post $form_action
	 * @return array
	 */
	private function get_classes_for_fields( $gateways, $form_action ) {
		$classes = array();
		foreach ( $this->get_conditional_fields() as $field ) {
			$classes[ $field ] = 'frm_gateway_setting';
			$show_field = false;
			foreach ( $gateways as $name => $gateway ) {
				if ( ! isset( $gateway['include'] ) || in_array( $field, $gateway['include'] ) ) {
					$classes[ $field ] .= ' show_' . $name;
					if ( count( $gateways ) === 1 ) {
						// if there are no gateways selected, but there is only one to select,
						// show this field
						$show_field = true;
					} elseif ( ! $show_field ) {
						$show_field = in_array( $name, $form_action->post_content['gateway'] );
					}
				}
			}
			if ( ! $show_field ) {
				$classes[ $field ] .= ' frm_hidden';
			}
			unset( $field );
		}

		return $classes;
	}

	public function add_new_pay_row() {
		$form_id   = FrmAppHelper::get_post_param( 'form_id', '', 'absint' );
		$row_num   = FrmAppHelper::get_post_param( 'row_num', '', 'absint' );
		$action_id = FrmAppHelper::get_post_param( 'email_id', '', 'absint' );

		$form_action = $this->get_single_action( $action_id );
		if ( empty( $form_action ) ) {
			$form_action     = new stdClass();
			$form_action->ID = $action_id;
			$this->_set( $action_id );
		}

		$form_action->post_content['change_field'][ $row_num ] = array( 'id' => '', 'value' => '', 'status' => '' );
		$this->after_pay_row( compact( 'form_id', 'row_num', 'form_action' ) );

		wp_die();
	}

	/**
	 * @param array $atts
	 * @return void
	 */
	public function after_pay_row( $atts ) {
		$id                  = 'frmtrans_after_pay_row_' . absint( $atts['form_action']->ID ) . '_' . $atts['row_num'];
		$atts['name']        = $this->get_field_name( 'change_field' );
		$atts['form_fields'] = $this->get_field_options( $atts['form_id'] );
		$action_control      = $this;

		include FrmTransAppHelper::plugin_path() . '/views/action-settings/_after_pay_row.php';
	}

	/**
	 * @param array $atts
	 * @return string
	 */
	public function after_payment_status( $atts ) {
		$status = array(
			'complete'      => __( 'Completed', 'formidable' ),
			'failed'        => __( 'Failed', 'formidable' ),
			'refunded'      => __( 'Refunded', 'formidable' ),
			'future-cancel' => __( 'Canceled', 'formidable' ),
			'canceled'      => __( 'Canceled and Expired', 'formidable-payments' ),
		);

		$name  = $this->get_field_name( 'change_field' );
		$input = '<select name="' . esc_attr( $name ) . '[' . absint( $atts['row_num'] ) . '][status]">';
		foreach ( $status as $value => $name ) {
			$selected_value = $atts['form_action']->post_content['change_field'][ $atts['row_num'] ]['status'];
			$selected       = selected( $selected_value, $value, false );
			$input         .= '<option value="' . esc_attr( $value ) . '" ' . $selected . '>' . esc_html( $name ) . '</option>';
		}
		$input .= '</select>';
		return $input;
	}

	/**
	 * @param array $atts
	 * @return string
	 */
	public function after_payment_field_dropdown( $atts ) {
		$name      = $this->get_field_name( 'change_field' );
		$dropdown  = '<select name="' . esc_attr( $name ) . '[' . absint( $atts['row_num'] ) . '][id]" >';
		$dropdown .= '<option value="">' . __( '&mdash; Select Field &mdash;', 'formidable-payments' ) . '</option>';

		$form_fields = $this->get_field_options( $atts['form_id'] );
		foreach ( $form_fields as $field ) {
			$selected_value = $atts['form_action']->post_content['change_field'][ $atts['row_num'] ]['id'];
			$selected       = selected( $selected_value, $field->id, false );
			$label          = FrmAppHelper::truncate( $field->name, 20 );
			$dropdown      .= '<option value="' . esc_attr( $field->id ) . '" '. $selected . '>' . $label . '</option>';
		}
		$dropdown .= '</select>';
		return $dropdown;
	}

	/**
	 * @param mixed $form_id
	 * @return array
	 */
	public function get_field_options( $form_id ) {
		$form_ids = array();
		if ( is_callable( 'FrmProFormsHelper::get_embedded_form_ids' ) ) {
			$form_ids = FrmProFormsHelper::get_embedded_form_ids( $form_id );
		}

		$form_ids[] = absint( $form_id );

		$form_fields = FrmField::getAll( array(
			'fi.form_id'  => $form_ids,
			'fi.type not' => array( 'divider', 'end_divider', 'html', 'break', 'captcha', 'rte', 'form' ),
		), 'field_order' );
		return $form_fields;
	}

	/**
	 * @param array $form_atts
	 * @param array $field_atts
	 * @return array
	 */
	public function maybe_show_fields_dropdown( $form_atts, $field_atts ) {
		$field_count = $field_id = 0;
		foreach ( $form_atts['form_fields'] as $field ) {
			if ( ! empty( $field_atts['allowed_fields'] ) && ! in_array( $field->type, (array) $field_atts['allowed_fields'] ) ) {
				continue;
			}
			$field_count++;
			$field_id = $field->id;
		}
		return compact( 'field_count', 'field_id' );
	}

	/**
	 * Show the dropdown fields for custom form fields.
	 *
	 * @since 2.09 The `$field_atts` might contain `skipped_fields`. By default, the submit field is skipped.
	 *
	 * @param  array $form_atts
	 * @param  array $field_atts
	 * @return void
	 */
	public function show_fields_dropdown( $form_atts, $field_atts ) {
		if ( ! isset( $field_atts['allowed_fields'] ) ) {
			$field_atts['allowed_fields'] = array();
		}

		if ( ! isset( $field_atts['skipped_fields'] ) ) {
			$field_atts['skipped_fields'] = array();

			if ( class_exists( 'FrmSubmitHelper' ) ) {
				$field_atts['skipped_fields'][] = FrmSubmitHelper::FIELD_TYPE;
			}
		}

		$has_field = false;
		?>
        <select class="frm_with_left_label" name="<?php echo esc_attr( $this->get_field_name( $field_atts['name'] ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( $field_atts['name'] ) ); ?>">
            <option value=""><?php esc_html_e( '&mdash; Select &mdash;', 'formidable' ); ?></option>
            <?php
            foreach ( $form_atts['form_fields'] as $field ) {
				if ( $field_atts['skipped_fields'] && in_array( $field->type, (array) $field_atts['skipped_fields'], true ) ) {
					continue;
				}

				if ( ! empty( $field_atts['allowed_fields'] ) && ! in_array( $field->type, (array) $field_atts['allowed_fields'], true ) ) {
					continue;
				}

				$has_field  = true;
				$key_exists = array_key_exists( $field_atts['name'], $form_atts['form_action']->post_content );
                ?>
                <option value="<?php echo esc_attr( $field->id ); ?>" <?php selected( $key_exists ? $form_atts['form_action']->post_content[ $field_atts['name'] ] : 0, $field->id ); ?>>
					<?php
					echo esc_attr( FrmAppHelper::truncate( $field->name, 50, 1 ) );
					if ( 'name' === $field->type ) {
						if ( 'billing_first_name' === $field_atts['name'] ) {
							echo ' (' . esc_html__( 'First', 'formidable' ) . ')';
						} elseif ( 'billing_last_name' === $field_atts['name'] ) {
							echo ' (' . esc_html__( 'Last', 'formidable' ) . ')';
						}
					}
					?>
                </option>
                <?php
				unset( $field );
            }

			if ( ! $has_field && ! empty( $field_atts['allowed_fields'] ) ) {
				$readable_fields = str_replace( '_', ' ', implode( ', ', (array) $field_atts['allowed_fields'] ) );
				if ( 'credit card' === $readable_fields ) {
					$readable_fields = __( 'payment', 'formidable-payments' );
				}
				?>
				<option value="">
					<?php echo esc_html( sprintf( __( 'Oops! You need a %s field in your form.', 'formidable' ), $readable_fields ) ); ?>
				</option>
			<?php
		}
		?>
		</select>
		<?php
	}

	/**
	 * This is here for < v2.01.
	 */
	public static function get_single_action_type( $action_id, $type = '' ) {
		$action_control = FrmFormActionsController::get_form_actions( 'payment' );
		return $action_control->get_single_action( $action_id );
	}
}
