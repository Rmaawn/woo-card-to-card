<?php
/**
 * WooCommerce Card to Card Payment Gateway Class
 *
 * @package Woo_Card_To_Card_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WOO_Gateway_C2C extends WC_Payment_Gateway {

	/**
	 * Parsed list of configured bank cards
	 *
	 * @var array
	 */
	public $cards = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id                 = WOO_C2C_GATEWAY_ID;
		$this->icon               = apply_filters( 'woocommerce_c2c_icon', WOO_C2C_PLUGIN_URL . 'assets/images/c2c-icon.svg' );
		$this->has_fields         = false;
		$this->method_title       = __( 'کارت به کارت حرفه‌ای (Pro)', 'woo-card-to-card-pro' );
		$this->method_description = __( 'درگاه پرداخت کارت به کارت با امکان تعریف چند کارت بانکی، آپلود فیش توسط مشتری و تطبیق هوشمند ضد فیش جعلی.', 'woo-card-to-card-pro' );
		$this->supports           = array( 'products' );

		// Load settings fields
		$this->init_form_fields();
		$this->init_settings();

		// Gateway properties from settings
		$this->title               = $this->get_option( 'title', __( 'پرداخت کارت به کارت', 'woo-card-to-card-pro' ) );
		$this->description         = $this->get_option( 'description', __( 'مبلغ سفارش را به یکی از شماره کارت‌های زیر واریز کرده و تصویر فیش را در مرحله بعد ثبت فرمایید.', 'woo-card-to-card-pro' ) );
		$this->instructions        = $this->get_option( 'instructions' );
		$this->cards               = WOO_C2C_Card_Manager::parse_cards( $this->get_option( 'cards_config' ) );

		// Save admin settings
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		// Customer receipt form on Thank You and View Order pages
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'render_receipt_and_card_section' ), 10 );
		add_action( 'woocommerce_view_order', array( $this, 'render_view_order_receipt_section' ), 10 );
	}

	/**
	 * Form fields in WooCommerce settings
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'فعال‌سازی درگاه', 'woo-card-to-card-pro' ),
				'type'    => 'checkbox',
				'label'   => __( 'فعال‌سازی پرداخت کارت به کارت', 'woo-card-to-card-pro' ),
				'default' => 'yes',
			),
			'title' => array(
				'title'       => __( 'عنوان درگاه در تسویه حساب', 'woo-card-to-card-pro' ),
				'type'        => 'text',
				'description' => __( 'عنوانی که مشتری در صفحه تسویه حساب مشاهده می‌کند.', 'woo-card-to-card-pro' ),
				'default'     => __( 'کارت به کارت (شتاب)', 'woo-card-to-card-pro' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'توضیحات درگاه در تسویه حساب', 'woo-card-to-card-pro' ),
				'type'        => 'textarea',
				'description' => __( 'توضیحاتی که پس از انتخاب این درگاه به مشتری نمایش داده می‌شود.', 'woo-card-to-card-pro' ),
				'default'     => __( 'مبلغ سفارش را کارت به کارت نموده و فیش واریز را بارگذاری نمایید.', 'woo-card-to-card-pro' ),
			),
			'cards_config' => array(
				'title'       => __( 'لیست کارت‌های بانکی', 'woo-card-to-card-pro' ),
				'type'        => 'textarea',
				'description' => __( 'هر کارت را در یک خط با فرمت روبرو وارد کنید: <br><code>شماره کارت | نام صاحب حساب | نام بانک | شماره شبا</code><br>مثال:<br><code>6037997512345678 | علی محمدی | بانک ملی | IR120170000000012345678901</code>', 'woo-card-to-card-pro' ),
				'default'     => "6037997512345678 | نام صاحب حساب | بانک ملی ایران | IR120170000000012345678901",
				'css'         => 'direction: ltr; text-align: left; min-height: 120px;',
			),
			'require_tracking' => array(
				'title'   => __( 'فیلد شماره پیگیری', 'woo-card-to-card-pro' ),
				'type'    => 'checkbox',
				'label'   => __( 'درج شماره ارجاع / پیگیری توسط مشتری اجباری باشد', 'woo-card-to-card-pro' ),
				'default' => 'yes',
			),
			'require_sender_card' => array(
				'title'   => __( 'فیلد ۴ رقم کارت مبدا', 'woo-card-to-card-pro' ),
				'type'    => 'checkbox',
				'label'   => __( 'درج ۴ رقم آخر کارت پرداخت‌کننده اجباری باشد', 'woo-card-to-card-pro' ),
				'default' => 'no',
			),
			'approval_target_status' => array(
				'title'       => __( 'وضعیت سفارش پس از تایید مدیر', 'woo-card-to-card-pro' ),
				'type'        => 'select',
				'description' => __( 'هنگامی که دکمه «تایید واریز» را در سفارش می‌زنید، وضعیت سفارش به این حالت تغییر می‌کند.', 'woo-card-to-card-pro' ),
				'default'     => 'processing',
				'options'     => array(
					'processing' => __( 'در حال انجام (Processing)', 'woo-card-to-card-pro' ),
					'completed'  => __( 'تکمیل شده (Completed)', 'woo-card-to-card-pro' ),
				),
			),
			'payment_deadline' => array(
				'title'       => __( 'مهلت پرداخت (دقیقه)', 'woo-card-to-card-pro' ),
				'type'        => 'number',
				'description' => __( 'زمان به دقیقه برای لغو خودکار سفارشات در انتظار پرداخت. (مثلاً ۶۰)', 'woo-card-to-card-pro' ),
				'default'     => '60',
			),
			'instructions' => array(
				'title'       => __( 'راهنما و توضیحات فاکتور', 'woo-card-to-card-pro' ),
				'type'        => 'textarea',
				'description' => __( 'توضیحات و راهنمایی که در صفحه دریافت سفارش (Thank You) نمایش داده می‌شود.', 'woo-card-to-card-pro' ),
				'default'     => __( 'لطفاً پس از انتقال وجه، حتماً عکس واضح فیش را از فرم زیر ارسال کنید تا سفارش شما تایید و وارد مرحله ارسال شود.', 'woo-card-to-card-pro' ),
			),
		);
	}

	/**
	 * Process Payment
	 *
	 * @param int $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return array( 'result' => 'fail' );
		}

		// Assign a specific active card to this order if not already assigned
		$active_card = WOO_C2C_Card_Manager::get_active_card_for_order( $this->cards, $order_id );
		if ( $active_card ) {
			$order->update_meta_data( '_c2c_destination_card', $active_card['raw_card_number'] );
			$order->update_meta_data( '_c2c_destination_bank', $active_card['bank_name'] );
			$order->update_meta_data( '_c2c_destination_holder', $active_card['card_holder'] );
		}
		
		$deadline = (int) $this->get_option( 'payment_deadline', '60' );
		if ( $deadline > 0 ) {
			$order->update_meta_data( '_c2c_payment_deadline', current_time( 'timestamp' ) + ( $deadline * 60 ) );
		}

		// Set initial status to awaiting receipt
		$order->update_status( 'wc-awaiting-receipt', __( 'سفارش با روش کارت به کارت ثبت شد؛ در انتظار بارگذاری فیش توسط مشتری.', 'woo-card-to-card-pro' ) );

		// Reduce stock levels
		wc_reduce_stock_levels( $order_id );

		// Empty cart
		WC()->cart->empty_cart();

		// Return thank you redirect
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * Render the visual card & receipt upload section on the Thank You page
	 *
	 * @param int $order_id
	 */
	public function render_receipt_and_card_section( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order || $order->get_payment_method() !== $this->id ) {
			return;
		}

		$this->output_card_and_form( $order );
	}

	/**
	 * Render receipt form on Customer View Order (My Account) page
	 *
	 * @param int $order_id
	 */
	public function render_view_order_receipt_section( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order || $order->get_payment_method() !== $this->id ) {
			return;
		}

		$this->output_card_and_form( $order );
	}

	/**
	 * Output Card UI and Upload Form
	 *
	 * @param WC_Order $order
	 */
	private function output_card_and_form( $order ) {
		$order_id = $order->get_id();

		// Retrieve assigned card or first card
		$assigned_card_number = $order->get_meta( '_c2c_destination_card' );
		$card = null;

		if ( ! empty( $assigned_card_number ) ) {
			foreach ( $this->cards as $c ) {
				if ( $c['raw_card_number'] === $assigned_card_number ) {
					$card = $c;
					break;
				}
			}
		}

		if ( ! $card ) {
			$card = WOO_C2C_Card_Manager::get_active_card_for_order( $this->cards, $order_id );
		}

		$receipt_url     = $order->get_meta( '_c2c_receipt_file_url' );
		$tracking_code   = $order->get_meta( '_c2c_tracking_code' );
		$sender_card     = $order->get_meta( '_c2c_sender_card' );
		$deposit_date    = $order->get_meta( '_c2c_deposit_date' );
		$receipt_status  = $order->get_meta( '_c2c_receipt_status' );
		$order_status    = $order->get_status();
		$payment_deadline= $order->get_meta( '_c2c_payment_deadline' );
		$upload_attempts = (int) $order->get_meta( '_c2c_upload_attempts' );
		$upload_attempts_remaining = max( 0, 3 - $upload_attempts );

		$require_tracking = ( 'yes' === $this->get_option( 'require_tracking', 'yes' ) );
		$require_sender   = ( 'yes' === $this->get_option( 'require_sender_card', 'no' ) );

		// Map variables for template compatibility.
		$timer_deadline    = ! empty( $payment_deadline ) ? (int) $payment_deadline : 0;
		$receipt_token     = $order->get_meta( '_c2c_receipt_token' );
		$receipt_viewer_url = ! empty( $receipt_token ) ? home_url( '/c2c-receipt/' . $receipt_token ) : '';

		include WOO_C2C_PLUGIN_DIR . 'templates/thankyou-receipt-form.php';
	}
}
