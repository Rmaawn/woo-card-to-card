<?php
/**
 * Plugin Uninstall
 *
 * Uninstalling the plugin deletes options, tables, and pages.
 *
 * @package Woo_Card_To_Card_Pro
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Only delete data if the user explicitly enables it via filter.
 * Default is false to prevent accidental data loss.
 */
if ( ! apply_filters( 'woo_c2c_delete_data_on_uninstall', false ) ) {
	return;
}

global $wpdb;

// 1. Delete Gateway Options
delete_option( 'woocommerce_c2c_gateway_settings' );

// 2. Delete all Order Meta starting with _c2c_
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '\_c2c\_%'" );

// For HPOS (WooCommerce 8.0+)
if ( class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore' ) ) {
	// Although WooCommerce handles meta in custom tables, we need to try and clean it if HPOS is active.
	$meta_table = $wpdb->prefix . 'wc_orders_meta';
	if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $meta_table ) ) === $meta_table ) {
		$wpdb->query( "DELETE FROM {$meta_table} WHERE meta_key LIKE '\_c2c\_%'" );
	}
}

// 3. Remove custom order status from any orders (revert to 'on-hold' or 'pending')
// Assuming the custom status is 'wc-awaiting-receipt' based on typical implementations
$custom_status = 'wc-awaiting-receipt';
$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_status = 'wc-on-hold' WHERE post_type = 'shop_order' AND post_status = %s", $custom_status ) );

if ( class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore' ) ) {
	$orders_table = $wpdb->prefix . 'wc_orders';
	if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $orders_table ) ) === $orders_table ) {
		$wpdb->query( $wpdb->prepare( "UPDATE {$orders_table} SET status = 'wc-on-hold' WHERE status = %s", $custom_status ) );
	}
}

// 4. Delete Uploaded Receipts Directory
$upload_dir = wp_upload_dir();
$receipts_dir = $upload_dir['basedir'] . '/c2c-receipts/';

if ( is_dir( $receipts_dir ) ) {
	// Recursive directory deletion
	$files = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $receipts_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ( $files as $fileinfo ) {
		$todo = ( $fileinfo->isDir() ? 'rmdir' : 'unlink' );
		$todo( $fileinfo->getRealPath() );
	}

	rmdir( $receipts_dir );
}

// 5. Clean up any scheduled cron events
wp_clear_scheduled_hook( 'woo_c2c_check_expired_orders' );

// Clear cache
wp_cache_flush();
