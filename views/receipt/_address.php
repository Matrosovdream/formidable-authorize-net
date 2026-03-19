
<div class="frm_card">
	<header class="frm_card_header">
		<?php
		if ( $receipt_type === 'billing' ) {
			esc_html__( 'Billing Details', 'frmauthnet' );
		} else {
			esc_html__( 'Shipping Details:', 'frmauthnet' );
		}
		?>
	</header>
	<dl class="frm_dl_horizontal frm_row">
		<?php if ( ! empty( $receipt_values[ $receipt_type . '_first_name' ] ) ) { ?>
			<dt class="frm_dt_horizontal <?php echo esc_attr( $receipt_item_dt_class ); ?>">
				<?php esc_html_e( 'Name:', 'frmauthnet' ); ?>
			</dt>
			<dd id="frm_athnet_receipt_address_name" class="frm_dd_horizontal <?php echo esc_attr( $receipt_item_dd_class ); ?>">
				<?php echo esc_html( $receipt_values[ $receipt_type . '_first_name' ] . ' ' . $receipt_values[ $receipt_type . '_last_name' ] ); ?>
			</dd>
		<?php } ?>
		<?php if ( ! empty( $receipt_values[ $receipt_type . '_company' ] ) ) : ?>
			<dt class="frm_dt_horizontal <?php echo esc_attr( $receipt_item_dt_class ); ?>">
				<?php esc_html_e( 'Company:', 'frmauthnet' ); ?>
			</dt>
			<dd id="frm_athnet_receipt_address_company" class="frm_dd_horizontal <?php echo esc_attr( $receipt_item_dd_class ); ?>">
				<?php echo esc_html( $receipt_values[ $receipt_type . '_company' ] ); ?>
			</dd>
		<?php endif ?>

		<?php if ( ! empty( $receipt_values[ $receipt_type . '_address' ] ) ) { ?>
			<dt class="frm_dt_horizontal <?php echo esc_attr( $receipt_item_dt_class ); ?>">
				<?php esc_html_e( 'Address:', 'frmauthnet' ); ?>
			</dt>
			<dd class="frm_dd_horizontal <?php echo esc_attr( $receipt_item_dd_class ); ?>">
				<address class="frm_address" id="frm_athnet_address">
					<?php
					$field_obj = FrmFieldFactory::get_field_type( 'address' );
					$address = $field_obj->get_display_value( $receipt_values[ $receipt_type . '_address' ] );
					echo FrmAppHelper::kses( $address, 'all' ); // WPCS: XSS ok.
					?>
				</address>
			</dd>
		<?php } ?>
	</dl>
</div>
