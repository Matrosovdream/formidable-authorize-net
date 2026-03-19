<?php

class FrmTransListHelper extends FrmListHelper {

    private $table = '';

	/**
	 * An array of all valid entry ids.
	 * This is retrieved all at once with a single database query.
	 * This is used to determine if a specific entry is deleted.
	 * When an entry is deleted, there is no link to the deleted entry.
	 *
	 * @var int[] $valid_entry_ids
	 */
	private $valid_entry_ids = array();

	public function __construct( $args ) {
		$this->table = isset( $_REQUEST['trans_type'] ) ? $_REQUEST['trans_type'] : '';

		parent::__construct( $args );
		$this->screen->set_screen_reader_content(
			array(
				'heading_list' => $this->table === 'payments' || ! $this->table ? esc_html__( 'Payments list', 'formidable' ) : esc_html__( 'Subscriptions list', 'formidable-payments' ),
			)
		);
	}

	public function prepare_items() {
		global $wpdb;
        
		$orderby = FrmAppHelper::get_param( 'orderby', 'id', 'get', 'sanitize_title' );
		$order   = FrmAppHelper::get_param( 'order', 'DESC', 'get', 'sanitize_text_field' );

		$page     = $this->get_pagenum();
		$per_page = $this->get_items_per_page( 'formidable_page_formidable_payments_per_page');
		$start    = ( $page - 1 ) * $per_page;
		$start    = FrmAppHelper::get_param( 'start', $start, 'get', 'absint' );

		$query       = $this->get_table_query();
		$this->items = $wpdb->get_results( 'SELECT * ' . $query . " ORDER BY p.{$orderby} $order LIMIT $start, $per_page");
		$total_items = $wpdb->get_var( 'SELECT COUNT(*) ' . $query );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page
		) );
	}

	private function get_table_query() {
		global $wpdb;

		$table_name = ( $this->table == 'subscriptions' ) ? 'frm_subscriptions' : 'frm_payments';
		$form_id    = FrmAppHelper::get_param( 'form', 0, 'get', 'absint' );
		if ( $form_id ) {
			$query = $wpdb->prepare( "FROM {$wpdb->prefix}{$table_name} p LEFT JOIN {$wpdb->prefix}frm_items i ON (p.item_id = i.id) WHERE i.form_id = %d", $form_id );
		} else {
			$query = 'FROM ' . $wpdb->prefix . $table_name . ' p';
		}
		return $query;
	}

	public function no_items() {
		esc_html_e( 'No payments found.', 'formidable' );
	}

	public function get_views() {

		$statuses = array(
		    'payments'      => __( 'Payments', 'formidable-payments' ),
		    'subscriptions' => __( 'Subscriptions', 'formidable' ),
		);

	    $links = array();

		$frm_payment = new FrmTransPayment();
		$frm_sub     = new FrmTransSubscription();
	    $counts      = array(
			'payments'      => $frm_payment->get_count(),
			'subscriptions' => $frm_sub->get_count(),
		);
        $type        = isset( $_REQUEST['trans_type'] ) ? sanitize_text_field( $_REQUEST['trans_type'] ) : 'payments';

	    foreach ( $statuses as $status => $name ) {

	        if ( $status == $type ) {
    			$class = ' class="current"';
    		} else {
    		    $class = '';
    		}

    		if ( $counts[ $status ] || 'published' === $status ) {
				$links[ $status ] = '<a href="' . esc_url( '?page=formidable-payments&trans_type=' . $status ) . '" ' . $class . '>' . sprintf( esc_html__( '%1$s %2$s(%3$s)%4$s', 'formidable' ), esc_html( $name ), '<span class="count">', number_format_i18n( $counts[ $status ] ), '</span>' ) . '</a>';
		    }

		    unset( $status, $name );
	    }

		return $links;
	}

	public function get_columns() {
	    return FrmTransListsController::payment_columns();
	}

	public function get_sortable_columns() {
		return array(
		    'item_id'        => 'item_id',
			'amount'         => 'amount',
			'created_at'     => 'created_at',
			'receipt_id'     => 'receipt_id',
			'sub_id'         => 'sub_id',
			'begin_date'     => 'begin_date',
			'expire_date'    => 'expire_date',
			'paysys'         => 'paysys',
			'status'         => 'status',
			'next_bill_date' => 'next_bill_date',
		);
	}
	
	public function get_bulk_actions(){
	    $actions = array( 'bulk_delete' => __( 'Delete' ) );
            
        return $actions;
    }

	public function extra_tablenav( $which ) {
		$footer = ( $which != 'top' );
		if ( ! $footer ) {
			$form_id = isset( $_REQUEST['form'] ) ? absint( $_REQUEST['form'] ) : 0;
			echo FrmFormsHelper::forms_dropdown( 'form', $form_id, array( 'blank' => __( 'View all forms', 'formidable' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<input id="post-query-submit" class="button" type="submit" value="Filter" name="filter_action">';
		}
	}

	public function display_rows() {
		$date_format = FrmTransAppHelper::get_date_format();
		$gateways    = FrmTransAppHelper::get_gateways();

		$alt = 0;

		$form_ids              = $this->get_form_ids();
		$args                  = compact( 'form_ids', 'date_format', 'gateways' );
		$this->valid_entry_ids = array_keys( $form_ids ); // $form_ids is indexed by entry ID.

		foreach ( $this->items as $item ) {
			echo '<tr id="payment-' . esc_attr( $item->id ) . '" valign="middle" ';

			if ( 0 === ( $alt++ % 2 ) ) {
				echo 'class="alternate"';
			}

			echo '>';
			$this->display_columns( $item, $args );
			echo '</tr>';

			unset( $item );
		}
    }

	private function display_columns( $item, $args ) {
		list( $columns, $hidden ) = $this->get_column_info();

		foreach ( $columns as $column_name => $column_display_name ) {
			$attributes          = self::get_row_classes( compact( 'column_name', 'hidden' ) );
			$args['column_name'] = $column_name;
			$val                 = $this->get_column_value( $item, $args );

			if ( $column_name === 'cb' ) {
				echo $val; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				echo '<td ' . $attributes . '>' . $val . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				unset( $val );
			}
		}
	}

	private function get_column_value( $item, $args ) {
		$column_name   = $args['column_name'];
		$function_name = 'get_' . $column_name . '_column';

		if ( method_exists( $this, $function_name ) ) {
			$val = $this->$function_name( $item, $args );
		} else {
			$val = $item->$column_name ? $item->$column_name : '';

			if ( strpos( $column_name, '_date' ) !== false ) {
				if ( ! empty( $item->$column_name ) && $item->$column_name != '0000-00-00' ) {
					$val = FrmTransAppHelper::format_the_date( $item->$column_name, $args['date_format'] );
				} else {
					$val = '';
				}
			}
		}

		return $val;
	}

	/**
	 * Query the database for additional entry details for each payment.
	 *
	 * @return object[] Database row objects indexed by entry ID. Row objects 
	 */
	private function get_form_ids() {
		$entry_ids = array();
		foreach ( $this->items as $item ) {
			$entry_ids[] = absint( $item->item_id );
			unset( $item );
		}

		global $wpdb;
		$forms = $wpdb->get_results(
			"SELECT
				fo.id as form_id,
				fo.name,
				e.id
			FROM {$wpdb->prefix}frm_items e
			LEFT JOIN {$wpdb->prefix}frm_forms fo ON (e.form_id = fo.id)
			WHERE e.id in (" . implode(',', $entry_ids ) .")"
		);
		unset( $entry_ids );

		$form_ids = array();
		foreach ( $forms as $form ) {
			// $form is an object with "form_id", "name", and "id" properties.
			// $form->id is an entry ID.
			$form_ids[ (int) $form->id ] = $form;
			unset( $form );
		}

		return $form_ids;
	}

	private function get_row_classes( $atts ) {
		$class = 'column-' . $atts['column_name'];

		if ( in_array( $atts['column_name'], $atts['hidden'] ) ) {
			$class .= ' frm_hidden';
		}

		return 'class="' . esc_attr( $class ) . '"';
	}

	private function get_cb_column( $item ) {
		return '<th scope="row" class="check-column"><input type="checkbox" name="item-action[]" value="' . esc_attr( $item->id ) . '" /></th>';
	}

	private function get_receipt_id_column( $item ) {
		return $this->get_action_column( $item, 'receipt_id' );
	}

	private function get_action_column( $item, $field ) {
		$link = add_query_arg( array( 'action' => 'show', 'id' => $item->id, 'type' => $this->table ) );

		$val = '<strong><a class="row-title" href="' . esc_url( $link ) . '" title="' . esc_attr__( 'Edit' ) . '">';
		$val .= $item->{$field};
		$val .= '</a></strong><br />';

		$val .= $this->row_actions( $this->get_row_actions( $item ) );
		return $val;
	}

	private function get_row_actions( $item ) {
		$base_link   = '?page=formidable-payments&action=';
		$edit_link   = $base_link . 'edit&id=' . $item->id;
		$view_link   = $base_link . 'show&id=' . $item->id . '&type=' .  $this->table;
		$delete_link = $base_link . 'destroy&id=' . $item->id . '&type=' . $this->table;

		$actions         = array();
		$actions['view'] = '<a href="' . esc_url( $view_link ) . '">' . esc_html__( 'View', 'formidable' ) . '</a>';

		if ( $this->table != 'subscriptions' ) {
			$actions['edit']   = '<a href="' . esc_url( $edit_link ) . '">' . esc_html__( 'Edit', 'formidable' ) . '</a>';
		}

		$actions['delete'] = '<a href="' . esc_url( wp_nonce_url( $delete_link ) ) . '" data-frmverify="' . esc_attr__( 'Permanently delete this payment?', 'formidable' ) . '" data-frmverify-btn="frm-button-red">' . esc_html__( 'Delete', 'formidable' ) . '</a>';

		return $actions;
	}

	/**
	 * Get the column value for displaying an entry ID.
	 *
	 * @param object $item A payment or subscription object.
	 * @return string
	 */
	private function get_item_id_column( $item ) {
		$entry_id         = (int) $item->item_id;
		$entry_is_deleted = ! in_array( $entry_id, $this->valid_entry_ids );

		if ( $entry_is_deleted ) {
			return sprintf( __( '%d (Deleted)', 'formidable' ), $entry_id );
		}

		return '<a href="' . esc_url( '?page=formidable-entries&frm_action=show&action=show&id=' . $entry_id ) . '">' . absint( $entry_id ) . '</a>';
	}

	private function get_form_id_column( $item, $atts ) {
		if ( isset( $atts['form_ids'][ $item->item_id ] ) ) {
			$form_link = FrmFormsHelper::edit_form_link( $atts['form_ids'][ $item->item_id ]->form_id );
			return $form_link;
		}

		return '';
	}

	private function get_user_id_column( $item ) {
		global $wpdb;
		$val = FrmDb::get_var( $wpdb->prefix .'frm_items', array( 'id' => $item->item_id ), 'user_id' );
		return FrmTransAppHelper::get_user_link( $val );
	}

	private function get_created_at_column( $item, $atts ) {
		if ( empty( $item->created_at ) || $item->created_at == '0000-00-00 00:00:00' ) {
			$val = '';
		} else {
			$date = FrmAppHelper::get_localized_date( $atts['date_format'], $item->created_at );
			$date_title = FrmAppHelper::get_localized_date( $atts['date_format'] . ' g:i:s A', $item->created_at );
			$val = '<abbr title="' . esc_attr( $date_title ) . '">' . $date . '</abbr>';
		}
		return $val;
	}

	private function get_amount_column( $item ) {
		if ( $this->table == 'subscriptions' ) {
			$val = FrmTransAppHelper::format_billing_cycle( $item );
		} else {
			$val = FrmTransAppHelper::formatted_amount( $item );
		}
		return $val;
	}

	private function get_end_count_column( $item ) {
		$limit = ( $item->end_count >= 9999 ) ? __( 'unlimited', 'formidable' ) : $item->end_count;

		$frm_payment = new FrmTransPayment();
		$completed_payments = $frm_payment->get_all_by( $item->id, 'sub_id' );
		$count = 0;

		foreach ( $completed_payments as $completed_payment ) {
			if ( $completed_payment->status === 'complete' ) {
				$count++;
			}
		}

		return sprintf( __( '%1$s of %2$s', 'formidable' ), $count, $limit );
	}

	/**
	 * Get the string for the "Processor" column.
	 *
	 * @param stdClass $item
	 * @param array    $atts
	 * @return string
	 */
	private function get_paysys_column( $item, $atts ) {
		if ( isset( $atts['gateways'][ $item->paysys ] ) ) {
			return $atts['gateways'][ $item->paysys ]['label'];
		}

		if ( 'paypal' === $item->paysys ) {
			return 'PayPal';
		}

		return $item->paysys;
	}

	/**
	 * @param stdClass $item
	 * @return string
	 */
	private function get_status_column( $item ) {
		$status = esc_html( FrmTransAppHelper::show_status( FrmTransAppHelper::get_payment_status( $item ) ) );

		if ( 'processing' === $item->status ) {
			$status .= $this->get_processing_tooltip();
		}

		return $status;
	}

	/**
	 * Display 'Test' or 'Live' in a mode column if the value is known.
	 * Old payment entries will have a NULL 'test' column value.
	 *
	 * @since 2.07
	 *
	 * @param stdClass $item Payment or Subscription object.
	 * @return string
	 */
	private function get_mode_column( $item ) {
		return esc_html( FrmTransAppHelper::get_test_mode_display_string( $item ) );
	}

	/**
	 * @since 2.04
	 *
	 * @return string
	 */
	private function get_processing_tooltip() {
		if ( ! is_callable( 'FrmAppHelper::clip' ) || ! is_callable( 'FrmAppHelper::array_to_html_params' ) ) {
			// FrmAppHelper::clip was added in v5.0.13 at the same time the $echo = true param was added to FrmAppHelper::array_to_html_params.
			// The check is just for both functions as it is safer if one is removed later.
			return '';
		}

		return FrmAppHelper::clip(
			function() {
				$params = array(
					'class' => 'frm_help frm_icon_font frm_tooltip_icon',
					'title' => __( 'This payment method may take between 4-5 business days to process.', 'formidable' ),
				);
				?>
				<span <?php FrmAppHelper::array_to_html_params( $params, true ); ?>></span>
				<?php
			}
		);
	}

	private function get_sub_id_column( $item ) {
		if ( empty( $item->sub_id ) ) {
			$val = '';
		} elseif ( $this->table == 'subscriptions' ) {
			$val = $this->get_action_column( $item, 'sub_id' );
		} else {
			$val = '<a href="' . esc_url( '?page=formidable-payments&action=show&type=subscriptions&id=' . $item->sub_id ) . '">' . $item->sub_id . '</a>';
		}
		return $val;
	}

	/**
	 * @return string
	 */
	protected function confirm_bulk_delete() {
		$page = $this->table === 'subscriptions' ? 'subscriptions' : 'payments';

		/* translators: %s: The current trans type, either payments/subscriptions */
		return sprintf( __( 'ALL selected %s in this form will be permanently deleted. Want to proceed?', 'formidable-payments' ), $page );
	}
}
