<?php
/**
 * Thank You / Order View Receipt Form & Card Display Template
 * Designed with Apple Human Interface Guidelines & Realistic Tactile Elements
 *
 * @package Woo_Card_To_Card_Pro
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

<div class="c2c-apple-container" id="c2c-container-<?php echo esc_attr($order_id); ?>">

    <!-- Apple-style Header -->
    <header class="c2c-header">
        <div class="c2c-header-badge">
            <svg class="c2c-shield-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                <path d="m9 12 2 2 4-4"/>
            </svg>
            <span>پرداخت مستقیم و امن بانکی</span>
        </div>
        <h3 class="c2c-title">انتقال وجه کارت به کارت</h3>
        <p class="c2c-subtitle">مبلغ سفارش را به کارت زیر انتقال داده و تصویر یا اطلاعات فیش را ثبت فرمایید.</p>
    </header>

    <?php if ($has_timer): ?>
        <!-- Apple Dynamic Island Style Countdown -->
        <div class="c2c-timer-wrapper" data-deadline="<?php echo esc_attr($timer_deadline); ?>">
            <div class="c2c-timer-capsule">
                <svg class="c2c-timer-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span class="c2c-timer-label">مهلت پرداخت:</span>
                <span class="c2c-timer-countdown">
                    <span class="c2c-timer-min">00</span>:<span class="c2c-timer-sec">00</span>
                </span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Apple Stepper Progress Bar -->
    <nav class="c2c-steps" aria-label="مراحل پرداخت">
        <div class="c2c-step <?php echo empty($receipt_url) ? 'active' : 'completed'; ?>">
            <div class="c2c-step-dot">
                <?php if (!empty($receipt_url)): ?>
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <?php else: ?>
                    <span>۱</span>
                <?php endif; ?>
            </div>
            <span class="c2c-step-label">واریز وجه</span>
        </div>
        <div class="c2c-step-line <?php echo !empty($receipt_url) ? 'active' : ''; ?>"></div>
        <div class="c2c-step <?php echo (!empty($receipt_url) && $receipt_status !== 'rejected') ? 'completed' : (empty($receipt_url) ? '' : 'active'); ?>">
            <div class="c2c-step-dot">
                <?php if (!empty($receipt_url) && $receipt_status !== 'rejected'): ?>
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <?php else: ?>
                    <span>۲</span>
                <?php endif; ?>
            </div>
            <span class="c2c-step-label">ثبت رسید</span>
        </div>
        <div class="c2c-step-line <?php echo ($receipt_status === 'approved') ? 'active' : ''; ?>"></div>
        <div class="c2c-step <?php echo ($receipt_status === 'approved') ? 'completed' : ''; ?>">
            <div class="c2c-step-dot">
                <?php if ($receipt_status === 'approved'): ?>
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <?php else: ?>
                    <span>۳</span>
                <?php endif; ?>
            </div>
            <span class="c2c-step-label">تایید نهایی</span>
        </div>
    </nav>

    <!-- Apple Pay Summary Card: Amount -->
    <div class="c2c-amount-box">
        <div class="c2c-amount-info">
            <span class="c2c-amount-label">مبلغ قابل پرداخت</span>
            <div class="c2c-amount-val">
                <span class="c2c-amount-highlight"><?php echo wp_kses_post($total_fmt); ?></span>
            </div>
        </div>
        <button type="button" class="c2c-copy-btn" data-clipboard="<?php echo esc_attr(preg_replace('/\D/', '', $total_raw)); ?>" title="کپی مبلغ عددی">
            <svg class="c2c-copy-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
            </svg>
            <span class="c2c-copy-btn-text">کپی مبلغ</span>
        </button>
    </div>

    <?php if ($card): ?>
    <!-- Realistic Bank Card (Inspired by Apple Card & Titanium Luxury Cards) -->
    <div class="c2c-card-scene">
        <div class="c2c-bank-card c2c-card-theme-<?php echo esc_attr($card['theme'] ?? 'default'); ?>" style="--card-base: <?php echo esc_attr($card['color'] ?? '#1d1d1f'); ?>;">
            
            <!-- Dynamic Specular Sheen (Moves with cursor in JS) -->
            <div class="c2c-card-specular"></div>
            
            <div class="c2c-card-inner">
                <!-- Card Header: Bank Brand & Shetab / NFC -->
                <div class="c2c-card-top">
                    <div class="c2c-bank-brand">
                        <span class="c2c-bank-name"><?php echo esc_html($card['bank_name']); ?></span>
                    </div>
                    <div class="c2c-card-badges">
                        <div class="c2c-contactless" title="قابلیت پرداخت بدون تماس (NFC)">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                <path d="M8.5 19.5c-1.8-2.2-2.8-5-2.8-8s1-5.8 2.8-8"/>
                                <path d="M12.5 17.5c-1.3-1.6-2-3.6-2-5.7s.7-4.1 2-5.7"/>
                                <path d="M16.5 15.5c-.8-1-1.2-2.3-1.2-3.7s.4-2.7 1.2-3.7"/>
                            </svg>
                        </div>
                        <div class="c2c-shetab-pill">
                            <span>شتاب</span>
                        </div>
                    </div>
                </div>

                <!-- Realistic Micro EMV Smart Chip -->
                <div class="c2c-chip-wrapper">
                    <div class="c2c-chip" aria-hidden="true">
                        <svg viewBox="0 0 50 38" width="48" height="36" class="c2c-chip-svg" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <linearGradient id="c2c-emv-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#f0f3f8"/>
                                    <stop offset="30%" stop-color="#d6dde7"/>
                                    <stop offset="60%" stop-color="#ffffff"/>
                                    <stop offset="85%" stop-color="#c1cbd7"/>
                                    <stop offset="100%" stop-color="#9da8b6"/>
                                </linearGradient>
                            </defs>
                            <rect x="1" y="1" width="48" height="36" rx="6" fill="url(#c2c-emv-grad)" stroke="rgba(0,0,0,0.22)" stroke-width="0.8"/>
                            <rect x="15" y="10" width="20" height="18" rx="3" fill="none" stroke="rgba(0,0,0,0.25)" stroke-width="0.9"/>
                            <line x1="1" y1="13" x2="15" y2="13" stroke="rgba(0,0,0,0.25)" stroke-width="0.9"/>
                            <line x1="1" y1="25" x2="15" y2="25" stroke="rgba(0,0,0,0.25)" stroke-width="0.9"/>
                            <line x1="35" y1="13" x2="49" y2="13" stroke="rgba(0,0,0,0.25)" stroke-width="0.9"/>
                            <line x1="35" y1="25" x2="49" y2="25" stroke="rgba(0,0,0,0.25)" stroke-width="0.9"/>
                            <line x1="25" y1="1" x2="25" y2="10" stroke="rgba(0,0,0,0.25)" stroke-width="0.9"/>
                            <line x1="25" y1="28" x2="25" y2="37" stroke="rgba(0,0,0,0.25)" stroke-width="0.9"/>
                        </svg>
                    </div>
                </div>

                <!-- 16-Digit Card Number with Apple Quick-Copy -->
                <div class="c2c-card-number-wrapper">
                    <div class="c2c-card-number" dir="ltr">
                        <?php 
                        $num = str_replace(' ', '', $card['card_number']);
                        $chunks = str_split($num, 4);
                        foreach($chunks as $chunk) {
                            echo '<span class="c2c-card-chunk">' . esc_html($chunk) . '</span>';
                        }
                        ?>
                    </div>
                    <button type="button" class="c2c-copy-card-btn" data-clipboard="<?php echo esc_attr($card['raw_card_number']); ?>" title="کپی شماره کارت">
                        <svg class="c2c-copy-svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                        </svg>
                        <span class="c2c-copy-pill-text">کپی</span>
                    </button>
                </div>

                <!-- Bottom Information: Cardholder & Shaba -->
                <div class="c2c-card-bottom">
                    <div class="c2c-holder-info">
                        <span class="c2c-card-label">صاحب کارت</span>
                        <span class="c2c-card-val"><?php echo esc_html($card['card_holder']); ?></span>
                    </div>

                    <?php if (!empty($card['shaba'])): ?>
                    <div class="c2c-shaba-info">
                        <span class="c2c-card-label">شماره شبا</span>
                        <div class="c2c-shaba-row">
                            <span class="c2c-card-val c2c-shaba-val" dir="ltr"><?php echo esc_html(WOO_C2C_Card_Manager::format_shaba($card['shaba'])); ?></span>
                            <button type="button" class="c2c-copy-shaba-btn" data-clipboard="<?php echo esc_attr(str_replace(' ', '', $card['shaba'])); ?>" title="کپی شماره شبا">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Receipt Management Section -->
    <div class="c2c-receipt-section">
        <?php if (!empty($receipt_url)): ?>
            <!-- Apple iOS Transaction Status Card -->
            <div class="c2c-status-card c2c-status-<?php echo esc_attr($receipt_status); ?>">
                <div class="c2c-status-header">
                    <div class="c2c-status-icon-wrapper">
                        <?php if ($receipt_status === 'approved'): ?>
                            <svg class="c2c-status-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        <?php elseif ($receipt_status === 'rejected'): ?>
                            <svg class="c2c-status-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                        <?php else: ?>
                            <svg class="c2c-status-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        <?php endif; ?>
                    </div>
                    <div class="c2c-status-text">
                        <?php if ($receipt_status === 'approved'): ?>
                            <strong class="c2c-status-title c2c-text-success">پرداخت شما تایید شد</strong>
                            <p class="c2c-status-desc">سفارش شما در مرحله آماده‌سازی و ارسال قرار گرفت.</p>
                        <?php elseif ($receipt_status === 'rejected'): ?>
                            <strong class="c2c-status-title c2c-text-danger">رسید ارسالی تایید نشد</strong>
                            <p class="c2c-status-desc">لطفاً رسید معتبر دیگری بارگذاری کرده یا مشخصات را بازبینی نمایید.</p>
                        <?php else: ?>
                            <strong class="c2c-status-title c2c-text-primary">رسید در حال بررسی است</strong>
                            <p class="c2c-status-desc">اطلاعات فیش دریافت شده و به زودی توسط کارشناسان تایید می‌شود.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Apple Inset Grouped Details -->
                <div class="c2c-receipt-preview-inline">
                    <a href="<?php echo esc_url($viewer_url); ?>" target="_blank" class="c2c-receipt-thumb-link" title="مشاهده تصویر بزرگ‌تر فیش">
                        <img src="<?php echo esc_url($receipt_url); ?>" alt="تصویر فیش واریزی" class="c2c-receipt-thumb" />
                        <div class="c2c-thumb-overlay">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                <line x1="11" y1="8" x2="11" y2="14"></line>
                                <line x1="8" y1="11" x2="14" y2="11"></line>
                            </svg>
                        </div>
                    </a>

                    <div class="c2c-receipt-meta-summary">
                        <?php if (!empty($tracking_code)): ?>
                            <div class="c2c-meta-row">
                                <span class="c2c-meta-label">کد پیگیری:</span>
                                <span class="c2c-meta-value" dir="ltr"><?php echo esc_html($tracking_code); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($sender_card)): ?>
                            <div class="c2c-meta-row">
                                <span class="c2c-meta-label">کارت مبدا:</span>
                                <span class="c2c-meta-value" dir="ltr">**** <?php echo esc_html($sender_card); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($deposit_date)): ?>
                            <div class="c2c-meta-row">
                                <span class="c2c-meta-label">تاریخ ثبت:</span>
                                <span class="c2c-meta-value"><?php echo esc_html($deposit_date); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($receipt_status === 'rejected'): ?>
                    <div class="c2c-action-row">
                        <button type="button" class="c2c-btn-secondary" onclick="document.getElementById('c2c-upload-form-wrapper').classList.toggle('c2c-hidden');">
                            <span>بارگذاری مجدد رسید</span>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Apple Style Upload Form -->
        <div id="c2c-upload-form-wrapper" class="c2c-upload-wrapper <?php echo (!empty($receipt_url) && $receipt_status !== 'rejected') ? 'c2c-hidden' : ''; ?>">
            <form id="c2c-receipt-upload-form" class="c2c-upload-form" enctype="multipart/form-data">
                <input type="hidden" name="order_id" value="<?php echo esc_attr($order_id); ?>">
                <input type="hidden" name="order_key" value="<?php echo esc_attr($order_key); ?>">

                <div class="c2c-form-header">
                    <h4>بارگذاری تصویر رسید پرداخت</h4>
                    <p>عکس فیش بانکی، رسید عابربانک یا اسکرین‌شات رسید همراه بانک را انتخاب کنید.</p>
                    <?php if ($has_attempts): ?>
                        <div class="c2c-attempts-badge">تلاش‌های مجاز: <?php echo intval($upload_attempts_remaining); ?></div>
                    <?php endif; ?>
                </div>

                <!-- Apple AirDrop / Files Drag & Drop Zone -->
                <div class="c2c-dropzone" id="c2c-dropzone">
                    <input type="file" id="c2c-file-input" name="receipt_file" accept="image/jpeg,image/png,image/webp" class="c2c-file-input" required>
                    
                    <div class="c2c-dropzone-content">
                        <div class="c2c-upload-icon-wrapper">
                            <svg class="c2c-upload-icon" viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                        </div>
                        <div class="c2c-dropzone-text">
                            <strong>فایل رسید را اینجا رها کنید یا کلیک کنید</strong>
                            <span>فرمت‌های JPG، PNG یا WEBP (حداکثر تا ۶ مگابایت)</span>
                        </div>
                    </div>

                    <!-- Apple Files Item Preview -->
                    <div class="c2c-file-preview-box c2c-hidden" id="c2c-file-preview-box">
                        <div class="c2c-preview-image-wrapper">
                            <img id="c2c-img-preview" src="" alt="پیش‌نمایش رسید">
                            <div class="c2c-upload-progress">
                                <div class="c2c-progress-bar" id="c2c-progress-bar"></div>
                            </div>
                        </div>
                        <div class="c2c-preview-details">
                            <div class="c2c-file-meta">
                                <span id="c2c-filename" class="c2c-filename"></span>
                                <span id="c2c-filesize" class="c2c-filesize"></span>
                            </div>
                            <button type="button" id="c2c-remove-file" class="c2c-btn-remove" title="حذف و انتخاب مجدد">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Apple Inset Grouped Input Fields -->
                <div class="c2c-fields-grid">
                    <div class="c2c-field-group">
                        <label for="c2c_tracking_code">کد پیگیری تراکنش <?php if ($require_tracking): ?><span class="c2c-required">*</span><?php endif; ?></label>
                        <div class="c2c-input-wrapper">
                            <input type="text" id="c2c_tracking_code" name="tracking_code" class="c2c-input" placeholder="مثال: 123456789" <?php echo $require_tracking ? 'required' : ''; ?> dir="ltr">
                        </div>
                    </div>

                    <div class="c2c-field-group">
                        <label for="c2c_sender_card">۴ رقم آخر کارت مبدا <?php if ($require_sender): ?><span class="c2c-required">*</span><?php endif; ?></label>
                        <div class="c2c-input-wrapper">
                            <input type="text" id="c2c_sender_card" name="sender_card" class="c2c-input c2c-input-digits" maxlength="4" placeholder="مثال: 4567" <?php echo $require_sender ? 'required' : ''; ?> dir="ltr" pattern="\d{4}">
                        </div>
                    </div>
                </div>

                <!-- Apple Primary Action Button -->
                <div class="c2c-submit-wrapper">
                    <button type="submit" id="c2c-submit-btn" class="c2c-btn-primary">
                        <span class="c2c-btn-text">ثبت نهایی رسید پرداخت</span>
                        <svg class="c2c-btn-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                        <span class="c2c-spinner c2c-hidden"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
