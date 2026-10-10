/**
 * Admin JavaScript for woo-card-to-card
 */
(function ($) {
    'use strict';

    $(document).ready(function () {

        function showAdminToast(msg, type = 'success') {
            $('.c2c-admin-toast').remove();
            const icon = type === 'success' ? 'yes' : 'no-alt';
            const iconColor = type === 'success' ? '#007017' : '#d63638';
            
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

        $(document).on('click', '.c2c-btn-approve', function (e) {
            e.preventDefault();
            const $btn = $(this);
            const orderId = $btn.data('order-id');

            if (!confirm('آیا از تایید این فیش واریزی و تغییر وضعیت سفارش به «در حال انجام» اطمینان دارید؟')) {
                return;
            }
                
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

        $(document).on('click', '.c2c-btn-reject', function (e) {
            e.preventDefault();
            const $btn = $(this);
            const orderId = $btn.data('order-id');

            const reason = prompt('دلیل رد فیش را مشخص کنید (به مشتری اطلاع داده خواهد شد):', 'رسید معتبر نیست');
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
})(jQuery);
