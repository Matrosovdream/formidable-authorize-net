<table class="frm_subscriptions_table">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Billing Cycle', 'formidable' ); ?></th>
			<th><?php esc_html_e( 'Next Bill Date', 'formidable' ); ?></th>
			<th></th>
		</tr>
	</thead>
	<tbody>
	<?php foreach ( $subscriptions as $sub ) { ?>
		<tr>
			<td style='--v-responsive-table-label:"<?php esc_attr_e( 'Billing Cycle', 'formidable' ); ?>"'><?php echo esc_html( FrmTransAppHelper::get_payment_description( $sub ) . ' - ' . FrmTransAppHelper::format_billing_cycle( $sub ) ); ?></td>
			<td style='--v-responsive-table-label:"<?php esc_attr_e( 'Next Bill Date', 'formidable' ); ?>"'><?php echo esc_html( FrmTransAppHelper::format_the_date( date('Y-m-d H:i:s', strtotime( $sub->next_bill_date ) ) ) ); ?></td>
			<td><?php FrmTransSubscriptionsController::show_cancel_link( $sub, false ); ?></td>
		</tr>
	<?php } ?>
	</tbody>
</table>
