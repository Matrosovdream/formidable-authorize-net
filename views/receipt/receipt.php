
<article class="frm_m_t_1">
	<div class="frm_row frm_top_xs frm_center_xs">
		<header class="frm_col_xs_12 frm_m_t_2">
			<h2 class="frm_authnet_receipt_heading">
				<?php echo esc_html( $reciept_heading_text ); ?>
			</h2>
		</header>
	</div>
	<div class="frm_row frm_m_t_1">
		<section id="frm_authnet_order_summary" class="frm_center_xs <?php echo esc_attr( $order_summary_class ); ?>">
			<div class="frm_card">
				<header class="frm_card_header">
					<?php esc_html_e( 'Order Summary', 'frmauthnet' ); ?>
				</header>
				<dl class="frm_dl_horizontal frm_row">

					<dt class="frm_dt_horizontal <?php echo esc_attr( $receipt_item_dt_class ); ?>">
						<?php esc_html_e( 'Order #:', 'frmauthnet' ); ?>
					</dt>
					<dd id="frm_athnet_receipt_billing_name" class="frm_dd_horizontal <?php echo esc_attr( $receipt_item_dd_class ); ?>">
						<?php echo esc_html( $receipt_values['transaction_id'] ); ?>
					</dd>
					<dt class="frm_dt_horizontal <?php echo esc_attr( $receipt_item_dt_class ); ?>">
						<?php esc_html_e( 'Invoice #:', 'frmauthnet' ); ?>
					</dt>
					<dd id="frm_athnet_receipt_billing_company" class="frm_dd_horizontal <?php echo esc_attr( $receipt_item_dd_class ); ?>">
						<?php echo esc_html( $receipt_values['invoice_number'] ); ?>
					</dd>
					<dt class="frm_dt_horizontal <?php echo esc_attr( $receipt_item_dt_class ); ?>">
						<?php esc_html_e( 'Status:', 'frmauthnet' ); ?>
					</dt>
					<dd id="frm_athnet_receipt_billing_address" class="frm_dd_horizontal <?php echo esc_attr( $receipt_item_dd_class ); ?>">
						<?php echo esc_html( $receipt_values['status'] ); ?>
					</dd>
					<dt class="frm_dt_horizontal <?php echo esc_attr( $receipt_item_dt_class ); ?>">
						<?php esc_html_e( 'Total:', 'frmauthnet' ); ?>
					</dt>
					<dd id="frm_athnet_receipt_billing_address" class="frm_dd_horizontal <?php echo esc_attr( $receipt_item_dd_class ); ?>">
						$<?php echo esc_html( $receipt_values['amount'] ); ?>
					</dd>
				</dl>
			</div>
		</section>
		<section id="frm_authnet_receipt" class="<?php echo esc_attr( $receipt_class ); ?>">
			<div class="frm_top_xs frm_center_xs <?php echo esc_attr( $receipt_item_wrap_class ); ?>">
				<div class="<?php echo esc_attr( $receipt_item_class ); ?>">
					<?php
					if ( isset( $receipt_values['billing_address'] ) ) {
						$receipt_type = 'billing';
						include( FrmAuthNetHelper::path() . '/views/receipt/_address.php' );
					}

					if ( isset( $receipt_values['shipping_address'] ) ) {
						$receipt_type = 'shipping';
						include( FrmAuthNetHelper::path() . '/views/receipt/_address.php' );
					}
					?>
				</div>
			</div>
		</section>
	</div>
	<div class="frm_row frm_top_xs frm_center_xs">
		<footer class="frm_col_xs_12 frm_m_t_2">
			<p class="frm_authnet_receipt_footer">
				<?php echo esc_html( $reciept_footer_text ); ?>
			</p>
		</footer>
	</div>
</article>
