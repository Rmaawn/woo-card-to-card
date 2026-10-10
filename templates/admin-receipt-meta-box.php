<?php
/**
 * Admin Order Meta Box Template - macOS Style
 *
 * @package Woo_Card_To_Card
 */

if (!defined('ABSPATH')) {
    exit;
}

$viewer_url = isset($receipt_viewer_url) && !empty($receipt_viewer_url) ? $receipt_viewer_url : $receipt_url;
$is_approved = ($receipt_status === 'approved');
$is_rejected = ($receipt_status === 'rejected');
$is_pending = (!$is_approved && !$is_rejected && !empty($receipt_url));

?>

<div class="c2c-admin-metabox-wrapper" id="c2c-admin-box-<?php echo esc_attr($order_id); ?>">

    <?php if (!empty($duplicate_hashes) || !empty($duplicate_tracks)): ?>
    <div class="c2c-admin-alert c2c-alert-critical">
        <div class="c2c-alert-header">
            <span class="dashicons dashicons-warning"></span>
            <strong>هشدار سیستم امنیتی: احتمال تطابق یا فیش تکراری!</strong>
        </div>
        <div class="c2c-alert-content">
            <?php if (!empty($duplicate_hashes)): ?>
                <div class="c2c-alert-row">
                    <span class="c2c-alert-label">تصویر فیش مشابه در سفارشات:</span>
                    <div class="c2c-dup-tags">
                    <?php foreach ($duplicate_hashes as $dup_id): ?>
                        <a href="<?php echo esc_url(get_edit_post_link($dup_id)); ?>" target="_blank" class="c2c-tag c2c-tag-danger">#<?php echo esc_html($dup_id); ?></a>
                    <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($duplicate_tracks)): ?>
                <div class="c2c-alert-row">
                    <span class="c2c-alert-label">کد پیگیری یکسان در سفارشات:</span>
                    <div class="c2c-dup-tags">
                    <?php foreach ($duplicate_tracks as $dup_id): ?>
                        <a href="<?php echo esc_url(get_edit_post_link($dup_id)); ?>" target="_blank" class="c2c-tag c2c-tag-warning">#<?php echo esc_html($dup_id); ?></a>
                    <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($receipt_url)): ?>
        <div class="c2c-empty-state">
            <div class="c2c-empty-icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <h4>در انتظار ارسال فیش</h4>
            <p>مشتری هنوز فیش یا شماره پیگیری پرداخت را در سامانه ثبت نکرده است.</p>
        </div>
    <?php else: ?>
        
        <div class="c2c-admin-grid">
            <!-- Left Column: Receipt QuickLook Preview -->
            <div class="c2c-admin-col-img">
                <div class="c2c-admin-thumb-wrap" data-full-image="<?php echo esc_url($viewer_url); ?>" title="کلیک برای بزرگ‌نمایی (QuickLook)">
                    <img src="<?php echo esc_url($receipt_url); ?>" alt="تصویر فیش پرداخت" class="c2c-admin-thumb" />
                    <div class="c2c-admin-zoom-overlay">
                        <span class="dashicons dashicons-search"></span>
                        <span>پیش‌نمایش سریع</span>
                    </div>
                </div>
                <div class="c2c-admin-img-actions">
                    <a href="<?php echo esc_url($viewer_url); ?>" target="_blank" class="c2c-btn-outline">
                        <span class="dashicons dashicons-external"></span> باز کردن در تب جدید
                    </a>
                </div>
            </div>

            <!-- Right Column: Verification Details & Action Buttons -->
            <div class="c2c-admin-col-details">
                
                <div class="c2c-status-banner c2c-status-<?php echo esc_attr($receipt_status); ?>">
                    <?php if ($is_approved): ?>
                        <span class="dashicons dashicons-yes-alt"></span> <strong>پرداخت تایید شده</strong>
                    <?php elseif ($is_rejected): ?>
                        <span class="dashicons dashicons-dismiss"></span> <strong>فیش رد شده</strong>
                    <?php else: ?>
                        <span class="dashicons dashicons-hourglass"></span> <strong>در انتظار بررسی و تایید</strong>
                    <?php endif; ?>
                </div>

                <div class="c2c-details-list">
                    <div class="c2c-detail-item">
                        <span class="c2c-label">کد پیگیری تراکنش:</span>
                        <span class="c2c-value c2c-code" dir="ltr"><?php echo !empty($tracking_code) ? esc_html($tracking_code) : '<span class="c2c-muted">—</span>'; ?></span>
                    </div>
                    <div class="c2c-detail-item">
                        <span class="c2c-label">۴ رقم آخر کارت مبدا:</span>
                        <span class="c2c-value c2c-code" dir="ltr"><?php echo !empty($sender_card) ? '**** ' . esc_html($sender_card) : '<span class="c2c-muted">—</span>'; ?></span>
                    </div>
                    <?php if (!empty($dest_card)): ?>
                    <div class="c2c-detail-item c2c-dest-card">
                        <span class="c2c-label">کارت واریز شده:</span>
                        <div class="c2c-value">
                            <span class="c2c-code" dir="ltr"><?php echo esc_html(WOO_C2C_Card_Manager::format_card_number($dest_card)); ?></span>
                            <?php if (!empty($dest_bank)): ?>
                                <span class="c2c-bank-name"><?php echo esc_html($dest_bank); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="c2c-detail-item">
                        <span class="c2c-label">زمان بارگذاری فیش:</span>
                        <span class="c2c-value c2c-date"><?php echo esc_html($deposit_date); ?></span>
                    </div>
                </div>

                <div class="c2c-action-buttons">
                    <button type="button" class="c2c-btn c2c-btn-success c2c-btn-approve" data-order-id="<?php echo esc_attr($order_id); ?>" <?php echo $is_approved ? 'disabled' : ''; ?>>
                        <span class="dashicons dashicons-yes"></span>
                        <?php echo $is_approved ? 'فیش تایید شده است' : 'تایید فیش و سفارش'; ?>
                    </button>
                    
                    <button type="button" class="c2c-btn c2c-btn-danger c2c-btn-reject" data-order-id="<?php echo esc_attr($order_id); ?>" <?php echo $is_rejected ? 'disabled' : ''; ?>>
                        <span class="dashicons dashicons-no-alt"></span>
                        <?php echo $is_rejected ? 'فیش رد شده است' : 'رد کردن فیش'; ?>
                    </button>
                </div>
                
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- macOS QuickLook Lightbox Modal -->
<div id="c2c-receipt-modal" class="c2c-modal-overlay">
    <div class="c2c-modal-container">
        <div class="c2c-modal-header">
            <h3>پیش‌نمایش سریع فیش (QuickLook)</h3>
            <button type="button" class="c2c-modal-close" title="بستن (Esc)">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="c2c-modal-body">
            <div class="c2c-modal-img-wrapper">
                <img id="c2c-modal-image" src="" alt="فیش پرداخت" />
            </div>
        </div>
        <div class="c2c-modal-footer">
            <div class="c2c-modal-zoom-controls">
                <button type="button" class="c2c-zoom-btn" data-zoom="out" title="کوچک‌نمایی"><span class="dashicons dashicons-minus"></span></button>
                <button type="button" class="c2c-zoom-btn" data-zoom="reset" title="اندازه اصلی"><span class="dashicons dashicons-image-rotate"></span></button>
                <button type="button" class="c2c-zoom-btn" data-zoom="in" title="بزرگ‌نمایی"><span class="dashicons dashicons-plus-alt2"></span></button>
            </div>
        </div>
    </div>
</div>
