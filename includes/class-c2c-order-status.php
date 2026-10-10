<?php
/**
 * Custom Order Status: Awaiting Receipt
 *
 * @package Woo_Card_To_Card
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WOO_C2C_Order_Status {

	const STATUS_SLUG = 'awaiting-receipt';
	const STATUS_FULL = 'wc-awaiting-receipt';

	/**
	 * Register post status in WordPress
	 */
	public static function register_status() {
		register_post_status( self::STATUS_FULL, array(
			'label'                     => _x( 'در انتظار بررسی فیش', 'Order status', 'woo-card-to-card' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'در انتظار فیش <span class="count">(%s)</span>', 'در انتظار فیش <span class="count">(%s)</span>', 'woo-card-to-card' )
		) );
		
		// Add email trigger registration for new status
		add_filter( 'woocommerce_email_actions', array( __CLASS__, 'add_email_actions' ) );
	}

	/**
	 * Add to WooCommerce order statuses list
	 *
	 * @param array $order_statuses
	 * @return array
	 */
	public static function add_to_order_statuses( $order_statuses ) {
		$new_order_statuses = array();

		foreach ( $order_statuses as $key => $status ) {
			$new_order_statuses[ $key ] = $status;
			if ( 'wc-on-hold' === $key ) {
				$new_order_statuses[ self::STATUS_FULL ] = _x( 'در انتظار بررسی فیش', 'Order status', 'woo-card-to-card' );
			}
		}

		if ( ! isset( $new_order_statuses[ self::STATUS_FULL ] ) ) {
			$new_order_statuses[ self::STATUS_FULL ] = _x( 'در انتظار بررسی فیش', 'Order status', 'woo-card-to-card' );
		}

		return $new_order_statuses;
	}

	/**
	 * Get the full status name
	 *
	 * @return string
	 */
	public static function get_status_slug() {
		return self::STATUS_SLUG;
	}
	
	/**
	 * Add email actions for custom status
	 * 
	 * @param array $actions
	 * @return array
	 */
	public static function add_email_actions( $actions ) {
		$actions[] = 'woocommerce_order_status_' . self::STATUS_SLUG;
		return $actions;
	}
}
