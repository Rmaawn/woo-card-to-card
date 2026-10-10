<?php
/**
 * Admin Meta Box Template for Receipt Details
 */

if (!defined('ABSPATH')) {
    exit;
}

$is_approved = ($receipt_status === 'approved');
$is_rejected = ($receipt_status === 'rejected');
?>

<div class="c2c-admin-metabox-wrapper" id="c2c-admin-box-<?php echo esc_attr($order_id); ?>">

    <?php if (!empty($duplicate_hashes) || !empty($duplicate_tracks)): ?>
    <div class="notice notice-error inline c2c-admin-alert">
        <p><strong>هشدار سیستم امنیتی: احتمال تطابق یا فیش تکراری!</strong></p>
        <div class="c2c-alert-content">
            <?php if (!empty($duplicate_hashes)): ?>
                <div class="c2c-alert-row">
                    <span>تصویر فیش مشابه در سفارشات:</span>
                    <?php foreach ($duplicate_hashes as $dup_id): ?>
                        <a href="<?php echo esc_url(get_edit_post_link($dup_id)); ?>" target="_blank" class="button button-small">#<?php echo esc_html($dup_id); ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($duplicate_tracks)): ?>
                <div class="c2c-alert-row">
                    <span>کد پیگیری یکسان در سفارشات:</span>
                    <?php foreach ($duplicate_tracks as $dup_id): ?>
                        <a href="<?php echo esc_url(get_edit_post_link($dup_id)); ?>" target="_blank" class="button button-small">#<?php echo esc_html($dup_id); ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($receipt_url)): ?>
        <div class="c2c-empty-state">
            <p>مشتری هنوز فیش یا شماره پیگیری پرداخت را در سامانه ثبت نکرده است.</p>
        </div>
    <?php else: ?>
        
        <div class="c2c-admin-grid">
            <div class="c2c-admin-col-img">
                <a href="<?php echo esc_url($viewer_url); ?>" target="_blank" class="c2c-admin-thumb-link" title="مشاهده فیش در اندازه بزرگ">
                    <img src="<?php echo esc_url($receipt_url); ?>" alt="تصویر فیش پرداخت" class="c2c-admin-thumb" />
                </a>
                <div class="c2c-admin-img-actions">
                    <a href="<?php echo esc_url($viewer_url); ?>" target="_blank" class="button button-secondary">
                        <span class="dashicons dashicons-external"></span> مشاهده در تب جدید
                    </a>
                </div>
            </div>

            <div class="c2c-admin-col-details">
                <div class="c2c-status-banner c2c-status-<?php echo esc_attr($receipt_status); ?>">
                    <?php if ($is_approved): ?>
                        <span class="dashicons dashicons-yes-alt"></span> <strong>تایید شده</strong>
                    <?php elseif ($is_rejected): ?>
                        <span class="dashicons dashicons-dismiss"></span> <strong>رد شده</strong>
                    <?php else: ?>
                        <span class="dashicons dashicons-hourglass"></span> <strong>در انتظار بررسی</strong>
                    <?php endif; ?>
                </div>

                <table class="widefat striped c2c-details-table">
                    <tbody>
                        <tr>
                            <th>کد پیگیری تراکنش:</th>
                            <td dir="ltr" style="text-align: right;">
                                <?php echo !empty($tracking_code) ? '<code>' . esc_html($tracking_code) . '</code>' : '—'; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>۴ رقم آخر کارت مبدا:</th>
                            <td dir="ltr" style="text-align: right;">
                                <?php echo !empty($sender_card) ? '<code>**** ' . esc_html($sender_card) . '</code>' : '—'; ?>
                            </td>
                        </tr>
                        <?php if (!empty($dest_card)): ?>
                        <tr>
                            <th>کارت واریز شده:</th>
                            <td dir="ltr" style="text-align: right;">
                                <code><?php echo esc_html(WOO_C2C_Card_Manager::format_card_number($dest_card)); ?></code>
                                <?php if (!empty($dest_bank)): ?>
                                    <br><small class="description"><?php echo esc_html($dest_bank); ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th>زمان بارگذاری فیش:</th>
                            <td><?php echo esc_html($deposit_date); ?></td>
                        </tr>
                    </tbody>
                </table>

                <div class="c2c-action-buttons">
                    <button type="button" class="button button-primary c2c-btn-approve" data-order-id="<?php echo esc_attr($order_id); ?>" <?php echo $is_approved ? 'disabled' : ''; ?>>
                        <span class="dashicons dashicons-yes"></span>
                        <?php echo $is_approved ? 'فیش تایید شده است' : 'تایید فیش'; ?>
                    </button>
                    
                    <button type="button" class="button c2c-btn-danger c2c-btn-reject" data-order-id="<?php echo esc_attr($order_id); ?>" <?php echo $is_rejected ? 'disabled' : ''; ?>>
                        <span class="dashicons dashicons-no-alt"></span>
                        <?php echo $is_rejected ? 'فیش رد شده است' : 'رد فیش'; ?>
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
