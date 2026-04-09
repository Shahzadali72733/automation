<?php
if (!defined('ABSPATH')) exit;

class SD_Ajax_Handlers {

    public static function init() {
        add_action('wp_ajax_sd_claim_job', [__CLASS__, 'claim_job']);
        add_action('wp_ajax_sd_confirm_job', [__CLASS__, 'confirm_job']);
        add_action('wp_ajax_sd_complete_job', [__CLASS__, 'complete_job']);
        add_action('wp_ajax_sd_move_stage', [__CLASS__, 'move_stage']);
        add_action('wp_ajax_sd_admin_approve', [__CLASS__, 'admin_approve']);
        add_action('wp_ajax_sd_admin_reject', [__CLASS__, 'admin_reject']);
        add_action('wp_ajax_sd_admin_send_notes', [__CLASS__, 'admin_send_notes']);
    }

    public static function claim_job() {
        check_ajax_referer('sd_vendor_nonce', 'nonce');

        $job_id = intval($_POST['job_id'] ?? 0);
        $vendor_id = get_current_user_id();

        if (!$job_id || !$vendor_id) {
            wp_send_json_error(['message' => 'Invalid request.']);
        }

        $current_vendor = get_post_meta($job_id, '_sd_assigned_vendor', true);
        if ($current_vendor) {
            wp_send_json_error(['message' => 'This job has already been claimed.']);
        }

        $stage = get_post_meta($job_id, '_sd_stage', true);
        if ($stage !== 'posted-to-vendors') {
            wp_send_json_error(['message' => 'This job is no longer available.']);
        }

        update_post_meta($job_id, '_sd_assigned_vendor', $vendor_id);
        update_post_meta($job_id, '_sd_stage', 'claimed');

        $vendor = get_userdata($vendor_id);
        SD_Meta_Boxes::add_timeline_event($job_id, 'Job claimed by vendor: ' . $vendor->display_name, '#f97316');

        wp_send_json_success(['message' => 'Job claimed! Reply YES to confirm and get full details.']);
    }

    public static function confirm_job() {
        check_ajax_referer('sd_vendor_nonce', 'nonce');

        $job_id = intval($_POST['job_id'] ?? 0);
        $vendor_id = get_current_user_id();

        if (!$job_id || !$vendor_id) {
            wp_send_json_error(['message' => 'Invalid request.']);
        }

        $assigned = get_post_meta($job_id, '_sd_assigned_vendor', true);
        if ((int)$assigned !== $vendor_id) {
            wp_send_json_error(['message' => 'This job is not assigned to you.']);
        }

        $stage = get_post_meta($job_id, '_sd_stage', true);
        if ($stage !== 'claimed') {
            wp_send_json_error(['message' => 'This job cannot be confirmed at its current stage.']);
        }

        update_post_meta($job_id, '_sd_stage', 'scheduled');
        SD_Meta_Boxes::add_timeline_event($job_id, 'Vendor confirmed (YES) — job scheduled', '#06b6d4');

        wp_send_json_success(['message' => 'Confirmed! You are assigned to this job. Full details are now visible.']);
    }

    public static function complete_job() {
        check_ajax_referer('sd_vendor_nonce', 'nonce');

        $job_id = intval($_POST['job_id'] ?? 0);
        $vendor_id = get_current_user_id();

        if (!$job_id || !$vendor_id) {
            wp_send_json_error(['message' => 'Invalid request.']);
        }

        $assigned = get_post_meta($job_id, '_sd_assigned_vendor', true);
        if ((int)$assigned !== $vendor_id) {
            wp_send_json_error(['message' => 'This job is not assigned to you.']);
        }

        $stage = get_post_meta($job_id, '_sd_stage', true);
        if ($stage !== 'scheduled') {
            wp_send_json_error(['message' => 'This job cannot be marked complete at its current stage.']);
        }

        update_post_meta($job_id, '_sd_stage', 'completed-review');
        SD_Meta_Boxes::add_timeline_event($job_id, 'Vendor marked job as DONE — pending review', '#10b981');

        wp_send_json_success(['message' => 'Job marked complete! Admin will review and process your payout.']);
    }

    public static function move_stage() {
        check_ajax_referer('sd_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
        }

        $job_id = intval($_POST['job_id'] ?? 0);
        $new_stage = sanitize_text_field($_POST['new_stage'] ?? '');

        if (!$job_id || !array_key_exists($new_stage, SD_Post_Types::STAGES)) {
            wp_send_json_error(['message' => 'Invalid job or stage.']);
        }

        $old_stage = get_post_meta($job_id, '_sd_stage', true);
        update_post_meta($job_id, '_sd_stage', $new_stage);
        SD_Meta_Boxes::add_timeline_event(
            $job_id,
            'Stage changed: ' . SD_Post_Types::get_stage_label($old_stage) . ' → ' . SD_Post_Types::get_stage_label($new_stage),
            SD_Post_Types::get_stage_color($new_stage)
        );

        wp_send_json_success(['message' => 'Stage updated.']);
    }

    public static function admin_approve() {
        check_ajax_referer('sd_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Permission denied.']);

        $job_id = intval($_POST['job_id'] ?? 0);
        if (!$job_id) wp_send_json_error(['message' => 'Invalid job.']);

        update_post_meta($job_id, '_sd_stage', 'approved-priced');
        update_post_meta($job_id, '_sd_admin_status', 'approved');
        SD_Meta_Boxes::add_timeline_event($job_id, 'Admin approved the request', '#8b5cf6');

        wp_send_json_success(['message' => 'Request approved. You can now post it to vendors.']);
    }

    public static function admin_reject() {
        check_ajax_referer('sd_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Permission denied.']);

        $job_id = intval($_POST['job_id'] ?? 0);
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');
        if (!$job_id) wp_send_json_error(['message' => 'Invalid job.']);

        update_post_meta($job_id, '_sd_admin_status', 'rejected');
        if ($reason) update_post_meta($job_id, '_sd_admin_notes', $reason);
        SD_Meta_Boxes::add_timeline_event($job_id, 'Admin rejected the request' . ($reason ? ': ' . $reason : ''), '#ef4444');

        wp_send_json_success(['message' => 'Request rejected.']);
    }

    public static function admin_send_notes() {
        check_ajax_referer('sd_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Permission denied.']);

        $job_id = intval($_POST['job_id'] ?? 0);
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        if (!$job_id || !$notes) wp_send_json_error(['message' => 'Job ID and notes are required.']);

        update_post_meta($job_id, '_sd_admin_status', 'needs-revision');
        update_post_meta($job_id, '_sd_admin_notes', $notes);
        SD_Meta_Boxes::add_timeline_event($job_id, 'Admin sent notes to client: ' . wp_trim_words($notes, 15), '#f59e0b');

        wp_send_json_success(['message' => 'Notes sent to client.']);
    }
}
