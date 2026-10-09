<?php
/**
 * Secure Receipt Viewer Endpoint
 *
 * @package Woo_Card_To_Card_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WOO_C2C_Receipt_Viewer {

	/**
	 * Init endpoint
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'add_endpoint' ) );
		add_action( 'template_redirect', array( __CLASS__, 'handle_request' ) );
	}

	/**
	 * Add rewrite endpoint
	 */
	public static function add_endpoint() {
		add_rewrite_endpoint( 'c2c-receipt', EP_ROOT );
	}

	/**
	 * Handle request
	 */
	public static function handle_request() {
		global $wp_query;

		if ( ! isset( $wp_query->query_vars['c2c-receipt'] ) ) {
			return;
		}

		$token = sanitize_text_field( $wp_query->query_vars['c2c-receipt'] );
		if ( empty( $token ) ) {
			wp_die( esc_html__( 'توکن نامعتبر است.', 'woo-card-to-card-pro' ), 403 );
		}

		// Find order by token
		$orders = wc_get_orders( array(
			'limit'      => 1,
			'meta_key'   => '_c2c_receipt_token',
			'meta_value' => $token,
		) );

		if ( empty( $orders ) ) {
			wp_die( esc_html__( 'فیش یافت نشد.', 'woo-card-to-card-pro' ), 404 );
		}

		$order = $orders[0];

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			if ( ! is_user_logged_in() || get_current_user_id() !== $order->get_customer_id() ) {
				// Allow access if token matches and they are on the frontend with correct order key. 
				// However, standard is they must be logged in as customer or admin.
				wp_die( esc_html__( 'دسترسی غیرمجاز.', 'woo-card-to-card-pro' ), 403 );
			}
		}

		$file_path = $order->get_meta( '_c2c_receipt_file_path' );
		if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
			wp_die( esc_html__( 'فایل فیش یافت نشد.', 'woo-card-to-card-pro' ), 404 );
		}

		$mime_type = wp_check_filetype( $file_path );
		if ( empty( $mime_type['type'] ) ) {
			$mime_type['type'] = 'application/octet-stream';
		}
		header( 'Content-Type: ' . $mime_type['type'] );
		header( 'Content-Length: ' . filesize( $file_path ) );
		readfile( $file_path );
		exit;
	}
}
