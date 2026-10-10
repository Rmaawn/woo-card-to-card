/**
 * Frontend JavaScript for woo-card-to-card
 */
(function($) {
    'use strict';

    $(document).ready(function() {

        // Toast Notification
        function showToast(message) {
            $('.woo-c2c-toast').remove();
            const $toast = $('<div class="woo-c2c-toast"></div>').text(message).appendTo('body');
            
            requestAnimationFrame(() => {
                setTimeout(() => $toast.addClass('show'), 10);
            });

            setTimeout(() => {
                $toast.removeClass('show');
                setTimeout(() => $toast.remove(), 300);
            }, 3000);
        }

        // Copy to clipboard
        $('.woo-c2c-copy-btn').on('click', function(e) {
            e.preventDefault();
            const textToCopy = $(this).data('clipboard');
            
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(textToCopy).then(() => {
                    showToast('با موفقیت کپی شد');
                });
            } else {
                // Fallback
                const textArea = document.createElement("textarea");
                textArea.value = textToCopy;
                textArea.style.position = "absolute";
                textArea.style.left = "-999999px";
                document.body.prepend(textArea);
                textArea.select();
                try {
                    document.execCommand('copy');
                    showToast('با موفقیت کپی شد');
                } catch (error) {
                    showToast('خطا در کپی');
                } finally {
                    textArea.remove();
                }
            }
        });

        // Countdown Timer
        const $timerBox = $('.woo-c2c-timer-box');
        if ($timerBox.length) {
            const deadline = parseInt($timerBox.data('deadline'), 10);
            
            if (deadline && !isNaN(deadline)) {
                $timerBox.show();
                const $minEl = $timerBox.find('.woo-c2c-min');
                const $secEl = $timerBox.find('.woo-c2c-sec');

                const updateTimer = setInterval(() => {
                    const now = Math.floor(Date.now() / 1000);
                    const diff = deadline - now;

                    if (diff <= 0) {
                        clearInterval(updateTimer);
                        $minEl.text('00');
                        $secEl.text('00');
                        $timerBox.html('<span style="color: #e53e3e; font-weight: bold;">مهلت پرداخت به پایان رسیده است.</span>');
                        $('#woo-c2c-upload-form input, #woo-c2c-upload-form button').prop('disabled', true);
                        return;
                    }

                    const m = Math.floor(diff / 60);
                    const s = diff % 60;
                    
                    $minEl.text(m < 10 ? '0' + m : m);
                    $secEl.text(s < 10 ? '0' + s : s);
                }, 1000);
            }
        }

        // Drag and Drop & File Preview
        const $dropzone = $('#woo-c2c-dropzone');
        const $fileInput = $('#woo-c2c-file-input');
        const $previewBox = $('#woo-c2c-preview-box');
        const $imgPreview = $('#woo-c2c-img-preview');
        const $filePrompt = $('.woo-c2c-file-prompt');
        const $removeBtn = $('#woo-c2c-remove-file');

        $fileInput.on('dragenter dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $dropzone.addClass('dragover');
        });

        $fileInput.on('dragleave drop', function(e) {
            $dropzone.removeClass('dragover');
        });

        $fileInput.on('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (!file.type.match('image.*')) {
                    showToast('فقط فایل تصویری مجاز است');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    $imgPreview.attr('src', e.target.result);
                    $filePrompt.hide();
                    $previewBox.show();
                    // Move input down so it doesn't block remove button
                    $fileInput.css('z-index', '1');
                }
                reader.readAsDataURL(file);
            }
        });

        $removeBtn.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $fileInput.val('');
            $imgPreview.attr('src', '');
            $previewBox.hide();
            $filePrompt.show();
            $fileInput.css('z-index', '10');
        });

        // Form Submission
        $('#woo-c2c-upload-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $btn = $('#woo-c2c-submit-btn');
            const $progress = $('#woo-c2c-progress-bar');
            const file = $fileInput[0].files[0];

            if (!file) {
                showToast('لطفا تصویر فیش را انتخاب کنید');
                return;
            }

            const formData = new FormData(this);
            formData.append('action', 'woo_c2c_upload_receipt');
            
            if (typeof wooC2cParams !== 'undefined' && wooC2cParams.nonce) {
                formData.append('nonce', wooC2cParams.nonce);
            }

            $btn.prop('disabled', true).text('در حال ارسال...');
            
            $.ajax({
                url: typeof wooC2cParams !== 'undefined' ? wooC2cParams.ajaxUrl : '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    const xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener("progress", function(evt) {
                        if (evt.lengthComputable) {
                            const percentComplete = (evt.loaded / evt.total) * 100;
                            $progress.width(percentComplete + '%');
                        }
                    }, false);
                    return xhr;
                },
                success: function(res) {
                    if (res.success) {
                        showToast(res.data?.message || 'فیش با موفقیت ارسال شد');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showToast(res.data?.message || 'خطا در ارسال فیش');
                        $btn.prop('disabled', false).text('ارسال فیش پرداخت');
                        $progress.width('0%');
                    }
                },
                error: function(xhr) {
                    let msg = 'خطای ارتباط با سرور';
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        msg = xhr.responseJSON.data.message;
                    } else if (xhr.responseText) {
                        if (xhr.responseText === '-1' || xhr.responseText === '0') {
                            msg = 'نشست امنیتی منقضی شده است. لطفاً صفحه را تازه‌سازی کنید.';
                        }
                    }
                    showToast(msg);
                    $btn.prop('disabled', false).text('ارسال فیش پرداخت');
                    $progress.width('0%');
                }
            });
        });

    });

})(jQuery);
