/**
 * Service Dispatch — Admin Dashboard JS
 * Handles pipeline drag-and-drop and admin interactions
 */
(function ($) {
    'use strict';

    $(function () {
        initPipelineSortable();
        initStageRadios();
        initRefreshButton();
    });

    function initPipelineSortable() {
        if (!$('.sd-sortable').length) return;

        $('.sd-sortable').sortable({
            connectWith: '.sd-sortable',
            placeholder: 'ui-sortable-placeholder',
            handle: '.sd-pipeline-card',
            tolerance: 'pointer',
            cursor: 'grabbing',
            opacity: 0.8,
            revert: 150,
            receive: function (event, ui) {
                var jobId = ui.item.data('job-id');
                var newStage = $(this).data('stage');

                $.ajax({
                    url: sdAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'sd_move_stage',
                        nonce: sdAdmin.nonce,
                        job_id: jobId,
                        new_stage: newStage,
                    },
                    success: function (res) {
                        if (res.success) {
                            updateColumnCounts();
                            showToast('Stage updated', 'success');
                        } else {
                            showToast(res.data.message || 'Error updating stage', 'error');
                            $(ui.sender).sortable('cancel');
                        }
                    },
                    error: function () {
                        showToast('Network error', 'error');
                        $(ui.sender).sortable('cancel');
                    },
                });
            },
        });
    }

    function initStageRadios() {
        $('input[name="sd_stage"]').on('change', function () {
            $('.sd-stage-option').removeClass('active');
            $(this).closest('.sd-stage-option').addClass('active');
        });
    }

    function initRefreshButton() {
        $('#sd-refresh-pipeline').on('click', function () {
            location.reload();
        });
    }

    function updateColumnCounts() {
        $('.sd-pipeline-column').each(function () {
            var count = $(this).find('.sd-pipeline-card').length;
            $(this).find('.sd-column-count').text(count);
        });
    }

    function showToast(message, type) {
        var bg = type === 'success' ? '#22c55e' : '#ef4444';
        var $toast = $(
            '<div class="sd-toast" style="' +
                'position:fixed;top:40px;right:20px;z-index:999999;' +
                'background:' + bg + ';color:white;padding:12px 20px;' +
                'border-radius:8px;font-size:14px;font-weight:500;' +
                'box-shadow:0 4px 16px rgba(0,0,0,0.15);' +
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
