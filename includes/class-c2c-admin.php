<?php
/**
 * Admin Order Meta Box & Verification Actions
 *
 * @package Woo_Card_To_Card_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WOO_C2C_Admin {

	/**
	 * Initialize Admin Hooks
	 */
	public static function init() {
		// Add meta boxes
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_box' ) );

		// Custom columns in order list
		add_filter( 'manage_edit-shop_order_columns', array( __CLASS__, 'add_order_column' ) );
		add_action( 'manage_shop_order_posts_custom_column', array( __CLASS__, 'render_order_column' ), 10, 2 );

		// HPOS custom columns
		add_filter( 'manage_woocommerce_page_wc-orders_columns', array( __CLASS__, 'add_order_column' ) );
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( __CLASS__, 'render_hpos_order_column' ), 10, 2 );

		// Bulk actions
		add_filter( 'bulk_actions-edit-shop_order', array( __CLASS__, 'register_bulk_actions' ) );
		add_filter( 'handle_bulk_actions-edit-shop_order', array( __CLASS__, 'handle_bulk_actions' ), 10, 3 );
		add_filter( 'bulk_actions-woocommerce_page_wc-orders', array( __CLASS__, 'register_bulk_actions' ) );
		add_filter( 'handle_bulk_actions-woocommerce_page_wc-orders', array( __CLASS__, 'handle_bulk_actions' ), 10, 3 );

		// Dashboard widget
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'add_dashboard_widget' ) );

		// AJAX handlers for approve / reject
		add_action( 'wp_ajax_woo_c2c_admin_approve', array( __CLASS__, 'handle_admin_approve' ) );
		add_action( 'wp_ajax_woo_c2c_admin_reject', array( __CLASS__, 'handle_admin_reject' ) );
	}
	
	/**
	 * Add Dashboard Widget
	 */
	public static function add_dashboard_widget() {
		if ( current_user_can( 'manage_woocommerce' ) ) {
			wp_add_dashboard_widget(
				'woo_c2c_dashboard_widget',
				__( 'فیش‌های بررسی نشده (کارت به کارت)', 'woo-card-to-card-pro' ),
				array( __CLASS__, 'render_dashboard_widget' )
			);
		}
	}
	
	/**
	 * Render Dashboard Widget
	 */
	public static function render_dashboard_widget() {
		$orders = wc_get_orders( array(
			'status' => 'wc-awaiting-receipt',
			'limit'  => -1,
			'return' => 'ids',
		) );
		
		$count = count( $orders );
		
		$url = admin_url( 'edit.php?post_status=wc-awaiting-receipt&post_type=shop_order' );
		if ( class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' ) && wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled() ) {
			$url = admin_url( 'admin.php?page=wc-orders&status=wc-awaiting-receipt' );
		}
		
		echo '<div style="display:flex; justify-content:space-between; align-items:center;">';
		echo '<p>' . sprintf( __( 'تعداد %d سفارش در انتظار بررسی فیش هستند.', 'woo-card-to-card-pro' ), $count ) . '</p>';
		if ( $count > 0 ) {
			echo '<a class="button button-primary" href="' . esc_url( $url ) . '">' . esc_html__( 'بررسی فیش‌ها', 'woo-card-to-card-pro' ) . '</a>';
		}
		echo '</div>';
	}
	
	/**
	 * Register bulk actions
	 */
	public static function register_bulk_actions( $bulk_actions ) {
		$bulk_actions['c2c_approve'] = __( 'تایید گروهی فیش‌ها', 'woo-card-to-card-pro' );
		return $bulk_actions;
	}

	/**
	 * Handle bulk actions
	 */
	public static function handle_bulk_actions( $redirect_to, $doaction, $post_ids ) {
		if ( 'c2c_approve' !== $doaction ) {
			return $redirect_to;
		}

		$approved = 0;
		$gateways = WC()->payment_gateways->payment_gateways();
		$target_status = 'processing';
		if ( isset( $gateways[ WOO_C2C_GATEWAY_ID ] ) ) {
			$target_status = $gateways[ WOO_C2C_GATEWAY_ID ]->get_option( 'approval_target_status', 'processing' );
		}

		foreach ( $post_ids as $post_id ) {
			$order = wc_get_order( $post_id );
			if ( ! $order ) {
				continue;
			}
			
			$status = $order->get_meta( '_c2c_receipt_status' );
			if ( 'approved' === $status || in_array( $order->get_status(), array( 'processing', 'completed' ), true ) ) {
				continue;
			}

			if ( $order->get_payment_method() === WOO_C2C_GATEWAY_ID ) {
				$order->update_meta_data( '_c2c_receipt_status', 'approved' );
				$order->set_status( $target_status, __( 'فیش کارت‌به‌کارت به صورت گروهی تایید شد.', 'woo-card-to-card-pro' ) );
				$order->save();
				$approved++;
			}
		}

		return add_query_arg( 'c2c_approved', $approved, $redirect_to );
	}

	/**
	 * Register Meta Box
	 */
	public static function register_meta_box() {
		$screen = class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' ) &&
				  wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
				  ? wc_get_page_screen_id( 'shop_order' )
				  : 'shop_order';

		add_meta_box(
			'woo_c2c_receipt_meta_box',
			__( '💳 بررسی فیش کارت به کارت', 'woo-card-to-card-pro' ),
			array( __CLASS__, 'render_meta_box_content' ),
			$screen,
			'side',
			'high'
		);
	}

	/**
	 * Render Meta Box Content in Order Edit Screen
	 *
	 * @param WP_Post|WC_Order $post_or_order_object
	 */
	public static function render_meta_box_content( $post_or_order_object ) {
		$order = ( $post_or_order_object instanceof WC_Order )
			? $post_or_order_object
			: wc_get_order( $post_or_order_object->ID );

		if ( ! $order ) {
			echo '<p>' . esc_html__( 'سفارش نامعتبر است.', 'woo-card-to-card-pro' ) . '</p>';
			return;
		}

		// Only display if paid with c2c or has receipt meta
		$payment_method = $order->get_payment_method();
		$receipt_url    = $order->get_meta( '_c2c_receipt_file_url' );

		if ( $payment_method !== WOO_C2C_GATEWAY_ID && empty( $receipt_url ) ) {
			echo '<p style="color:#666;font-size:13px;">' . esc_html__( 'روش پرداخت این سفارش کارت به کارت نیست.', 'woo-card-to-card-pro' ) . '</p>';
			return;
		}

		$order_id           = $order->get_id();
		$tracking_code      = $order->get_meta( '_c2c_tracking_code' );
		$sender_card        = $order->get_meta( '_c2c_sender_card' );
		$deposit_date       = $order->get_meta( '_c2c_deposit_date' );
		$receipt_status     = $order->get_meta( '_c2c_receipt_status' );
		$dest_card          = $order->get_meta( '_c2c_destination_card' );
		$dest_bank          = $order->get_meta( '_c2c_destination_bank' );
		$duplicate_hashes   = (array) $order->get_meta( '_c2c_duplicate_hash_orders' );
		$duplicate_tracks   = (array) $order->get_meta( '_c2c_duplicate_tracking_orders' );

		// Build secure receipt viewer URL.
		$receipt_token      = $order->get_meta( '_c2c_receipt_token' );
		$receipt_viewer_url = ! empty( $receipt_token ) ? home_url( '/c2c-receipt/' . $receipt_token ) : '';

		include WOO_C2C_PLUGIN_DIR . 'templates/admin-receipt-meta-box.php';
	}

	/**
	 * Add column header to orders list
	 *
	 * @param array $columns
	 * @return array
	 */
	public static function add_order_column( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $column ) {
			$new_columns[ $key ] = $column;
			if ( 'order_status' === $key ) {
				$new_columns['c2c_receipt_status'] = __( 'فیش کارت‌به‌کارت', 'woo-card-to-card-pro' );
			}
		}
		if ( ! isset( $new_columns['c2c_receipt_status'] ) ) {
			$new_columns['c2c_receipt_status'] = __( 'فیش کارت‌به‌کارت', 'woo-card-to-card-pro' );
		}
		return $new_columns;
	}

	/**
	 * Render column content for classic WP post orders
	 *
	 * @param string $column
	 * @param int $post_id
	 */
	public static function render_order_column( $column, $post_id ) {
		if ( 'c2c_receipt_status' === $column ) {
			$order = wc_get_order( $post_id );
			self::output_column_badge( $order );
		}
	}

	/**
	 * Render column content for HPOS orders
	 *
	 * @param string $column
	 * @param WC_Order $order
	 */
	public static function render_hpos_order_column( $column, $order ) {
		if ( 'c2c_receipt_status' === $column ) {
			self::output_column_badge( $order );
		}
	}

	/**
	 * Output the status badge in the order column
	 *
	 * @param WC_Order $order
	 */
	private static function output_column_badge( $order ) {
		if ( ! $order ) {
			return;
		}

		if ( $order->get_payment_method() !== WOO_C2C_GATEWAY_ID ) {
			echo '<span style="color:#aaa;">—</span>';
			return;
		}

		$receipt_url        = $order->get_meta( '_c2c_receipt_file_url' );
		$receipt_status     = $order->get_meta( '_c2c_receipt_status' );
		$duplicate_hashes   = (array) $order->get_meta( '_c2c_duplicate_hash_orders' );
		$duplicate_tracks   = (array) $order->get_meta( '_c2c_duplicate_tracking_orders' );

		// Check if there are fraud warnings
		if ( ! empty( $duplicate_hashes ) || ! empty( $duplicate_tracks ) ) {
			echo '<span class="c2c-badge c2c-badge-warning" title="' . esc_attr__( 'هشدار: تطابق رسید یا کد پیگیری با سفارش دیگر!', 'woo-card-to-card-pro' ) . '">⚠️ مشکوک به تکرار</span>';
			return;
		}

		if ( ! empty( $receipt_url ) ) {
			if ( 'approved' === $receipt_status ) {
				echo '<span class="c2c-badge c2c-badge-success">✓ تایید شده</span>';
			} elseif ( 'rejected' === $receipt_status ) {
				echo '<span class="c2c-badge c2c-badge-danger">✕ رد شده</span>';
			} else {
				echo '<span class="c2c-badge c2c-badge-primary">📄 ارسال شده</span>';
			}
		} else {
			echo '<span class="c2c-badge c2c-badge-muted">منتظر فیش</span>';
		}
	}

	/**
	 * AJAX: Approve Receipt
	 */
	public static function handle_admin_approve() {
		check_ajax_referer( 'woo_c2c_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'دسترسی غیرمجاز.', 'woo-card-to-card-pro' ) ), 403 );
		}

		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'سفارش یافت نشد.', 'woo-card-to-card-pro' ) ), 404 );
		}
		
		$status = $order->get_meta( '_c2c_receipt_status' );
		if ( 'approved' === $status || in_array( $order->get_status(), array( 'processing', 'completed' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'این فیش یا سفارش قبلاً تایید شده است.', 'woo-card-to-card-pro' ) ), 400 );
		}

		// Get target status from gateway options (defaults to processing)
		$gateways = WC()->payment_gateways->payment_gateways();
		$target_status = 'processing';
		if ( isset( $gateways[ WOO_C2C_GATEWAY_ID ] ) ) {
			$target_status = $gateways[ WOO_C2C_GATEWAY_ID ]->get_option( 'approval_target_status', 'processing' );
		}

		$order->update_meta_data( '_c2c_receipt_status', 'approved' );
		$order->set_status( $target_status, __( 'فیش کارت‌به‌کارت توسط مدیر تایید شد و وجه دریافت گردید.', 'woo-card-to-card-pro' ) );
		$order->add_order_note( sprintf( __( 'مدیر (%s) فیش واریزی را تایید کرد.', 'woo-card-to-card-pro' ), wp_get_current_user()->display_name ), false, true );
		$order->save();

		wp_send_json_success( array( 'message' => __( 'فیش با موفقیت تایید و وضعیت سفارش تغییر کرد.', 'woo-card-to-card-pro' ) ) );
	}

	/**
	 * AJAX: Reject Receipt
	 */
	public static function handle_admin_reject() {
		check_ajax_referer( 'woo_c2c_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'دسترسی غیرمجاز.', 'woo-card-to-card-pro' ) ), 403 );
		}

		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		$reason   = isset( $_POST['reason'] ) ? sanitize_text_field( $_POST['reason'] ) : __( 'فیش نامعتبر است', 'woo-card-to-card-pro' );

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'سفارش یافت نشد.', 'woo-card-to-card-pro' ) ), 404 );
		}
		
		$status = $order->get_meta( '_c2c_receipt_status' );
		if ( 'approved' === $status || in_array( $order->get_status(), array( 'processing', 'completed' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'این فیش یا سفارش قبلاً تایید شده و امکان رد آن وجود ندارد.', 'woo-card-to-card-pro' ) ), 400 );
		}

		$order->update_meta_data( '_c2c_receipt_status', 'rejected' );
		$order->update_meta_data( '_c2c_rejection_reason', $reason );
		$order->set_status( 'on-hold', sprintf( __( 'فیش کارت‌به‌کارت توسط مدیر رد شد. علت: %s', 'woo-card-to-card-pro' ), $reason ) );
		
		$customer_note = sprintf( __( 'فیش واریزی کارت‌به‌کارت شما رد شد. علت: %s. لطفاً با پشتیبانی در تماس باشید.', 'woo-card-to-card-pro' ), $reason );
		$order->add_order_note( $customer_note, true, true );
		$order->save();

		wp_send_json_success( array( 'message' => __( 'فیش رد شد و یادداشت به مشتری اطلاع داده شد.', 'woo-card-to-card-pro' ) ) );
	}
}
