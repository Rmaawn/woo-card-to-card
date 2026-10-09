/**
 * Admin JavaScript for Card-to-Card Pro Gateway
 * macOS Sonoma / Apple Style Admin Dialogs & QuickLook
 */
(function ($) {
    'use strict';

    $(document).ready(function () {

        // --- 1. macOS Style Admin Toast ---
        function showAdminToast(msg, type = 'success') {
            $('.c2c-admin-toast').remove();
            const icon = type === 'success' ? 'yes' : 'no-alt';
            const iconColor = type === 'success' ? '#34c759' : '#ff3b30';
            
            const $toast = $(`
                <div class="c2c-admin-toast">
                    <span class="dashicons dashicons-${icon}" style="color: ${iconColor};"></span>
                    <span>${msg}</span>
                </div>
            `).appendTo('body');
            
            requestAnimationFrame(() => {
                setTimeout(() => $toast.addClass('show'), 10);
            });

            setTimeout(() => {
                $toast.removeClass('show');
                setTimeout(() => $toast.remove(), 400);
            }, 3000);
        }

        // --- 2. macOS QuickLook Lightbox Modal ---
        const $modal = $('#c2c-receipt-modal');
        const $modalImg = $('#c2c-modal-image');
        let currentZoom = 1;

        $(document).on('click', '.c2c-admin-thumb-wrap', function (e) {
            e.preventDefault();
            const fullUrl = $(this).attr('data-full-image');
            if (fullUrl) {
                currentZoom = 1;
                $modalImg.attr('src', fullUrl).css('transform', `scale(${currentZoom})`);
                $modal.addClass('active');
            }
        });

        $('.c2c-modal-close').on('click', () => $modal.removeClass('active'));
        
        $modal.on('click', function (e) {
            if ($(e.target).hasClass('c2c-modal-overlay') || $(e.target).hasClass('c2c-modal-body')) {
                $modal.removeClass('active');
            }
        });

        $(document).on('keyup', function (e) {
            if (e.key === 'Escape' && $modal.hasClass('active')) {
                $modal.removeClass('active');
            }
        });

        $('.c2c-zoom-btn').on('click', function () {
            const action = $(this).data('zoom');
            if (action === 'in') currentZoom = Math.min(3, currentZoom + 0.25);
            else if (action === 'out') currentZoom = Math.max(0.5, currentZoom - 0.25);
            else currentZoom = 1;
            
            $modalImg.css('transform', `scale(${currentZoom})`);
        });

        // --- 3. macOS Style Modal Alerts ---
        function customConfirm(message, confirmText, isDanger, callback) {
            const btnBg = isDanger ? '#ff3b30' : '#0071e3';
            const overlay = $(`
                <div style="position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:999999;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);">
                    <div style="background:#ffffff;padding:24px 28px;border-radius:16px;max-width:380px;width:90%;text-align:right;direction:rtl;box-shadow:0 24px 48px rgba(0,0,0,0.2);border:1px solid rgba(0,0,0,0.08);font-family:-apple-system,BlinkMacSystemFont,'SF Pro Text','Vazirmatn',sans-serif;">
                        <h4 style="margin:0 0 10px;font-size:15px;font-weight:700;color:#1d1d1f;">تایید عملیات</h4>
                        <p style="margin:0 0 20px;color:#6e6e73;font-size:13.5px;line-height:1.5;">${message}</p>
                        <div style="display:flex;gap:10px;justify-content:flex-end;">
                            <button class="c2c-cancel-btn" style="padding:8px 16px;border:1px solid rgba(0,0,0,0.12);background:#ffffff;border-radius:8px;cursor:pointer;font-size:13px;font-weight:500;color:#1d1d1f;font-family:inherit;">انصراف</button>
                            <button class="c2c-confirm-btn" style="padding:8px 16px;border:none;background:${btnBg};color:#ffffff;border-radius:8px;cursor:pointer;font-weight:600;font-size:13px;font-family:inherit;">${confirmText}</button>
                        </div>
                    </div>
                </div>
            `).appendTo('body');

            overlay.find('.c2c-cancel-btn').on('click', () => { overlay.remove(); callback(false); });
            overlay.find('.c2c-confirm-btn').on('click', () => { overlay.remove(); callback(true); });
        }

        function customPrompt(message, callback) {
            const overlay = $(`
                <div style="position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:999999;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);">
                    <div style="background:#ffffff;padding:24px 28px;border-radius:16px;max-width:400px;width:90%;text-align:right;direction:rtl;box-shadow:0 24px 48px rgba(0,0,0,0.2);border:1px solid rgba(0,0,0,0.08);font-family:-apple-system,BlinkMacSystemFont,'SF Pro Text','Vazirmatn',sans-serif;">
                        <h4 style="margin:0 0 10px;font-size:15px;font-weight:700;color:#1d1d1f;">${message}</h4>
                        <textarea class="c2c-prompt-input" rows="3" style="width:100%;padding:10px 12px;background:#f5f5f7;border:1px solid rgba(0,0,0,0.1);border-radius:8px;margin-bottom:18px;outline:none;font-family:inherit;font-size:13px;color:#1d1d1f;" placeholder="علت عدم پذیرش رسید واریزی..."></textarea>
                        <div style="display:flex;gap:10px;justify-content:flex-end;">
                            <button class="c2c-cancel-btn" style="padding:8px 16px;border:1px solid rgba(0,0,0,0.12);background:#ffffff;border-radius:8px;cursor:pointer;font-size:13px;font-weight:500;color:#1d1d1f;font-family:inherit;">انصراف</button>
                            <button class="c2c-confirm-btn" style="padding:8px 16px;border:none;background:#ff3b30;color:#ffffff;border-radius:8px;cursor:pointer;font-weight:600;font-size:13px;font-family:inherit;">رد فیش</button>
                        </div>
                    </div>
                </div>
            `).appendTo('body');

            const $input = overlay.find('.c2c-prompt-input');
            $input.focus();
            overlay.find('.c2c-cancel-btn').on('click', () => { overlay.remove(); callback(null); });
            overlay.find('.c2c-confirm-btn').on('click', () => { overlay.remove(); callback($input.val() || 'رسید معتبر نیست'); });
        }

        // --- 4. AJAX Approve / Reject Actions ---
        $(document).on('click', '.c2c-btn-approve', function (e) {
            e.preventDefault();
            const $btn = $(this);
            const orderId = $btn.data('order-id');

            customConfirm('آیا از تایید این فیش واریزی و تغییر وضعیت سفارش به «در حال انجام» اطمینان دارید؟', 'تایید و ثبت', false, function (confirmed) {
                if (!confirmed) return;
                
                $btn.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> در حال تایید...');
                
                $.post(typeof wooC2cAdminParams !== 'undefined' ? wooC2cAdminParams.ajaxUrl : ajaxurl, {
                    action: 'woo_c2c_admin_approve',
                    order_id: orderId,
                    nonce: typeof wooC2cAdminParams !== 'undefined' ? wooC2cAdminParams.nonce : ''
                }, function (res) {
                    if (res.success) {
                        showAdminToast(res.data?.message || 'فیش پرداخت با موفقیت تایید شد.', 'success');
                        setTimeout(() => window.location.reload(), 900);
                    } else {
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> تایید فیش');
                        showAdminToast(res.data?.message || 'خطا در تایید فیش', 'error');
                    }
                }).fail(() => {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> تایید فیش');
                    showAdminToast('خطای ارتباط با سرور', 'error');
                });
            });
        });

        $(document).on('click', '.c2c-btn-reject', function (e) {
            e.preventDefault();
            const $btn = $(this);
            const orderId = $btn.data('order-id');

            customPrompt('دلیل رد فیش را مشخص کنید (به مشتری اطلاع داده خواهد شد):', function (reason) {
                if (reason === null) return;

                $btn.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> در حال رد فیش...');
                
                $.post(typeof wooC2cAdminParams !== 'undefined' ? wooC2cAdminParams.ajaxUrl : ajaxurl, {
                    action: 'woo_c2c_admin_reject',
                    order_id: orderId,
                    reason: reason,
                    nonce: typeof wooC2cAdminParams !== 'undefined' ? wooC2cAdminParams.nonce : ''
                }, function (res) {
                    if (res.success) {
                        showAdminToast(res.data?.message || 'فیش با موفقیت رد شد.', 'success');
                        setTimeout(() => window.location.reload(), 900);
                    } else {
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-no-alt"></span> رد فیش');
                        showAdminToast(res.data?.message || 'خطا در ثبت رد فیش', 'error');
                    }
                }).fail(() => {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-no-alt"></span> رد فیش');
                    showAdminToast('خطای ارتباط با سرور', 'error');
                });
            });
        });

    });
})(jQuery);
