<?php

class FrmTransListsController {

	/**
	 * @return void
	 */
	public static function add_list_hooks() {
		if ( ! class_exists( 'FrmAppHelper' ) ) {
			return;
		}

		$frm_settings = FrmAppHelper::get_settings();

		add_filter( 'manage_' . sanitize_title( $frm_settings->menu ) . '_page_formidable-payments_columns', 'FrmTransListsController::payment_columns' );
		add_filter( 'frm_entries_payment_expiration_column', 'FrmTransEntriesController::entry_payment_expiration_column', 10, 2 );
	}

	/**
	 * @param array $columns
	 * @return array
	 */
	public static function payment_columns( $columns = array() ) {
		add_screen_option(
			'per_page',
			array(
				'label'   => esc_html__( 'Payments', 'formidable-payments' ),
				'default' => 20,
				'option'  => 'formidable_page_formidable_payments_per_page',
			)
		);

		$type = isset( $_REQUEST['trans_type'] ) ? $_REQUEST['trans_type'] : 'payments';

		$columns['cb']      = '<input type="checkbox" />';
		$columns['user_id'] = esc_html__( 'Customer', 'formidable' );

		if ( 'subscriptions' === $type ) {
			$add_columns = array(
				'sub_id'         => esc_html__( 'Profile ID', 'formidable' ),
				'item_id'        => esc_html__( 'Entry', 'formidable' ),
				'form_id'        => esc_html__( 'Form', 'formidable' ),
				'amount'         => esc_html__( 'Billing Cycle', 'formidable' ),
				'end_count'      => esc_html__( 'Payments Made', 'formidable' ),
				'next_bill_date' => esc_html__( 'Next Bill Date', 'formidable' ),
			);
		} else {
			$add_columns = array(
				'receipt_id'  => esc_html__( 'Receipt ID', 'formidable' ),
				'item_id'     => esc_html__( 'Entry', 'formidable' ),
				'form_id'     => esc_html__( 'Form', 'formidable' ),
				'amount'      => esc_html__( 'Amount', 'formidable' ),
				'sub_id'      => esc_html__( 'Subscription', 'formidable' ),
				'begin_date'  => esc_html__( 'Begin Date', 'formidable' ),
				'expire_date' => esc_html__( 'Expire Date', 'formidable' ),
			);
		}

		$columns = $columns + $add_columns;

		$columns['status']     = esc_html__( 'Status', 'formidable' );
		$columns['created_at'] = esc_html__( 'Date', 'formidable' );
		$columns['paysys']     = esc_html__( 'Processor', 'formidable' );
		$columns['mode']       = esc_html__( 'Mode', 'formidable' );

		return $columns;
	}

	public static function save_per_page( $save, $option, $value ) {
		if ( $option === 'formidable_page_formidable_payments_per_page' ) {
			$save = absint( $value );
		}
		return $save;
	}

	/**
	 * Handle payment/subscription list routing.
	 *
	 * @param string $action
	 */
	public static function route( $action ) {
		if ( empty( $action ) || $action === 'list' ) {
			$bulk_action = self::get_bulk_action();

			if ( ! empty( $bulk_action ) ) {
				if ( $_GET && $bulk_action && array_key_exists( 'REQUEST_URI', $_SERVER ) ) {
					$_SERVER['REQUEST_URI'] = str_replace( '&action=' . $bulk_action, '', $_SERVER['REQUEST_URI'] );
				}

				return self::bulk_actions( $bulk_action );
			} else {
				return self::display_list();
			}
		}
	}

	/**
	 * Check for a bulk action.
	 *
	 * @return string|false
	 */
	private static function get_bulk_action() {
		$action = FrmAppHelper::get_param( 'action', '', 'get', 'sanitize_text_field' );
		if ( $action == -1 ) {
			$action = FrmAppHelper::get_param( 'action2', '', 'get', 'sanitize_text_field' );
		}

		if ( strpos( $action, 'bulk_' ) === 0 ) {
			return $action;
		}

		return false;
	}

	/**
	 * Maybe process bulk actions.
	 *
	 * @param string $action
	 * @return void
	 */
	private static function bulk_actions( $action ) {
		$response = array(
			'errors'  => array(),
			'message' => '',
		);

		$items = FrmAppHelper::get_param( 'item-action', '' );
		if ( empty( $items ) ) {
			$response['errors'][] = __( 'No payments were selected', 'formidable-payments' );
		} else {
			if ( ! is_array( $items ) ) {
				$items = explode( ',', $items );
			}

			$bulkaction = str_replace( 'bulk_', '', $action );
			if ( $bulkaction === 'delete' ) {
				self::bulk_delete( $items, $response );
			}
		}

		self::display_list( $response );
	}

	/**
	 * Handle bulk deleting payments.
	 *
	 * @param array $items
	 * @param array $response
	 * @return void
	 */
	private static function bulk_delete( $items, &$response ) {
		if ( ! current_user_can( 'frm_delete_entries' ) ) {
			$frm_settings         = FrmAppHelper::get_settings();
			$response['errors'][] = $frm_settings->admin_permission;
			return;
		}

		if ( is_array( $items ) ) {
			$frm_payment = new FrmTransPayment();
			foreach ( $items as $item_id ) {
				if ( $frm_payment->destroy( absint( $item_id ) ) ) {
					$response['message'] = __( 'Payments were Successfully Deleted', 'formidable-payments' );
				}
			}
		}
	}

	/**
	 * @return array
	 */
	public static function list_page_params() {
		$values = array();
		foreach ( array( 'id' => '', 'paged' => 1, 'form' => '', 'search' => '', 'sort' => '', 'sdir' => '' ) as $var => $default ) {
			$values[ $var ] = FrmAppHelper::get_param( $var, $default );
		}

		return $values;
	}

	/**
	 * Display a list.
	 *
	 * @param array $response
	 * @return void
	 */
	public static function display_list( $response = array() ) {
		$defaults = array( 'errors' => array(), 'message' => '' );
		$response = array_merge( $defaults, $response );
		$errors   = $response['errors'];
		$message  = $response['message'];

		$wp_list_table = new FrmTransListHelper( self::list_page_params() );

		$pagenum = $wp_list_table->get_pagenum();

		$wp_list_table->prepare_items();

		$total_pages = $wp_list_table->get_pagination_arg( 'total_pages' );
		if ( $pagenum > $total_pages && $total_pages > 0 ) {
			// if the current page is higher than the total pages,
			// reset it and prepare again to get the right entries
			$_GET['paged'] = $_REQUEST['paged'] = $total_pages;
			$pagenum       = $wp_list_table->get_pagenum();
			$wp_list_table->prepare_items();
		}

		include FrmTransAppHelper::plugin_path() . '/views/lists/list.php';
	}
}
