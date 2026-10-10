<?php
/**
 * Receipt Handler & Anti-Fraud Engine
 *
 * Handles file uploads, security validation, and duplicate receipt / tracking code detection.
 *
 * @package Woo_Card_To_Card
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WOO_C2C_Receipt_Handler {

	const UPLOAD_DIR_NAME = 'c2c-receipts';
	const MAX_FILE_SIZE = 6291456; // 6MB
	const MAX_UPLOADS = 3;

	/**
	 * Initialize handler
	 */
	public static function init() {
		add_action( 'wp_ajax_woo_c2c_upload_receipt', array( __CLASS__, 'handle_upload_ajax' ) );
		add_action( 'wp_ajax_nopriv_woo_c2c_upload_receipt', array( __CLASS__, 'handle_upload_ajax' ) );
	}

	/**
	 * Get or create secure uploads directory
	 *
	 * @return array array('path' => string, 'url' => string)
	 */
	public static function get_upload_dir() {
		$upload_dir = wp_upload_dir();
		// Randomize subdir per month
		$month_dir = gmdate( 'Y-m' );
		$target_path = trailingslashit( $upload_dir['basedir'] ) . self::UPLOAD_DIR_NAME . '/' . $month_dir;
		$target_url  = trailingslashit( $upload_dir['baseurl'] ) . self::UPLOAD_DIR_NAME . '/' . $month_dir;

		if ( ! file_exists( $target_path ) ) {
			wp_mkdir_p( $target_path );

			// Add index.php to prevent directory listing
			@file_put_contents( trailingslashit( $upload_dir['basedir'] ) . self::UPLOAD_DIR_NAME . '/index.php', '<?php // Silence is golden' );
			@file_put_contents( $target_path . '/index.php', '<?php // Silence is golden' );

			// Add .htaccess to prevent script execution
			$htaccess_content  = "# Protect receipt uploads\n";
			$htaccess_content .= "<FilesMatch \"\\.(php|phtml|php3|php4|php5|php7|phps|cgi|pl|exe)$\">\n";
			$htaccess_content .= "Order Deny,Allow\nDeny from all\n";
			$htaccess_content .= "</FilesMatch>\n";
			$htaccess_content .= "Options -Indexes\n";
			@file_put_contents( trailingslashit( $upload_dir['basedir'] ) . self::UPLOAD_DIR_NAME . '/.htaccess', $htaccess_content );
		}

		return array(
			'path' => $target_path,
			'url'  => $target_url,
		);
	}

	/**
	 * AJAX handler for customer receipt submission
	 */
	public static function handle_upload_ajax() {
		// 1. Check Nonce
		check_ajax_referer( 'woo_c2c_receipt_nonce', 'nonce' );

		// 2. Validate Order ID & Key
		$order_id  = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		$order_key = isset( $_POST['order_key'] ) ? sanitize_text_field( $_POST['order_key'] ) : '';

		if ( ! $order_id || empty( $order_key ) ) {
			wp_send_json_error( array( 'message' => __( 'اطلاعات سفارش نامعتبر است.', 'woo-card-to-card' ) ), 400 );
		}
		
		// Rate limiting per IP
		$ip = WC_Geolocation::get_ip_address();
		$transient_key = 'c2c_upload_limit_' . md5( $ip );
		$attempts = (int) get_transient( $transient_key );
		if ( $attempts >= 5 ) {
			wp_send_json_error( array( 'message' => __( 'تعداد درخواست‌های شما بیش از حد مجاز است. لطفاً بعداً تلاش کنید.', 'woo-card-to-card' ) ), 429 );
		}
		set_transient( $transient_key, $attempts + 1, HOUR_IN_SECONDS );

		// Race condition lock
		$lock_key = 'c2c_upload_lock_' . $order_id;
		if ( get_transient( $lock_key ) ) {
			wp_send_json_error( array( 'message' => __( 'درخواست شما در حال پردازش است.', 'woo-card-to-card' ) ), 409 );
		}
		set_transient( $lock_key, 1, 30 );

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			delete_transient( $lock_key );
			wp_send_json_error( array( 'message' => __( 'سفارش مورد نظر یافت نشد.', 'woo-card-to-card' ) ), 404 );
		}

		// Verify order key match
		if ( ! hash_equals( $order->get_order_key(), $order_key ) ) {
			delete_transient( $lock_key );
			wp_send_json_error( array( 'message' => __( 'دسترسی غیرمجاز به این سفارش.', 'woo-card-to-card' ) ), 403 );
		}
		
		// Ensure current user is owner or it's a guest matching key
		if ( is_user_logged_in() && $order->get_customer_id() > 0 && $order->get_customer_id() !== get_current_user_id() ) {
			delete_transient( $lock_key );
			wp_send_json_error( array( 'message' => __( 'دسترسی غیرمجاز.', 'woo-card-to-card' ) ), 403 );
		}

		// Check if already approved
		$receipt_status = $order->get_meta( '_c2c_receipt_status' );
		if ( 'approved' === $receipt_status || in_array( $order->get_status(), array( 'processing', 'completed' ), true ) ) {
			delete_transient( $lock_key );
			wp_send_json_error( array( 'message' => __( 'فیش قبلاً تایید شده است.', 'woo-card-to-card' ) ), 400 );
		}

		// Check attempts limit
		$upload_attempts = (int) $order->get_meta( '_c2c_upload_attempts' );
		if ( $upload_attempts >= self::MAX_UPLOADS ) {
			delete_transient( $lock_key );
			wp_send_json_error( array( 'message' => __( 'حداکثر تعداد مجاز برای آپلود فیش به پایان رسیده است.', 'woo-card-to-card' ) ), 400 );
		}

		// 3. Validate Uploaded File
		if ( empty( $_FILES['receipt_file'] ) || ! is_uploaded_file( $_FILES['receipt_file']['tmp_name'] ) ) {
			delete_transient( $lock_key );
			wp_send_json_error( array( 'message' => __( 'لطفاً تصویر فیش واریزی را بارگذاری نمایید.', 'woo-card-to-card' ) ), 400 );
		}

		$file = $_FILES['receipt_file'];

		// Size check
		if ( $file['size'] > self::MAX_FILE_SIZE ) {
			delete_transient( $lock_key );
			wp_send_json_error( array( 'message' => __( 'حجم تصویر بیش از حد مجاز است (حداکثر ۶ مگابایت).', 'woo-card-to-card' ) ), 400 );
		}

		// Prevent path traversal in filenames
		$file_name = sanitize_file_name( basename( $file['name'] ) );

		// MIME & Extension check
		$allowed_mimes = array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png'          => 'image/png',
			'webp'         => 'image/webp'
		);

		// Use 3-parameter wp_check_filetype_and_ext
		$file_info = wp_check_filetype_and_ext( $file['tmp_name'], $file_name, $allowed_mimes );
		if ( ! $file_info['ext'] || ! in_array( $file_info['type'], $allowed_mimes, true ) ) {
			delete_transient( $lock_key );
			wp_send_json_error( array( 'message' => __( 'فرمت فایل مجاز نیست. فقط تصاویر JPG, PNG یا WEBP پذیرفته می‌شوند.', 'woo-card-to-card' ) ), 400 );
		}

		// finfo secondary validation
		if ( function_exists( 'finfo_file' ) ) {
			$finfo = finfo_open( FILEINFO_MIME_TYPE );
			$mime = finfo_file( $finfo, $file['tmp_name'] );
			finfo_close( $finfo );
			if ( ! in_array( $mime, $allowed_mimes, true ) ) {
				delete_transient( $lock_key );
				wp_send_json_error( array( 'message' => __( 'محتوای فایل نامعتبر است.', 'woo-card-to-card' ) ), 400 );
			}
		}

		// Image integrity check
		$img_size = @getimagesize( $file['tmp_name'] );
		if ( $img_size === false ) {
			delete_transient( $lock_key );
			wp_send_json_error( array( 'message' => __( 'فایل ارسالی یک تصویر معتبر نیست.', 'woo-card-to-card' ) ), 400 );
		}

		// 4. Calculate File Hash for Anti-Fraud Detection
		$file_hash = md5_file( $file['tmp_name'] );
		$duplicate_order_ids = self::find_duplicate_hash_orders( $file_hash, $order_id );

		// 5. Secure File Move
		$dir_info = self::get_upload_dir();
		$random_token = wp_generate_password( 32, false, false );
		$new_filename = sprintf( 'receipt_order_%d_%s.%s', $order_id, wp_generate_password( 10, false, false ), $file_info['ext'] );
		$destination_path = trailingslashit( $dir_info['path'] ) . $new_filename;
		
		if ( ! move_uploaded_file( $file['tmp_name'], $destination_path ) ) {
			delete_transient( $lock_key );
			wp_send_json_error( array( 'message' => __( 'ذخیره‌سازی فایل با خطا مواجه شد. لطفاً دوباره تلاش کنید.', 'woo-card-to-card' ) ), 500 );
		}

		$token_url = home_url( '/c2c-receipt/' . $random_token . '/' );

		// 6. Process Form Fields
		$tracking_code = isset( $_POST['tracking_code'] ) ? sanitize_text_field( $_POST['tracking_code'] ) : '';
		$sender_card   = isset( $_POST['sender_card'] ) ? sanitize_text_field( $_POST['sender_card'] ) : '';
		$deposit_date  = isset( $_POST['deposit_date'] ) ? sanitize_text_field( $_POST['deposit_date'] ) : current_time( 'mysql' );

		$duplicate_tracking_orders = array();
		if ( ! empty( $tracking_code ) ) {
			$duplicate_tracking_orders = self::find_duplicate_tracking_orders( $tracking_code, $order_id );
		}

		// 7. Save Order Meta
		$order->update_meta_data( '_c2c_receipt_token', $random_token );
		$order->update_meta_data( '_c2c_receipt_file_url', esc_url_raw( $token_url ) );
		$order->update_meta_data( '_c2c_receipt_file_path', sanitize_text_field( $destination_path ) );
		$order->update_meta_data( '_c2c_receipt_hash', sanitize_text_field( $file_hash ) );
		$order->update_meta_data( '_c2c_tracking_code', sanitize_text_field( $tracking_code ) );
		$order->update_meta_data( '_c2c_sender_card', sanitize_text_field( $sender_card ) );
		$order->update_meta_data( '_c2c_deposit_date', sanitize_text_field( $deposit_date ) );
		$order->update_meta_data( '_c2c_receipt_status', 'submitted' );
		$order->update_meta_data( '_c2c_submitted_time', current_time( 'timestamp' ) );
		$order->update_meta_data( '_c2c_upload_attempts', $upload_attempts + 1 );

		if ( ! empty( $duplicate_order_ids ) ) {
			$order->update_meta_data( '_c2c_duplicate_hash_orders', $duplicate_order_ids );
		} else {
			$order->delete_meta_data( '_c2c_duplicate_hash_orders' );
		}

		if ( ! empty( $duplicate_tracking_orders ) ) {
			$order->update_meta_data( '_c2c_duplicate_tracking_orders', $duplicate_tracking_orders );
		} else {
			$order->delete_meta_data( '_c2c_duplicate_tracking_orders' );
		}

		// 8. Update Order Status
		$order->set_status( 'wc-awaiting-receipt', __( 'مشتری فیش واریزی را بارگذاری کرد.', 'woo-card-to-card' ) );

		$note_content = sprintf(
			__( "📄 فیش کارت به کارت ثبت شد:\n• کد پیگیری: %s\n• ۴ رقم آخر کارت: %s\n• تاریخ واریز: %s", 'woo-card-to-card' ),
			! empty( $tracking_code ) ? $tracking_code : '---',
			! empty( $sender_card ) ? $sender_card : '---',
			$deposit_date
		);

		if ( ! empty( $duplicate_order_ids ) ) {
			$note_content .= sprintf( "\n⚠️ هشدار تقلب: این تصویر فیش دقیقاً در سفارش(های) #%s استفاده شده است!", implode( ', #', $duplicate_order_ids ) );
		}

		if ( ! empty( $duplicate_tracking_orders ) ) {
			$note_content .= sprintf( "\n⚠️ هشدار تقلب: این کد پیگیری قبلاً در سفارش(های) #%s ثبت شده است!", implode( ', #', $duplicate_tracking_orders ) );
		}

		$order->add_order_note( $note_content, false, true );
		$order->save();
		
		// Send notification
		if ( class_exists( 'WOO_C2C_Notifications' ) ) {
			WOO_C2C_Notifications::send_admin_notification( $order_id );
		}

		delete_transient( $lock_key );

		wp_send_json_success( array(
			'message'     => __( 'فیش واریزی شما با موفقیت ثبت شد و در صف بررسی مدیر قرار گرفت.', 'woo-card-to-card' ),
			'receipt_url' => $token_url,
			'tracking'    => $tracking_code
		) );
	}

	/**
	 * Find orders sharing the exact same receipt image hash
	 *
	 * @param string $hash
	 * @param int $current_order_id
	 * @return array
	 */
	public static function find_duplicate_hash_orders( $hash, $current_order_id ) {
		if ( empty( $hash ) ) {
			return array();
		}

		$orders = wc_get_orders( array(
			'limit'        => 5,
			'exclude'      => array( $current_order_id ),
			'meta_key'     => '_c2c_receipt_hash',
			'meta_value'   => $hash,
			'meta_compare' => '=',
			'return'       => 'ids'
		) );

		return ! empty( $orders ) ? $orders : array();
	}

	/**
	 * Find orders sharing the exact same tracking code
	 *
	 * @param string $tracking_code
	 * @param int $current_order_id
	 * @return array
	 */
	public static function find_duplicate_tracking_orders( $tracking_code, $current_order_id ) {
		if ( empty( $tracking_code ) ) {
			return array();
		}

		$orders = wc_get_orders( array(
			'limit'        => 5,
			'exclude'      => array( $current_order_id ),
			'meta_key'     => '_c2c_tracking_code',
			'meta_value'   => $tracking_code,
			'meta_compare' => '=',
			'return'       => 'ids'
		) );

		return ! empty( $orders ) ? $orders : array();
	}
}
