<div class="frm_grid_container">
	<p>
		<label for="<?php echo esc_attr( $action_control->get_field_id( 'description' ) ); ?>">
			<?php esc_html_e( 'Description', 'formidable' ); ?>
		</label>
		<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'description' ) ); ?>" id="<?php echo esc_attr( $action_control->get_field_id( 'description' ) ); ?>" value="<?php echo esc_attr( $form_action->post_content['description'] ); ?>" class="frm_not_email_subject large-text" />
	</p>

	<p class="frm6">
		<label for="<?php echo esc_attr( $action_control->get_field_id( 'amount' ) ); ?>">
			<?php esc_html_e( 'Amount', 'formidable'); ?>
		</label>
		<input type="text" value="<?php echo esc_attr( $form_action->post_content['amount'] ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'amount' ) ); ?>" id="<?php echo esc_attr( $action_control->get_field_id( 'amount' ) ); ?>" class="frm_not_email_subject large-text" />
	</p>

	<?php
	$cc_field = $this->maybe_show_fields_dropdown( $field_dropdown_atts, array( 'name' => 'credit_card', 'allowed_fields' => 'credit_card' ) );
	if ( $cc_field['field_count'] === 1 ) {
		?>
		<input type="hidden" name="<?php echo esc_attr( $this->get_field_name( 'credit_card' ) ); ?>" value="<?php echo esc_attr( $cc_field['field_id'] ); ?>" />
		<?php
	} else {
	?>
		<p class="<?php echo esc_attr( $classes['credit_card'] ); ?> frm6">
			<label for="<?php echo esc_attr( $this->get_field_id( 'credit_card' ) ); ?>">
				<?php esc_html_e( 'Payment', 'formidable' ); ?>
			</label>
			<?php
			$this->show_fields_dropdown(
				$field_dropdown_atts,
				array(
					'name'           => 'credit_card',
					'allowed_fields' => 'credit_card',
				)
			);
			?>
		</p>
	<?php
	}
	?>

	<p class="frm6">
		<label>
			<?php esc_html_e( 'Payment Type', 'formidable' ); ?>
		</label>
		<select name="<?php echo esc_attr( $this->get_field_name( 'type' ) ); ?>" class="frm_trans_type">
			<option value="single" <?php selected( $form_action->post_content['type'], 'one_time' ); ?>><?php esc_html_e( 'One-time Payment', 'formidable' ); ?></option>
			<option value="recurring" <?php selected( $form_action->post_content['type'], 'recurring' ); ?>><?php esc_html_e( 'Recurring', 'formidable' ); ?></option>
		</select>
	</p>

	<p class="frm_trans_sub_opts frm6 <?php echo $form_action->post_content['type'] === 'recurring' ? '' : 'frm_hidden'; ?>">
		<label>
			<?php esc_html_e( 'Repeat Every', 'formidable' ); ?>
		</label>
		<span class="frm_grid_container">
			<input class="frm6" type="number" name="<?php echo esc_attr( $this->get_field_name( 'interval_count' ) ); ?>" value="<?php echo esc_attr( $form_action->post_content['interval_count'] ); ?>" max="90" min="1" step="1" />
			<select class="frm6" name="<?php echo esc_attr( $this->get_field_name( 'interval' ) ); ?>">
				<?php foreach ( FrmTransAppHelper::get_repeat_times() as $k => $v ) { ?>
					<option value="<?php echo esc_attr($k); ?>" <?php selected( $form_action->post_content['interval'], $k ); ?>><?php echo esc_html( $v ); ?></option>
				<?php } ?>
			</select>
			<input type="hidden" name="<?php echo esc_attr( $this->get_field_name( 'payment_count' ) ); ?>" value="<?php echo esc_attr( $form_action->post_content['payment_count'] ); ?>" />
		</span>
	</p>

	<p class="frm_trans_sub_opts frm6 <?php echo $form_action->post_content['type'] === 'recurring' ? '' : 'frm_hidden'; ?>">
		<label>
			<?php esc_html_e( 'Recurring Payment Limit', 'formidable' ); ?>
		</label>
		<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'payment_limit' ) ); ?>" value="<?php echo esc_attr( $form_action->post_content['payment_limit'] ); ?>" id="<?php echo esc_attr( $action_control->get_field_id( 'payment_limit' ) ); ?>" class="frm_not_email_subject" />
	</p>

	<p class="frm_trans_sub_opts frm6 <?php echo $form_action->post_content['type'] === 'recurring' ? '' : 'frm_hidden'; ?>">
		<label>
			<?php esc_html_e( 'Trial Period', 'formidable' ); ?>
		</label>
		<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'trial_interval_count' ) ); ?>" value="<?php echo esc_attr( $form_action->post_content['trial_interval_count'] ); ?>" id="<?php echo esc_attr( $action_control->get_field_id( 'trial_interval_count' ) ); ?>" class="frm_not_email_subject" />
		<?php esc_html_e( 'day(s)', 'formidable' ); ?>
	</p>

	<?php if ( isset( $form_action->post_content['capture'] ) ) { ?>
	<p class="frm_gateway_no_recur frm6 <?php echo esc_attr( $form_action->post_content['type'] === 'recurring' ? 'frm_hidden' : '' ); ?>">
		<label>
			<?php esc_html_e( 'Capture Payment', 'formidable' ); ?>
		</label>
		<select name="<?php echo esc_attr( $this->get_field_name( 'capture' ) ); ?>" id="<?php echo esc_attr( $action_control->get_field_id( 'capture' ) ); ?>">
			<option value="">
				<?php esc_html_e( 'When entry is submitted', 'formidable' ); ?>
			</option>
			<option value="authorize" <?php selected( $form_action->post_content['capture'], 'authorize' ); ?>>
				<?php esc_html_e( 'Later (collect manually)', 'formidable-payments' ); ?>
			</option>
		</select>
	</p>
	<?php } ?>

	<p class="frm6">
		<label for="<?php echo esc_attr( $this->get_field_id( 'currency' ) ); ?>">
			<?php esc_html_e( 'Currency', 'formidable' ); ?>
		</label>
		<?php
		if ( is_callable( 'FrmTransLiteAppHelper::show_currency_dropdown' ) ) {
			// This was introduced in v6.22 of Lite (with the Square release. Scheduled for June 12, 2025. ).
			FrmTransLiteAppHelper::show_currency_dropdown( $this->get_field_id( 'currency' ), $this->get_field_name( 'currency' ), $form_action->post_content );
		} else {
			?>
			<select name="<?php echo esc_attr( $this->get_field_name( 'currency' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'currency' ) ); ?>">
				<?php
				foreach ( FrmTransAppHelper::get_currencies() as $code => $currency ) {
					?>
					<option value="<?php echo esc_attr( strtolower( $code ) ); ?>" <?php selected( $form_action->post_content['currency'], strtolower( $code ) ); ?>><?php echo esc_html( $currency['name'] . ' (' . strtoupper( $code ) . ')' ); ?></option>
					<?php
					unset( $currency, $code );
				}
			?>
			</select>
			<?php
		}
		?>
	</p>

	<p>
		<?php
			esc_html_e( 'Gateway(s)', 'formidable-payments' );

			if ( in_array( 'authnet_aim', array_keys( $gateways ), true ) || apply_filters( 'frm_gateway_setting_checkbox', false ) ) {
				$gateway_input_type = 'checkbox';
			} else {
				$gateway_input_type = 'radio';
			}

			foreach ( $gateways as $gateway_name => $gateway ) {
				$gateway_classes  = $gateway['recurring'] ? '' : 'frm_gateway_no_recur';
				$gateway_classes .= $form_action->post_content['type'] === 'recurring' && ! $gateway['recurring'] ? ' frm_hidden' : '';
				$gateway_id       = $this->get_field_id( 'gateways' ) . '_' . $gateway_name;
		?>
			<label for="<?php echo esc_attr( $gateway_id ); ?>" class="frm_gateway_opt <?php echo esc_attr( $gateway_classes ); ?>">
				<?php if ( count( $gateways ) == 1 ) { ?>
					<input type="hidden" value="<?php echo esc_attr( $gateway_name ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'gateway' ) ); ?>[]" />
				<?php } else { ?>
					<input type="<?php echo esc_attr( $gateway_input_type ); ?>" value="<?php echo esc_attr( $gateway_name ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'gateway' ) ); ?>[]" id="<?php echo esc_attr( $gateway_id ); ?>" <?php FrmAppHelper::checked( $form_action->post_content['gateway'], $gateway_name ); ?>/>
				<?php } ?>
				<?php echo esc_html( $gateway['label'] ); ?> &nbsp;
			</label>
		<?php
			}
		?>
	</p>

	<?php
	foreach ( $gateways as $gateway_name => $gateway ) {
		do_action( 'frm_pay_show_' . $gateway_name . '_options', array(
			'form_action' => $form_action, 'action_control' => $this,
		) );	
	}
	?>

</div>
<div class="<?php echo esc_attr( $classes['bank_account'] ); ?>">
	<h3>
		<?php esc_html_e( 'Bank Account Details', 'formidable-payments' ); ?>
	</h3>
</div>

	<p class="<?php echo esc_attr( $classes['bank_account'] ); ?>">
		<label for="<?php echo esc_attr( $action_control->get_field_id( 'routing_num' ) ); ?>">
			<?php esc_html_e( 'Routing Number', 'formidable-payments' ); ?>
		</label>
		<?php $action_control->show_fields_dropdown( $field_dropdown_atts, array( 'name' => 'routing_num' ) ); ?>
	</p>
	<p class="<?php echo esc_attr( $classes['bank_account'] ); ?>">
		<label for="<?php echo esc_attr( $action_control->get_field_id( 'account_num' ) ); ?>">
			<?php esc_html_e( 'Account Number', 'formidable-payments' ); ?>
		</label>
		<?php $action_control->show_fields_dropdown( $field_dropdown_atts, array( 'name' => 'account_num' ) ); ?>
	</p>
	<p class="<?php echo esc_attr( $classes['bank_account'] ); ?>">
		<label for="<?php echo esc_attr( $action_control->get_field_id( 'bank_name' ) ); ?>">
			<?php esc_html_e( 'Bank Name', 'formidable-payments' ); ?>
		</label>
		<?php $action_control->show_fields_dropdown( $field_dropdown_atts, array( 'name' => 'bank_name' ) ); ?>
	</p>

<div class="frm_grid_container">
	<h3>
		<?php esc_html_e( 'Customer Information', 'formidable' ); ?>
	</h3>

	<p class="frm6">
		<label for="<?php echo esc_attr( $action_control->get_field_id( 'email' ) ); ?>">
			<?php esc_html_e( 'Email', 'formidable' ); ?>
		</label>
		<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'email' ) ); ?>" id="<?php echo esc_attr( $action_control->get_field_id( 'email' ) ); ?>" value="<?php echo esc_attr( $form_action->post_content['email'] ); ?>" class="frm_not_email_to large-text" />
	</p>

	<p class="<?php echo esc_attr( $classes['billing_address'] ); ?> frm6">
		<label for="<?php echo esc_attr( $action_control->get_field_id( 'billing_address' ) ); ?>">
			<?php esc_html_e( 'Address', 'formidable' ); ?>
		</label>
		<?php $action_control->show_fields_dropdown( $field_dropdown_atts, array( 'name' => 'billing_address', 'allowed_fields' => 'address' ) ); ?>
	</p>
	<p class="<?php echo esc_attr( $classes['billing_first_name'] ); ?> frm6">
		<label for="<?php echo esc_attr( $this->get_field_id( 'billing_first_name' ) ); ?>">
			<?php esc_html_e( 'First Name', 'formidable' ); ?>
		</label>
		<?php $this->show_fields_dropdown( $field_dropdown_atts, array( 'name' => 'billing_first_name' ) ); ?>
	</p>
	<p class="<?php echo esc_attr( $classes['billing_last_name'] ); ?> frm6">
		<label for="<?php echo esc_attr( $this->get_field_id( 'billing_last_name' ) ); ?>">
			<?php esc_html_e( 'Last Name', 'formidable' ); ?>
		</label>
		<?php $this->show_fields_dropdown( $field_dropdown_atts, array( 'name' => 'billing_last_name' ) ); ?>
	</p>
	<p class="<?php echo esc_attr( $classes['billing_company'] ); ?> frm6">
		<label for="<?php echo esc_attr( $this->get_field_id( 'billing_company' ) ); ?>">
			<?php esc_html_e( 'Company', 'formidable-payments' ); ?>
		</label>
		<?php $this->show_fields_dropdown( $field_dropdown_atts, array( 'name' => 'billing_company' ) ); ?>
	</p>
</div>

<div class="frm_trans_shipping <?php echo esc_attr( $classes['use_shipping'] ); ?>">
	<h3>
		<?php esc_html_e( 'Shipping Information', 'formidable-payments' ); ?>
		<?php FrmTransActionsController::show_svg_tooltip( esc_html__( 'Select the fields to associate with the customer shipping information.', 'formidable-payments' ) ); ?>
	</h3>
</div>
<div class="frm_grid_container">
	<p class="<?php echo esc_attr( $classes['use_shipping'] ); ?> frm6">
		<?php esc_html_e( 'Shipping', 'formidable-payments' ); ?>
		<label for="<?php echo esc_attr( $action_control->get_field_id( 'use_shipping' ) ); ?>">
			<input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'use_shipping' ) ); ?>" id="<?php echo esc_attr( $action_control->get_field_id( 'use_shipping' ) ); ?>" value="1" <?php checked( $form_action->post_content['use_shipping'], 1 ); ?> class="frm_trans_shipping_box" />
			<?php esc_html_e( 'Collect shipping information with this form', 'formidable-payments' ); ?>
		</label>
	</p>

	<p class="frm_trans_shipping frm6 <?php echo esc_attr( $classes['shipping_address'] ); ?>">
		<label for="<?php echo esc_attr( $action_control->get_field_id( 'shipping_address' ) ); ?>">
			<?php esc_html_e( 'Address', 'formidable' ); ?>
		</label>
		<?php $action_control->show_fields_dropdown( $field_dropdown_atts, array( 'name' => 'shipping_address', 'allowed_fields' => 'address', ) );?>
	</p>

	<p class="frm_trans_shipping frm6 <?php echo esc_attr( $classes['shipping_first_name'] ); ?>">
		<label for="<?php echo esc_attr( $action_control->get_field_id( 'shipping_first_name' ) ); ?>">
			<?php esc_html_e( 'First Name', 'formidable' ); ?>
		</label>
		<?php $action_control->show_fields_dropdown( $field_dropdown_atts, array( 'name' => 'shipping_first_name' ) ); ?>
	</p>
	<p class="frm_trans_shipping frm6 <?php echo esc_attr( $classes['shipping_last_name'] ); ?>">
		<label for="<?php echo esc_attr( $action_control->get_field_id( 'shipping_last_name' ) ); ?>">
			<?php esc_html_e( 'Last Name', 'formidable' ); ?>
		</label>
		<?php $action_control->show_fields_dropdown( $field_dropdown_atts, array( 'name' => 'shipping_last_name' ) ); ?>
	</p>

	<p class="frm_trans_shipping frm6 <?php echo esc_attr( $classes['shipping_company'] ); ?>">
		<label for="<?php echo esc_attr( $action_control->get_field_id( 'shipping_company' ) ); ?>">
			<?php esc_html_e( 'Company', 'formidable-payments' ); ?>
		</label>
		<?php $action_control->show_fields_dropdown( $field_dropdown_atts, array( 'name' => 'shipping_company' ) ); ?>
	</p>
</div>

<h3>
	<?php esc_html_e( 'After Payment', 'formidable-payments' ); ?>
	<?php FrmTransActionsController::show_svg_tooltip( esc_html__( 'Change a field value when the status of a payment changes.', 'formidable-payments' ) ); ?>
</h3>

<div class="frm_add_remove">
	<p id="frmtrans_after_pay_<?php echo absint( $form_action->ID ); ?>" <?php echo empty( $form_action->post_content['change_field'] ) ? '' : 'class="frm_hidden"'; ?>>
		<a href="#" class="frm_add_trans_logic button" data-emailkey="<?php echo absint( $form_action->ID ); ?>">
			+ <?php esc_html_e( 'Add', 'formidable-payments' ); ?>
		</a>
	</p>
	<div id="postcustomstuff" class="frmtrans_after_pay_rows <?php echo empty( $form_action->post_content['change_field'] ) ? 'frm_hidden' : ''; ?>">
		<table id="list-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Payment Status', 'formidable-payments' ); ?></th>
					<th><?php esc_html_e( 'Field', 'formidable-payments' ); ?></th>
					<th><?php esc_html_e( 'Value', 'formidable-payments' ); ?></th>
					<th style="max-width:60px;"></th>
				</tr>
			</thead>
			<tbody data-wp-lists="list:meta">
				<?php
				foreach ( $form_action->post_content['change_field'] as $row_num => $vals ) {
					$this->after_pay_row( array(
						'form_id' => $args['form']->id, 'row_num' => $row_num, 'form_action' => $form_action,
					) );
				}
				?>
			</tbody>
		</table>
	</div>
</div>
