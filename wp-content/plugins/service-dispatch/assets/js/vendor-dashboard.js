/**
 * Service Dispatch — Vendor Dashboard JS
 * Handles claim, confirm, and complete actions
 */
(function ($) {
    'use strict';

    $(function () {
        initClaimButtons();
        initConfirmButtons();
        initCompleteButtons();
    });

    function initClaimButtons() {
        $(document).on('click', '.sd-claim-btn', function () {
            var $btn = $(this);
            var jobId = $btn.data('job-id');
            if (!confirm('Are you sure you want to claim this job?')) return;

            $btn.prop('disabled', true).text('Claiming...');

            $.ajax({
                url: sdVendor.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sd_claim_job',
                    nonce: sdVendor.nonce,
                    job_id: jobId,
                },
                success: function (res) {
                    if (res.success) {
                        showToast(res.data.message, 'success');
                        setTimeout(function () { location.reload(); }, 1500);
                    } else {
                        showToast(res.data.message || 'Could not claim job.', 'error');
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-flag"></span> Claim This Job');
                    }
                },
                error: function () {
                    showToast('Network error. Please try again.', 'error');
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-flag"></span> Claim This Job');
                },
            });
        });
    }

    function initConfirmButtons() {
        $(document).on('click', '.sd-confirm-btn', function () {
            var $btn = $(this);
            var jobId = $btn.data('job-id');

            $btn.prop('disabled', true).text('Confirming...');

            $.ajax({
                url: sdVendor.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sd_confirm_job',
                    nonce: sdVendor.nonce,
                    job_id: jobId,
                },
                success: function (res) {
                    if (res.success) {
                        showToast(res.data.message, 'success');
                        setTimeout(function () { location.reload(); }, 1500);
                    } else {
                        showToast(res.data.message || 'Could not confirm job.', 'error');
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Confirm (YES)');
                    }
                },
                error: function () {
                    showToast('Network error. Please try again.', 'error');
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Confirm (YES)');
                },
            });
        });
    }

    function initCompleteButtons() {
        $(document).on('click', '.sd-complete-btn', function () {
            var $btn = $(this);
            var jobId = $btn.data('job-id');
            if (!confirm('Mark this job as complete?')) return;

            $btn.prop('disabled', true).text('Completing...');

            $.ajax({
                url: sdVendor.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sd_complete_job',
                    nonce: sdVendor.nonce,
                    job_id: jobId,
                },
                success: function (res) {
                    if (res.success) {
                        showToast(res.data.message, 'success');
                        setTimeout(function () { location.reload(); }, 1500);
                    } else {
                        showToast(res.data.message || 'Could not complete job.', 'error');
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-yes-alt"></span> Mark Complete (DONE)');
                    }
                },
                error: function () {
                    showToast('Network error. Please try again.', 'error');
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-yes-alt"></span> Mark Complete (DONE)');
                },
            });
        });
    }

    function showToast(message, type) {
        var bg = type === 'success' ? '#22c55e' : '#ef4444';
        var $toast = $(
            '<div style="' +
                'position:fixed;top:20px;right:20px;z-index:999999;' +
                'background:' + bg + ';color:white;padding:14px 24px;' +
                'border-radius:10px;font-size:14px;font-weight:500;' +
                'box-shadow:0 8px 24px rgba(0,0,0,0.2);' +
                'opacity:0;transform:translateY(-10px);transition:all 0.3s;">' +
                message +
                '</div>'
        );
        $('body').append($toast);
        setTimeout(function () {
            $toast.css({ opacity: 1, transform: 'translateY(0)' });
        }, 10);
        setTimeout(function () {
            $toast.css({ opacity: 0, transform: 'translateY(-10px)' });
            setTimeout(function () { $toast.remove(); }, 300);
        }, 3000);
    }
})(jQuery);
