/**
 * Service Dispatch — Vendor Dashboard JS
 * Job details modal, claim, claim+YES, confirm, complete
 */
(function ($) {
    'use strict';

    var $overlay = null;
    var currentJobId = null;

    $(function () {
        initClaimButtons();
        initClaimYesButtons();
        initConfirmButtons();
        initCompleteButtons();
        initDetailsButtons();
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    });

    function ensureModalShell() {
        if ($overlay && $overlay.length) {
            return $overlay;
        }
        $overlay = $(
            '<div class="sd-job-modal-overlay" role="dialog" aria-modal="true" aria-hidden="true" style="display:none">' +
                '<div class="sd-job-modal">' +
                '<div class="sd-job-modal-header">' +
                '<h2 class="sd-job-modal-title"></h2>' +
                '<button type="button" class="sd-job-modal-close" aria-label="Close">&times;</button>' +
                '</div>' +
                '<div class="sd-job-modal-meta"></div>' +
                '<div class="sd-job-modal-body"></div>' +
                '<div class="sd-job-modal-footer"></div>' +
                '</div>' +
                '</div>'
        );
        $('body').append($overlay);
        $overlay.on('click', function (e) {
            if (e.target === $overlay[0]) {
                closeModal();
            }
        });
        $overlay.find('.sd-job-modal-close').on('click', closeModal);
        $overlay.on('click', '.sd-job-modal', function (e) {
            e.stopPropagation();
        });
        return $overlay;
    }

    function openModal(jobId) {
        currentJobId = jobId;
        var $el = ensureModalShell();
        $el.find('.sd-job-modal-title').text('Job #' + jobId);
        $el.find('.sd-job-modal-meta').empty();
        $el.find('.sd-job-modal-body').html('<div class="sd-job-modal-loading">Loading…</div>');
        $el.find('.sd-job-modal-footer').empty();
        $el.css('display', 'flex');
        requestAnimationFrame(function () {
            $el.addClass('sd-open');
        });
        $el.attr('aria-hidden', 'false');

        $.ajax({
            url: sdVendor.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sd_get_job_details',
                nonce: sdVendor.nonce,
                job_id: jobId,
            },
            success: function (res) {
                if (!res.success) {
                    $el.find('.sd-job-modal-body').html(
                        '<div class="sd-job-modal-error">' + escapeHtml(res.data && res.data.message ? res.data.message : 'Could not load job.') + '</div>'
                    );
                    return;
                }
                renderModalContent(res.data);
            },
            error: function () {
                $el.find('.sd-job-modal-body').html('<div class="sd-job-modal-error">Network error.</div>');
            },
        });
    }

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function renderModalContent(d) {
        var $el = ensureModalShell();
        $el.find('.sd-job-modal-title').text(d.title ? d.title : 'Job #' + d.id);
        $el.find('.sd-job-modal-meta').text(d.stage_label || '');

        var bodyHtml = '';
        if (d.description) {
            bodyHtml += '<p class="sd-job-modal-desc">' + escapeHtml(d.description) + '</p>';
        }
        if (d.rows && d.rows.length) {
            bodyHtml += '<table class="sd-job-modal-table">';
            d.rows.forEach(function (row) {
                var v = row.value !== undefined && row.value !== null && String(row.value).trim() !== '' ? String(row.value) : '—';
                bodyHtml +=
                    '<tr><th>' + escapeHtml(row.label) + '</th><td>' + escapeHtml(v) + '</td></tr>';
            });
            bodyHtml += '</table>';
        }
        if (d.photos && d.photos.length) {
            bodyHtml += '<div class="sd-job-modal-files"><h4>Photos</h4><div class="sd-job-modal-photos">';
            d.photos.forEach(function (url) {
                bodyHtml +=
                    '<a href="' + escapeHtml(url) + '" target="_blank" rel="noopener noreferrer"><img src="' + escapeHtml(url) + '" alt="" /></a>';
            });
            bodyHtml += '</div></div>';
        }
        if (d.documents && d.documents.length) {
            bodyHtml += '<div class="sd-job-modal-files"><h4>Documents</h4><ul>';
            d.documents.forEach(function (url, i) {
                bodyHtml +=
                    '<li><a href="' + escapeHtml(url) + '" target="_blank" rel="noopener noreferrer">File ' + (i + 1) + '</a></li>';
            });
            bodyHtml += '</ul></div>';
        }
        $el.find('.sd-job-modal-body').html(bodyHtml);

        var $foot = $el.find('.sd-job-modal-footer');
        $foot.empty();

        if (d.can_claim) {
            $foot.append(
                '<button type="button" class="sd-btn sd-btn-primary sd-modal-claim" data-job-id="' +
                    d.id +
                    '"><span class="dashicons dashicons-flag"></span> Claim</button>'
            );
            $foot.append(
                '<button type="button" class="sd-btn sd-btn-success sd-modal-claim-yes" data-job-id="' +
                    d.id +
                    '"><span class="dashicons dashicons-yes"></span> Claim &amp; confirm YES</button>'
            );
        }
        if (d.can_confirm) {
            $foot.append(
                '<button type="button" class="sd-btn sd-btn-primary sd-modal-confirm" data-job-id="' +
                    d.id +
                    '"><span class="dashicons dashicons-yes"></span> Confirm (YES)</button>'
            );
        }
        if (d.can_complete) {
            $foot.append(
                '<button type="button" class="sd-btn sd-btn-success sd-modal-complete" data-job-id="' +
                    d.id +
                    '"><span class="dashicons dashicons-yes-alt"></span> Mark complete</button>'
            );
        }
        $foot.append(
            '<button type="button" class="sd-btn sd-btn-secondary sd-modal-close-only">Close</button>'
        );
        $foot.find('.sd-modal-close-only').on('click', closeModal);

        $foot.find('.sd-modal-claim').on('click', function () {
            var id = $(this).data('job-id');
            if (!confirm('Claim this job? You will still need to confirm YES to finalize.')) return;
            runAjaxAction('sd_claim_job', id, $(this), 'Claiming…');
        });
        $foot.find('.sd-modal-claim-yes').on('click', function () {
            var id = $(this).data('job-id');
            if (!confirm('Claim and confirm YES now? The client and admin will see you as the assigned provider.')) return;
            runAjaxAction('sd_claim_and_confirm_job', id, $(this), 'Saving…');
        });
        $foot.find('.sd-modal-confirm').on('click', function () {
            var id = $(this).data('job-id');
            runAjaxAction('sd_confirm_job', id, $(this), 'Confirming…');
        });
        $foot.find('.sd-modal-complete').on('click', function () {
            var id = $(this).data('job-id');
            if (!confirm('Mark this job as complete?')) return;
            runAjaxAction('sd_complete_job', id, $(this), 'Completing…');
        });
    }

    function runAjaxAction(action, jobId, $btn, pendingLabel) {
        var html = $btn.html();
        $btn.prop('disabled', true).html(pendingLabel);
        $.ajax({
            url: sdVendor.ajaxUrl,
            type: 'POST',
            data: {
                action: action,
                nonce: sdVendor.nonce,
                job_id: jobId,
            },
            success: function (res) {
                if (res.success) {
                    showToast(res.data.message, 'success');
                    closeModal();
                    setTimeout(function () {
                        location.reload();
                    }, 900);
                } else {
                    showToast(res.data.message || 'Action failed.', 'error');
                    $btn.prop('disabled', false).html(html);
                }
            },
            error: function () {
                showToast('Network error.', 'error');
                $btn.prop('disabled', false).html(html);
            },
        });
    }

    function closeModal() {
        if (!$overlay || !$overlay.length) return;
        $overlay.removeClass('sd-open');
        $overlay.attr('aria-hidden', 'true');
        setTimeout(function () {
            $overlay.css('display', 'none');
        }, 200);
        currentJobId = null;
    }

    function initDetailsButtons() {
        $(document).on('click', '.sd-job-details-btn', function () {
            var id = $(this).data('job-id');
            if (id) openModal(parseInt(id, 10));
        });
    }

    function initClaimButtons() {
        $(document).on('click', '.sd-claim-btn', function () {
            var $btn = $(this);
            var jobId = $btn.data('job-id');
            if (!confirm('Claim this job? You will need to confirm YES afterward so the client sees you as assigned.')) return;

            var html = $btn.html();
            $btn.prop('disabled', true).html('Claiming…');

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
                        setTimeout(function () {
                            location.reload();
                        }, 1500);
                    } else {
                        showToast(res.data.message || 'Could not claim job.', 'error');
                        $btn.prop('disabled', false).html(html);
                    }
                },
                error: function () {
                    showToast('Network error. Please try again.', 'error');
                    $btn.prop('disabled', false).html(html);
                },
            });
        });
    }

    function initClaimYesButtons() {
        $(document).on('click', '.sd-claim-yes-btn', function () {
            var $btn = $(this);
            var jobId = $btn.data('job-id');
            if (!confirm('Claim and confirm YES now? The client and admin will see you as the assigned provider immediately.')) return;

            var html = $btn.html();
            $btn.prop('disabled', true).html('Saving…');

            $.ajax({
                url: sdVendor.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sd_claim_and_confirm_job',
                    nonce: sdVendor.nonce,
                    job_id: jobId,
                },
                success: function (res) {
                    if (res.success) {
                        showToast(res.data.message, 'success');
                        setTimeout(function () {
                            location.reload();
                        }, 1500);
                    } else {
                        showToast(res.data.message || 'Could not complete action.', 'error');
                        $btn.prop('disabled', false).html(html);
                    }
                },
                error: function () {
                    showToast('Network error. Please try again.', 'error');
                    $btn.prop('disabled', false).html(html);
                },
            });
        });
    }

    function initConfirmButtons() {
        $(document).on('click', '.sd-confirm-btn', function () {
            var $btn = $(this);
            var jobId = $btn.data('job-id');
            var html = $btn.html();
            $btn.prop('disabled', true).html('Confirming…');

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
                        setTimeout(function () {
                            location.reload();
                        }, 1500);
                    } else {
                        showToast(res.data.message || 'Could not confirm job.', 'error');
                        $btn.prop('disabled', false).html(html);
                    }
                },
                error: function () {
                    showToast('Network error. Please try again.', 'error');
                    $btn.prop('disabled', false).html(html);
                },
            });
        });
    }

    function initCompleteButtons() {
        $(document).on('click', '.sd-complete-btn', function () {
            var $btn = $(this);
            var jobId = $btn.data('job-id');
            if (!confirm('Mark this job as complete?')) return;

            var html = $btn.html();
            $btn.prop('disabled', true).html('Completing…');

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
                        setTimeout(function () {
                            location.reload();
                        }, 1500);
                    } else {
                        showToast(res.data.message || 'Could not complete job.', 'error');
                        $btn.prop('disabled', false).html(html);
                    }
                },
                error: function () {
                    showToast('Network error. Please try again.', 'error');
                    $btn.prop('disabled', false).html(html);
                },
            });
        });
    }

    function showToast(message, type) {
        var bg = type === 'success' ? '#22c55e' : '#ef4444';
        var $toast = $(
            '<div style="' +
                'position:fixed;top:20px;right:20px;z-index:9999999;' +
                'background:' +
                bg +
                ';color:white;padding:14px 24px;' +
                'border-radius:10px;font-size:14px;font-weight:500;' +
                'box-shadow:0 8px 24px rgba(0,0,0,0.2);' +
                'opacity:0;transform:translateY(-10px);transition:all 0.3s;">' +
                escapeHtml(message) +
                '</div>'
        );
        $('body').append($toast);
        setTimeout(function () {
            $toast.css({ opacity: 1, transform: 'translateY(0)' });
        }, 10);
        setTimeout(function () {
            $toast.css({ opacity: 0, transform: 'translateY(-10px)' });
            setTimeout(function () {
                $toast.remove();
            }, 300);
        }, 3200);
    }
})(jQuery);
