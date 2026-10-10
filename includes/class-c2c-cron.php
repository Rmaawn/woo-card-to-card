<?php
/**
 * Cron Job Handler
 *
 * @package Woo_Card_To_Card
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WOO_C2C_Cron {

	/**
	 * Init hooks
	 */
	public static function init() {
		add_action( 'woo_c2c_check_expired_orders', array( __CLASS__, 'cancel_expired_orders' ) );
	}

	/**
	 * Schedule cron
	 */
	public static function schedule_events() {
		if ( ! wp_next_scheduled( 'woo_c2c_check_expired_orders' ) ) {
			wp_schedule_event( time(), 'hourly', 'woo_c2c_check_expired_orders' );
		}
	}

	/**
	 * Unschedule cron
	 */
	public static function unschedule_events() {
		$timestamp = wp_next_scheduled( 'woo_c2c_check_expired_orders' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'woo_c2c_check_expired_orders' );
		}
	}

	/**
	 * Cancel expired orders
	 */
	public static function cancel_expired_orders() {
		$orders = wc_get_orders( array(
			'status'       => 'wc-awaiting-receipt',
			'limit'        => 50,
			'meta_key'     => '_c2c_payment_deadline',
			'meta_compare' => '<',
			'meta_value'   => current_time( 'timestamp' ),
		) );

		if ( empty( $orders ) ) {
			return;
		}

		foreach ( $orders as $order ) {
			if ( 'approved' !== $order->get_meta( '_c2c_receipt_status' ) ) {
				$order->update_status( 'cancelled', __( 'سفارش به دلیل عدم پرداخت در زمان مقرر لغو شد.', 'woo-card-to-card' ) );
			}
		}
	}
}
