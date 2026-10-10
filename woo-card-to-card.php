<?php
/**
 * Plugin Name: WooCommerce Card to Card Payment Gateway
 * Plugin URI: https://github.com/Rmaawn/woo-card-to-card
 * Description: راه‌حل حرفه‌ای و امن پرداخت کارت به کارت برای ووکامرس با قابلیت مدیریت چند کارت، آپلود فیش، سیستم هوشمند ضد تقلب و فیش جعلی، و پنل بررسی سریع ادمین.
 * Version: 1.0.0
 * Author: آرمان محبعلی
 * Author URI: https://armanmohebali.ir
 * Text Domain: woo-card-to-card
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 8.9
 *
 * @package Woo_Card_To_Card
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define Plugin Constants
define( 'WOO_C2C_VERSION', '1.0.0' );
define( 'WOO_C2C_PLUGIN_FILE', __FILE__ );
define( 'WOO_C2C_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WOO_C2C_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WOO_C2C_GATEWAY_ID', 'c2c_gateway' );

/**
 * Declare HPOS (High-Performance Order Storage) Compatibility
 */
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

/**
 * Main Plugin Bootstrap Class
 */
final class WOO_Card_To_Card {

	/**
	 * Singleton instance
	 *
	 * @var WOO_Card_To_Card|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return WOO_Card_To_Card
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required files
	 */
	private function includes() {
		require_once WOO_C2C_PLUGIN_DIR . 'includes/class-c2c-order-status.php';
		require_once WOO_C2C_PLUGIN_DIR . 'includes/class-c2c-card-manager.php';
		require_once WOO_C2C_PLUGIN_DIR . 'includes/class-c2c-receipt-handler.php';
		require_once WOO_C2C_PLUGIN_DIR . 'includes/class-c2c-admin.php';
		require_once WOO_C2C_PLUGIN_DIR . 'includes/class-c2c-notifications.php';
		require_once WOO_C2C_PLUGIN_DIR . 'includes/class-c2c-cron.php';
		require_once WOO_C2C_PLUGIN_DIR . 'includes/class-c2c-receipt-viewer.php';
	}

	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks() {
		// Check if WooCommerce is active
		add_action( 'plugins_loaded', array( $this, 'init_gateway' ) );

		// Register custom order statuses
		add_action( 'init', array( 'WOO_C2C_Order_Status', 'register_status' ) );
		add_filter( 'wc_order_statuses', array( 'WOO_C2C_Order_Status', 'add_to_order_statuses' ) );

		// Enqueue frontend scripts & styles
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

		// Enqueue admin scripts & styles
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Init admin components
		if ( is_admin() ) {
			WOO_C2C_Admin::init();
		}

		// Init receipt AJAX handler
		WOO_C2C_Receipt_Handler::init();
		
		// Init cron and receipt viewer
		WOO_C2C_Cron::init();
		WOO_C2C_Receipt_Viewer::init();
	}

	/**
	 * Initialize WooCommerce Payment Gateway
	 */
	public function init_gateway() {
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return;
		}

		require_once WOO_C2C_PLUGIN_DIR . 'includes/class-c2c-gateway.php';
		add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );
	}

	/**
	 * Add Gateway to WooCommerce
	 *
	 * @param array $gateways
	 * @return array
	 */
	public function add_gateway( $gateways ) {
		$gateways[] = 'WOO_Gateway_C2C';
		return $gateways;
	}

	/**
	 * Notice if WooCommerce is not installed or active
	 */
	public function woocommerce_missing_notice() {
		?>
		<div class="error">
			<p><?php esc_html_e( 'افزونه woo-card-to-card برای فعالیت نیاز به نصب و فعال‌سازی ووکامرس دارد.', 'woo-card-to-card' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Enqueue Frontend Assets (CSS / JS)
	 */
	public function enqueue_frontend_assets() {
		// Enqueue only on checkout, order received (thank you), or view order pages
		if ( is_checkout() || is_wc_endpoint_url( 'order-received' ) || is_wc_endpoint_url( 'view-order' ) ) {
			wp_enqueue_style(
				'woo-c2c-frontend',
				WOO_C2C_PLUGIN_URL . 'assets/css/c2c-frontend.css',
				array(),
				WOO_C2C_VERSION
			);

			wp_enqueue_script(
				'woo-c2c-frontend',
				WOO_C2C_PLUGIN_URL . 'assets/js/c2c-frontend.js',
				array( 'jquery' ),
				WOO_C2C_VERSION,
				true
			);

			wp_localize_script( 'woo-c2c-frontend', 'wooC2cParams', array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'woo_c2c_receipt_nonce' ),
				'i18n'      => array(
					'copied'            => __( 'کپی شد!', 'woo-card-to-card' ),
					'uploading'         => __( 'در حال ارسال فیش...', 'woo-card-to-card' ),
					'selectImage'       => __( 'لطفاً تصویر فیش واریزی را انتخاب کنید.', 'woo-card-to-card' ),
					'invalidFileType'   => __( 'فرمت فایل مجاز نیست (فقط JPG, PNG, WEBP).', 'woo-card-to-card' ),
					'fileTooLarge'      => __( 'حجم فایل بیشتر از حد مجاز است.', 'woo-card-to-card' ),
					'success'           => __( 'فیش واریزی شما با موفقیت ثبت شد و در صف بررسی قرار گرفت.', 'woo-card-to-card' ),
					'genericError'      => __( 'خطایی در ارسال فیش رخ داد. لطفاً مجدداً تلاش کنید.', 'woo-card-to-card' ),
				)
			) );
		}
	}

	/**
	 * Enqueue Admin Assets
	 *
	 * @param string $hook
	 */
	public function enqueue_admin_assets( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		// Check if we are on WooCommerce order edit or order list screens
		$is_order_page = in_array( $screen->id, array(
			'shop_order',
			'woocommerce_page_wc-orders',
			'edit-shop_order',
			'dashboard'
		), true );

		if ( $is_order_page ) {
			wp_enqueue_style(
				'woo-c2c-admin',
				WOO_C2C_PLUGIN_URL . 'assets/css/c2c-admin.css',
				array(),
				WOO_C2C_VERSION
			);

			wp_enqueue_script(
				'woo-c2c-admin',
				WOO_C2C_PLUGIN_URL . 'assets/js/c2c-admin.js',
				array( 'jquery' ),
				WOO_C2C_VERSION,
				true
			);

			wp_localize_script( 'woo-c2c-admin', 'wooC2cAdminParams', array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'woo_c2c_admin_nonce' ),
				'i18n'    => array(
					'confirmApprove'   => __( 'آیا از تایید این فیش و تغییر وضعیت سفارش به «در حال انجام» مطمئن هستید؟', 'woo-card-to-card' ),
					'confirmReject'    => __( 'علت رد فیش را وارد کنید:', 'woo-card-to-card' ),
					'defaultRejectMsg' => __( 'فیش واریزی نامعتبر است یا وجه به حساب ننشسته است.', 'woo-card-to-card' ),
					'processing'       => __( 'در حال پردازش...', 'woo-card-to-card' ),
					'done'             => __( 'عملیات با موفقیت انجام شد.', 'woo-card-to-card' ),
				)
			) );
		}
	}
}

// Activation: schedule cron and flush rewrite rules for receipt viewer endpoint.
register_activation_hook( WOO_C2C_PLUGIN_FILE, function () {
	require_once plugin_dir_path( WOO_C2C_PLUGIN_FILE ) . 'includes/class-c2c-cron.php';
	require_once plugin_dir_path( WOO_C2C_PLUGIN_FILE ) . 'includes/class-c2c-receipt-viewer.php';
	WOO_C2C_Cron::schedule_events();
	WOO_C2C_Receipt_Viewer::add_endpoint();
	flush_rewrite_rules();
} );

// Deactivation: unschedule cron and flush rewrite rules.
register_deactivation_hook( WOO_C2C_PLUGIN_FILE, function () {
	require_once plugin_dir_path( WOO_C2C_PLUGIN_FILE ) . 'includes/class-c2c-cron.php';
	WOO_C2C_Cron::unschedule_events();
	flush_rewrite_rules();
} );

// Bootstrap the plugin.
WOO_Card_To_Card::get_instance();
