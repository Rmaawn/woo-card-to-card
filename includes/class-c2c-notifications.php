<?php
/**
 * Email Notifications
 *
 * @package Woo_Card_To_Card_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WOO_C2C_Notifications {

	/**
	 * Send admin email on receipt upload.
	 *
	 * @param int $order_id Order ID.
	 */
	public static function send_admin_notification( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$to      = get_option( 'admin_email' );
		$subject = sprintf( __( 'فیش جدید برای سفارش #%d آپلود شد', 'woo-card-to-card-pro' ), $order_id );
		
		$order_url = admin_url( 'post.php?post=' . $order_id . '&action=edit' );
		if ( class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' ) && wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled() ) {
			$order_url = admin_url( 'admin.php?page=wc-orders&action=edit&id=' . $order_id );
		}
		
		$message = sprintf( __( 'سلام،%1$sیک فیش کارت به کارت جدید برای سفارش #%2$d آپلود شده است.%1$sبرای بررسی به لینک زیر مراجعه کنید:%1$s%3$s', 'woo-card-to-card-pro' ), "\n\n", $order_id, esc_url( $order_url ) );

		wp_mail( $to, $subject, $message );
	}
}
