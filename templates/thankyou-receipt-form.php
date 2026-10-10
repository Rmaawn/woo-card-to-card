<?php
/**
 * Frontend Template for Receipt Upload and Card Display
 */

if (!defined('ABSPATH')) {
    exit;
}

$order_id    = $order->get_id();
$order_key   = $order->get_order_key();
$total_raw   = $order->get_total();
$total_fmt   = $order->get_formatted_order_total();

// Support for variables
$has_timer     = isset($timer_deadline) && $timer_deadline > time();
$viewer_url    = isset($receipt_viewer_url) && !empty($receipt_viewer_url) ? $receipt_viewer_url : $receipt_url;
$has_attempts  = isset($upload_attempts_remaining);
?>

<div class="woo-c2c-wrapper" id="woo-c2c-form-<?php echo esc_attr($order_id); ?>">

    <div class="woo-c2c-header">
        <h3 class="woo-c2c-title">پرداخت کارت به کارت</h3>
        <p class="woo-c2c-desc">لطفا مبلغ سفارش را به کارت زیر واریز نموده و فیش پرداختی را آپلود کنید.</p>
    </div>

    <?php if ($has_timer): ?>
    <div class="woo-c2c-timer-box" data-deadline="<?php echo esc_attr($timer_deadline); ?>" style="display: none;">
        <span class="woo-c2c-timer-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg></span>
        <span class="woo-c2c-timer-text">زمان باقیمانده برای پرداخت:</span>
        <span class="woo-c2c-countdown">
            <b class="woo-c2c-min">00</b>:<b class="woo-c2c-sec">00</b>
        </span>
    </div>
    <?php endif; ?>

    <?php if (!empty($card)): ?>
    <div class="woo-c2c-payment-info">
        <div class="woo-c2c-amount-box">
            <span>مبلغ قابل پرداخت:</span>
            <strong><?php echo wp_kses_post($total_fmt); ?></strong>
        </div>
        
        <div class="woo-c2c-bank-card <?php echo !empty($card['bank_name']) ? 'bank-' . strtolower(str_replace(' ', '-', esc_attr($card['bank_name']))) : ''; ?>">
            <div class="woo-c2c-card-inner">
                <div class="woo-c2c-card-top">
                    <?php if (!empty($card['bank_name'])): ?>
                        <span class="woo-c2c-bank-logo-text"><?php echo esc_html($card['bank_name']); ?></span>
                    <?php endif; ?>
                    <div class="woo-c2c-chip">
                        <div class="woo-c2c-chip-line"></div>
                        <div class="woo-c2c-chip-line"></div>
                        <div class="woo-c2c-chip-line"></div>
                    </div>
                </div>
                
                <div class="woo-c2c-card-number-box">
                    <div class="woo-c2c-card-number" dir="ltr">
                        <?php 
                        $num = str_replace(' ', '', $card['card_number']);
                        $chunks = str_split($num, 4);
                        foreach($chunks as $chunk) {
                            echo '<span>' . esc_html($chunk) . '</span>';
                        }
                        ?>
                    </div>
                    <button type="button" class="woo-c2c-copy-btn" data-clipboard="<?php echo esc_attr(str_replace(' ', '', $card['card_number'])); ?>" title="کپی شماره کارت">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                    </button>
                </div>

                <div class="woo-c2c-card-bottom">
                    <div class="woo-c2c-card-owner">
                        <small>صاحب حساب</small>
                        <span><?php echo esc_html($card['card_holder']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($card['shaba'])): ?>
        <div class="woo-c2c-shaba-box">
            <span>شماره شبا:</span>
            <div class="woo-c2c-shaba-val" dir="ltr"><?php echo esc_html(WOO_C2C_Card_Manager::format_shaba($card['shaba'])); ?></div>
            <button type="button" class="woo-c2c-copy-btn" data-clipboard="<?php echo esc_attr(str_replace(' ', '', $card['shaba'])); ?>" title="کپی شماره شبا">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
            </button>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="woo-c2c-upload-section">
        <?php if (!empty($receipt_url)): ?>
            <div class="woo-c2c-status-alert woo-c2c-status-<?php echo esc_attr($receipt_status); ?>">
                <div class="woo-c2c-status-icon">
                    <?php if ($receipt_status === 'approved'): ?>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <?php elseif ($receipt_status === 'rejected'): ?>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                    <?php else: ?>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    <?php endif; ?>
                </div>
                <div class="woo-c2c-status-body">
                    <?php if ($receipt_status === 'approved'): ?>
                        <strong>پرداخت شما تایید شد</strong>
                        <p>سفارش شما در حال پیگیری است.</p>
                    <?php elseif ($receipt_status === 'rejected'): ?>
                        <strong>فیش ارسالی رد شد</strong>
                        <p>لطفا تصویر فیش جدیدی آپلود کنید.</p>
                        <button type="button" class="woo-c2c-btn-secondary" onclick="document.getElementById('woo-c2c-form-wrapper').style.display='block';">آپلود مجدد</button>
                    <?php else: ?>
                        <strong>فیش در حال بررسی است</strong>
                        <p>فیش پرداختی شما دریافت شد و به زودی بررسی خواهد شد.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="woo-c2c-receipt-preview">
                <a href="<?php echo esc_url($viewer_url); ?>" target="_blank" title="مشاهده فیش">
                    <img src="<?php echo esc_url($receipt_url); ?>" alt="فیش پرداختی" />
                </a>
            </div>
        <?php endif; ?>

        <div id="woo-c2c-form-wrapper" style="<?php echo (!empty($receipt_url) && $receipt_status !== 'rejected') ? 'display:none;' : ''; ?>">
            <form id="woo-c2c-upload-form" enctype="multipart/form-data">
                <input type="hidden" name="order_id" value="<?php echo esc_attr($order_id); ?>">
                <input type="hidden" name="order_key" value="<?php echo esc_attr($order_key); ?>">

                <div class="woo-c2c-file-area" id="woo-c2c-dropzone">
                    <input type="file" id="woo-c2c-file-input" name="receipt_file" accept="image/jpeg,image/png,image/webp" required>
                    <div class="woo-c2c-file-prompt">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                        <p><strong>برای انتخاب فیش کلیک کنید</strong><br>یا فایل را اینجا رها کنید (JPG, PNG)</p>
                    </div>
                    <div class="woo-c2c-file-preview-box" id="woo-c2c-preview-box" style="display:none;">
                        <img id="woo-c2c-img-preview" src="" alt="پیش نمایش" />
                        <button type="button" id="woo-c2c-remove-file" title="حذف">&times;</button>
                        <div id="woo-c2c-progress-bar" class="woo-c2c-progress"></div>
                    </div>
                </div>

                <div class="woo-c2c-fields-row">
                    <div class="woo-c2c-form-group">
                        <label for="woo_c2c_tracking">شماره پیگیری (اختیاری)</label>
                        <input type="text" id="woo_c2c_tracking" name="tracking_code" placeholder="مثلا 12345678" dir="ltr">
                    </div>
                    <div class="woo-c2c-form-group">
                        <label for="woo_c2c_sender">۴ رقم آخر کارت مبدا (اختیاری)</label>
                        <input type="text" id="woo_c2c_sender" name="sender_card" maxlength="4" placeholder="مثلا 1234" dir="ltr" pattern="\d{4}">
                    </div>
                </div>

                <button type="submit" id="woo-c2c-submit-btn" class="woo-c2c-btn-primary">
                    <span>ارسال فیش پرداخت</span>
                </button>
            </form>
        </div>
    </div>
</div>
