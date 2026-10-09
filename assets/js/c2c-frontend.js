/**
 * Frontend JavaScript for Card-to-Card Pro Gateway
 * Apple Human Interface Design Edition
 */
(function ($) {
    'use strict';

    $(document).ready(function () {
        
        // --- 1. Dynamic Island / iOS Style Floating Toast ---
        function showToast(msg, type = 'success') {
            let $container = $('.c2c-toast-container');
            if ($container.length === 0) {
                $container = $('<div class="c2c-toast-container"></div>').appendTo('body');
            }
            
            // Apple SF Style Icons
            const icon = type === 'success' 
                ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>'
                : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';
            
            const $toast = $(`<div class="c2c-toast ${type}">${icon} <span>${msg}</span></div>`);
            $container.append($toast);
            
            // Trigger Apple spring animation
            requestAnimationFrame(() => {
                setTimeout(() => $toast.addClass('show'), 10);
            });
            
            setTimeout(() => {
                $toast.removeClass('show');
                setTimeout(() => $toast.remove(), 400);
            }, 3500);
        }

        // --- 2. Tactile Clipboard Copy API ---
        $(document).on('click', '[data-clipboard]', function (e) {
            e.preventDefault();
            const $btn = $(this);
            const textToCopy = $btn.attr('data-clipboard');
            if (!textToCopy) return;

            const isCard = $btn.hasClass('c2c-copy-card-btn');
            const isShaba = $btn.hasClass('c2c-copy-shaba-btn');
            const successMsg = isCard ? 'شماره کارت کپی شد' : (isShaba ? 'شماره شبا کپی شد' : 'مبلغ کپی شد');

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(textToCopy).then(() => {
                    handleCopySuccess($btn, successMsg);
                }).catch(() => {
                    fallbackCopy(textToCopy, $btn, successMsg);
                });
            } else {
                fallbackCopy(textToCopy, $btn, successMsg);
            }
        });

        function fallbackCopy(text, $btn, successMsg) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.opacity = '0';
            textArea.style.pointerEvents = 'none';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    handleCopySuccess($btn, successMsg);
                } else {
                    showToast('خطا در کپی متن', 'error');
                }
            } catch (err) {
                showToast('مرورگر اجازه کپی خودکار نمی‌دهد', 'error');
            }
            document.body.removeChild(textArea);
        }

        function handleCopySuccess($btn, successMsg) {
            $btn.addClass('copied');
            const originalContent = $btn.html();
            
            // Checkmark feedback
            $btn.html('<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>');
            showToast(successMsg, 'success');
            
            setTimeout(() => {
                $btn.html(originalContent);
                $btn.removeClass('copied');
            }, 1800);
        }

        // --- 3. Realistic Physical Tilt & Dynamic Specular Reflection ---
        const $card = $('.c2c-bank-card');
        if ($card.length) {
            let reqId = null;

            $card.on('mousemove', function (e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const percentX = (x / rect.width) * 100;
                const percentY = (y / rect.height) * 100;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                // Subtle, realistic tilt (max ~5 degrees, physical feel)
                const rotateX = ((y - centerY) / centerY) * -5.5;
                const rotateY = ((x - centerX) / centerX) * 5.5;
                
                if (reqId) cancelAnimationFrame(reqId);
                reqId = requestAnimationFrame(() => {
                    this.style.setProperty('--sheen-x', `${percentX}%`);
                    this.style.setProperty('--sheen-y', `${percentY}%`);
                    this.style.transform = `perspective(1000px) rotateX(${rotateX.toFixed(2)}deg) rotateY(${rotateY.toFixed(2)}deg) scale3d(1.01, 1.01, 1.01)`;
                });
            });

            $card.on('mouseleave', function () {
                if (reqId) cancelAnimationFrame(reqId);
                this.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)';
            });
        }

        // --- 4. Apple-style Countdown Timer ---
        const $timerWrapper = $('.c2c-timer-wrapper');
        if ($timerWrapper.length) {
            const deadline = parseInt($timerWrapper.data('deadline'), 10);
            const $capsule = $timerWrapper.find('.c2c-timer-capsule');
            const $min = $timerWrapper.find('.c2c-timer-min');
            const $sec = $timerWrapper.find('.c2c-timer-sec');
            
            const timerInterval = setInterval(() => {
                const now = Math.floor(Date.now() / 1000);
                const diff = deadline - now;
                
                if (diff <= 0) {
                    clearInterval(timerInterval);
                    $min.text('00');
                    $sec.text('00');
                    $capsule.removeClass('urgent').addClass('expired').html('<span>مهلت پرداخت به پایان رسیده است.</span>');
                    $('#c2c-submit-btn').prop('disabled', true);
                    return;
                }
                
                if (diff < 300) { // 5 minutes remaining
                    $capsule.addClass('urgent');
                }
                
                const m = Math.floor(diff / 60);
                const s = diff % 60;
                
                $min.text(m.toString().padStart(2, '0'));
                $sec.text(s.toString().padStart(2, '0'));
            }, 1000);
        }

        // --- 5. AirDrop / Files Drag & Drop File Upload ---
        const $dropzone = $('#c2c-dropzone');
        const $fileInput = $('#c2c-file-input');
        if ($dropzone.length) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                $dropzone.on(eventName, e => {
                    e.preventDefault();
                    e.stopPropagation();
                });
            });

            $dropzone.on('dragover', () => $dropzone.addClass('dragover'));
            $dropzone.on('dragleave drop', () => $dropzone.removeClass('dragover'));
            $dropzone.on('drop', e => {
                const files = e.originalEvent.dataTransfer.files;
                if (files.length) {
                    $fileInput[0].files = files;
                    handleFileSelection(files[0]);
                }
            });

            $fileInput.on('change', function () {
                if (this.files.length) handleFileSelection(this.files[0]);
            });

            $('#c2c-remove-file').on('click', function (e) {
                e.preventDefault();
                $fileInput.val('');
                $('#c2c-file-preview-box').addClass('c2c-hidden');
                $('.c2c-dropzone-content').removeClass('c2c-hidden');
            });
        }

        function handleFileSelection(file) {
            const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                showToast('تنها تصاویر با فرمت JPG، PNG یا WEBP مجاز هستند.', 'error');
                return;
            }
            if (file.size > 6 * 1024 * 1024) {
                showToast('حجم فایل بیشتر از سقف مجاز ۶ مگابایت است.', 'error');
                return;
            }

            const reader = new FileReader();
            reader.onload = e => {
                $('#c2c-img-preview').attr('src', e.target.result);
                $('#c2c-filename').text(file.name);
                
                const sizeInMb = (file.size / (1024 * 1024)).toFixed(2);
                $('#c2c-filesize').text(sizeInMb + ' مگابایت');
                
                $('.c2c-dropzone-content').addClass('c2c-hidden');
                $('#c2c-file-preview-box').removeClass('c2c-hidden');
                
                // Subtle smooth progress indicator
                $('#c2c-progress-bar').css('width', '0%');
                setTimeout(() => $('#c2c-progress-bar').css('width', '100%'), 120);
            };
            reader.readAsDataURL(file);
        }

        // --- 6. Form Submission (Clean, Professional Feedback) ---
        $('#c2c-receipt-upload-form').on('submit', function (e) {
            e.preventDefault();
            const form = this;
            const file = $fileInput[0].files[0];

            if (!file) {
                showToast('لطفاً ابتدا تصویر فیش یا رسید را انتخاب فرمایید.', 'error');
                return;
            }

            const formData = new FormData(form);
            formData.append('action', 'woo_c2c_upload_receipt');
            formData.append('nonce', typeof wooC2cParams !== 'undefined' ? wooC2cParams.nonce : '');

            const $btn = $('#c2c-submit-btn');
            const $spinner = $btn.find('.c2c-spinner');
            const $arrow = $btn.find('.c2c-btn-arrow');
            const $text = $btn.find('.c2c-btn-text');
            
            $btn.prop('disabled', true);
            $arrow.addClass('c2c-hidden');
            $spinner.removeClass('c2c-hidden');
            $text.text('در حال ثبت رسید...');

            $.ajax({
                url: typeof wooC2cParams !== 'undefined' ? wooC2cParams.ajaxUrl : '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (res) {
                    if (res.success) {
                        showToast(res.data?.message || 'رسید پرداخت با موفقیت ثبت شد.', 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        resetBtn();
                        showToast(res.data?.message || 'خطا در ثبت رسید. لطفاً مجدداً امتحان کنید.', 'error');
                    }
                },
                error: function (xhr) {
                    resetBtn();
                    let msg = 'خطای اتصال به سرور. اینترنت خود را بررسی کنید.';
                    if (xhr.responseJSON?.data?.message) {
                        msg = xhr.responseJSON.data.message;
                    }
                    showToast(msg, 'error');
                }
            });

            function resetBtn() {
                $btn.prop('disabled', false);
                $spinner.addClass('c2c-hidden');
                $arrow.removeClass('c2c-hidden');
                $text.text('ثبت نهایی رسید پرداخت');
            }
        });
    });
})(jQuery);
