<table class="form-table">
	<tbody id="frm_authnet_general_settings" class="form-body">

		<tr class="form-field" valign="top">
			<td class="" colspan="2">
				<h3>
					<?php
					esc_html_e( 'Authorize.net API Settings', 'frmauthnet' );
					FrmAuthNetSettingsController::show_svg_tooltip( __( 'Login to your Authorize.net Merchant account. From your Merchant account home page click on the Accounts tab. Next click Settings on the left sidebar. Then click API Login ID and Transaction Key (under security settings).', 'frmauthnet' ) );
					?>
				</h3>
			</td>
		</tr>

		<tr class="form-field" valign="top">
			<td class="frm_label_td">
				<label for="frm_authnet_environment">
					<?php esc_html_e( 'Environment', 'frmauthnet' ); ?>
				</label>
			</td>
			<td class="frm_input_td">
				<select name="frm_authnet_environment" id="frm_authnet_environment" class="frm_long_input">
					<option value="live" <?php selected( $settings->settings->environment, 'live' ); ?> <?php echo $ssl_disabled ? ' disabled="disabled"' : ''; ?>>
						<?php echo esc_html( __( 'Live', 'frmauthnet' ) . $ssl_message ); ?>
					</option>
					<option value="sandbox" <?php selected( $settings->settings->environment, 'sandbox' ); ?>>
						<?php esc_html_e( 'Testing', 'frmauthnet' ); ?>
					</option>
				</select>
			</td>
		</tr>
		<!-- ./Environment -->


		<tr class="form-field" valign="top">
			<td class="frm_label_td">
				<label for="frm_authnet_login_id">
					<?php
					esc_html_e( 'API Login ID', 'frmauthnet' );
					FrmAuthNetSettingsController::show_svg_tooltip( __( 'From your API Settings page copy your API Login ID and paste it here.', 'frmauthnet' ) );
					?>
				</label>
			</td>
			<td class="frm_input_td">
				<input type="text" name="frm_authnet_login_id" id="frm_authnet_login_id" value="<?php echo esc_attr( trim( $settings->settings->login_id ) ); ?>" class="frm_long_input"  />
			</td>
		</tr>
		<!-- ./ Authorize.net login ID -->

		<tr class="form-field" valign="top">
			<td class="frm_label_td">
				<label for="frm_authnet_transaction_key">
					<?php
					esc_html_e( 'Transaction Key', 'frmauthnet' );
					FrmAuthNetSettingsController::show_svg_tooltip( __( 'The Transaction key is for sending transactions to your account. From your API Settings page fill out your Secret Answer. Copy your Transaction Key and paste it here.', 'frmauthnet' ) );
					?>
				</label>
			</td>
			<td class="frm_input_td">
				<input type="text" name="frm_authnet_transaction_key" id="frm_authnet_transaction_key" value="<?php echo esc_attr( trim( $settings->settings->transaction_key ) ); ?>" class="frm_long_input"  />
			</td>
		</tr>

		<tr class="form-field" valign="top">
			<td class="frm_label_td">
				<label for="frm_authnet_signature_key">
					<?php
					esc_html_e( 'Signature Key', 'frmauthnet' );
					FrmAuthNetSettingsController::show_svg_tooltip( __( 'The Signature key is for receiving notifications from Authorize.net. From your API Settings page fill out your Secret Answer. Copy your Signature Key and paste it here.', 'frmauthnet' ) );
					?>
				</label>
			</td>
			<td class="frm_input_td">
				<input type="text" name="frm_authnet_signature_key" id="frm_authnet_signature_key" value="<?php echo esc_attr( trim( $settings->settings->signature_key ) ); ?>" class="frm_long_input"  />
			</td>
		</tr>

			<tr class="form-field aim_settings" valign="top">
				<td colspan="2">
					<h3>
						<?php
						esc_html_e( 'Receipt', 'frmauthnet' );
						FrmAuthNetSettingsController::show_svg_tooltip( __( 'The style and text on your receipt page.', 'frmauthnet' ) );
						?>
					</h3>
				</td>
			</tr>

			<!-- .Style Reciept? -->
			<tr class="form-field aim_settings" valign="top">
				<td>
					<label for="frm_authnet_reciept_heading_text">
						<?php esc_html_e( 'Header Text', 'frmauthnet' ); ?>
					</label>
				</td>
				<td>
					<input type="text" name="frm_authnet_reciept_heading_text" id="frm_authnet_reciept_heading_text" value="<?php echo esc_attr( $settings->settings->reciept_heading_text ); ?>" class="frm_long_input" />
				</td>
			</tr>
			<!-- ./Header Text - reciept_heading_text -->

			<tr class="form-field aim_settings" valign="top">
				<td>
					<label for="frm_authnet_reciept_footer_text">
						<?php esc_html_e( 'Footer Text', 'frmauthnet' ); ?>
					</label>
				</td>
				<td>
					<input type="text" name="frm_authnet_reciept_footer_text" id="frm_authnet_reciept_footer_text" value="<?php echo esc_attr( $settings->settings->reciept_footer_text ); ?>" class="frm_long_input" />
				</td>
			</tr>
			<!-- ./Footer Text - reciept_footer_text -->

		</tbody>

	</table>
