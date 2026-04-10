/**
 * Service Request — success modal after Fluent Forms AJAX submit
 */
(function ($) {
    'use strict';

    function isServiceRequestSubmit(settings) {
        if (!settings || !settings.data || typeof settings.data !== 'string') {
            return false;
        }
        if (settings.data.indexOf('fluentform_submit') === -1) {
            return false;
        }
        var fid = String(sdSrForm.formId);
        var raw = settings.data;
        try {
            raw = decodeURIComponent(raw.replace(/\+/g, ' '));
        } catch (e) {}
        return (
            raw.indexOf('form_id=' + fid) !== -1 ||
            raw.indexOf('form_id=' + fid + '&') !== -1 ||
            raw.indexOf('data[form_id]=' + fid) !== -1
        );
    }

    function showSuccessModal() {
        var $root = $('#sd-sr-modal-root');
        if (!$root.length) {
            return;
        }
        $root.find('.sd-sr-modal-title').text(sdSrForm.title);
        $root.find('.sd-sr-modal-message').html('<p>' + $('<div/>').text(sdSrForm.message).html() + '</p>');
        $root.find('.sd-sr-modal-home').attr('href', sdSrForm.homeUrl).text(sdSrForm.homeLabel);
        $root.removeAttr('hidden').addClass('is-open');
        $('body').addClass('sd-sr-modal-open');
    }

    function closeModal() {
        var $root = $('#sd-sr-modal-root');
        $root.removeClass('is-open').attr('hidden', 'hidden');
        $('body').removeClass('sd-sr-modal-open');
    }

    $(document).on('click', '.sd-sr-modal-backdrop, .sd-sr-modal-close', closeModal);
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && $('#sd-sr-modal-root').hasClass('is-open')) {
            closeModal();
        }
    });

    $(document).ajaxComplete(function (event, xhr, settings) {
        if (!isServiceRequestSubmit(settings)) {
            return;
        }
        var json;
        try {
            json = JSON.parse(xhr.responseText);
        } catch (err) {
            return;
        }
        if (!json || !json.success || !json.data) {
            return;
        }
        var res = json.data.result;
        if (!res || res.action !== 'hide_form') {
            return;
        }
        // Hide any inline success block Fluent Forms may inject
        $('.frm-fluent-form')
            .closest('.fluentform')
            .find('.ff-el-success-message, .ff_msg_success, .ff_form_success')
            .hide();
        showSuccessModal();
    });
})(jQuery);
