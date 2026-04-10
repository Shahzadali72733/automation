<?php
if (!defined('ABSPATH')) exit;

class SD_Ajax_Handlers {

    public static function init() {
        add_action('wp_ajax_sd_get_job_details', [__CLASS__, 'get_job_details']);
        add_action('wp_ajax_sd_claim_job', [__CLASS__, 'claim_job']);
        add_action('wp_ajax_sd_claim_and_confirm_job', [__CLASS__, 'claim_and_confirm_job']);
        add_action('wp_ajax_sd_confirm_job', [__CLASS__, 'confirm_job']);
        add_action('wp_ajax_sd_complete_job', [__CLASS__, 'complete_job']);
        add_action('wp_ajax_sd_move_stage', [__CLASS__, 'move_stage']);
        add_action('wp_ajax_sd_admin_approve', [__CLASS__, 'admin_approve']);
        add_action('wp_ajax_sd_admin_reject', [__CLASS__, 'admin_reject']);
        add_action('wp_ajax_sd_admin_send_notes', [__CLASS__, 'admin_send_notes']);
    }

    /**
     * Whether this vendor may load job details (available pool or assigned to them).
     */
    private static function vendor_may_view_job($job_id, $vendor_id) {
        $post = get_post($job_id);
        if (!$post || $post->post_type !== 'sd_job') {
            return false;
        }
        $stage = get_post_meta($job_id, '_sd_stage', true);
        $assigned = (int) get_post_meta($job_id, '_sd_assigned_vendor', true);
        $vid = (int) $vendor_id;
        if ($assigned === $vid) {
            return true;
        }
        if ($stage === 'posted-to-vendors') {
            return true;
        }
        if ($stage === 'approved-priced' && $assigned === 0) {
            return true;
        }
        return false;
    }

    public static function get_job_details() {
        check_ajax_referer('sd_vendor_nonce', 'nonce');

        $job_id = (int) ($_POST['job_id'] ?? 0);
        $vendor_id = get_current_user_id();

        if (!$job_id || !$vendor_id) {
            wp_send_json_error(['message' => __('Invalid request.', 'service-dispatch')]);
        }

        if (!in_array('sd_vendor', wp_get_current_user()->roles, true)) {
            wp_send_json_error(['message' => __('Permission denied.', 'service-dispatch')]);
        }

        if (!self::vendor_may_view_job($job_id, $vendor_id)) {
            wp_send_json_error(['message' => __('You cannot view this job.', 'service-dispatch')]);
        }

        wp_send_json_success(self::build_job_details_payload($job_id, $vendor_id));
    }

    private static function build_job_details_payload($job_id, $vendor_id) {
        $post = get_post($job_id);
        $meta = SD_Meta_Boxes::get_all_meta($job_id);
        $stage = get_post_meta($job_id, '_sd_stage', true) ?: 'new-request';
        $assigned = (int) get_post_meta($job_id, '_sd_assigned_vendor', true);
        $is_mine = $assigned === (int) $vendor_id;

        $service_key = $meta['service_type'] ?: '';
        $urgency_key = $meta['urgency'] ?: '';
        $time_key = $meta['time_window'] ?: '';

        $time_labels = [
            'morning'   => __('Morning (8am–12pm)', 'service-dispatch'),
            'afternoon' => __('Afternoon (12pm–4pm)', 'service-dispatch'),
            'evening'   => __('Evening (4pm–8pm)', 'service-dispatch'),
        ];

        $rows = [
            ['label' => __('Client name', 'service-dispatch'), 'value' => $meta['client_name']],
            ['label' => __('Company', 'service-dispatch'), 'value' => $meta['company_name']],
            ['label' => __('Email', 'service-dispatch'), 'value' => $meta['client_email']],
            ['label' => __('Phone', 'service-dispatch'), 'value' => $meta['client_phone']],
            ['label' => __('Service address', 'service-dispatch'), 'value' => $meta['service_address']],
            ['label' => __('City', 'service-dispatch'), 'value' => $meta['city']],
            ['label' => __('State', 'service-dispatch'), 'value' => $meta['state']],
            ['label' => __('Zip', 'service-dispatch'), 'value' => $meta['zip']],
            ['label' => __('Site / store name', 'service-dispatch'), 'value' => $meta['site_name']],
            ['label' => __('Service type', 'service-dispatch'), 'value' => SD_Post_Types::SERVICE_TYPES[$service_key] ?? $service_key],
            ['label' => __('Urgency', 'service-dispatch'), 'value' => SD_Post_Types::URGENCY_LEVELS[$urgency_key] ?? $urgency_key],
            ['label' => __('Preferred date', 'service-dispatch'), 'value' => $meta['preferred_date']],
            ['label' => __('Time window', 'service-dispatch'), 'value' => $time_labels[$time_key] ?? $time_key],
            ['label' => __('On-site contact', 'service-dispatch'), 'value' => $meta['onsite_contact']],
            ['label' => __('On-site phone', 'service-dispatch'), 'value' => $meta['onsite_phone']],
            ['label' => __('Access instructions', 'service-dispatch'), 'value' => $meta['access_instructions']],
        ];

        $vendor_pay = get_post_meta($job_id, '_sd_vendor_pay', true);
        // Vendors only see the agreed vendor payout — never the client-facing estimate in this UI.
        if ($vendor_pay !== '' && $vendor_pay !== null && $vendor_pay !== false) {
            $price_display = is_numeric($vendor_pay)
                ? '$' . number_format((float) $vendor_pay, 2)
                : (string) $vendor_pay;
            $rows[] = ['label' => __('Price', 'service-dispatch'), 'value' => $price_display];
        }

        $photos = get_post_meta($job_id, '_sd_photos', true);
        if (!is_array($photos)) {
            $photos = [];
        }
        $photo_urls = [];
        foreach ($photos as $p) {
            if (is_string($p) && $p !== '') {
                $photo_urls[] = esc_url_raw($p);
            } elseif (is_array($p) && !empty($p['url'])) {
                $photo_urls[] = esc_url_raw($p['url']);
            }
        }
        $documents = get_post_meta($job_id, '_sd_documents', true);
        if (!is_array($documents)) {
            $documents = [];
        }
        $doc_urls = [];
        foreach ($documents as $d) {
            if (is_string($d) && $d !== '') {
                $doc_urls[] = esc_url_raw($d);
            } elseif (is_array($d) && !empty($d['url'])) {
                $doc_urls[] = esc_url_raw($d['url']);
            }
        }

        $description = $post->post_content ? wp_strip_all_tags($post->post_content) : '';

        $posted = ($stage === 'posted-to-vendors');
        $unassigned = ($assigned === 0);
        $taken_by_other = ($posted && $assigned > 0 && !$is_mine);
        $can_claim = $posted && $unassigned && ($stage !== 'approved-priced');
        $can_claim_yes = $posted && !$taken_by_other && ($stage !== 'approved-priced') && ($unassigned || $is_mine);
        $can_confirm = $is_mine && ($stage === 'claimed' || ($posted && $is_mine));
        $can_complete = ($stage === 'scheduled' && $is_mine);

        return [
            'id'             => $job_id,
            'title'          => get_the_title($post),
            'description'    => $description,
            'rows'           => $rows,
            'photos'         => array_values(array_filter($photo_urls)),
            'documents'      => array_values(array_filter($doc_urls)),
            'stage'          => $stage,
            'stage_label'    => SD_Post_Types::get_stage_label($stage),
            'can_claim'      => $can_claim,
            'can_claim_yes'  => $can_claim_yes,
            'can_confirm'    => $can_confirm,
            'can_complete'   => $can_complete,
        ];
    }

    public static function claim_job() {
        check_ajax_referer('sd_vendor_nonce', 'nonce');

        $job_id = intval($_POST['job_id'] ?? 0);
        $vendor_id = get_current_user_id();

        if (!$job_id || !$vendor_id) {
            wp_send_json_error(['message' => 'Invalid request.']);
        }

        if (!in_array('sd_vendor', wp_get_current_user()->roles, true)) {
            wp_send_json_error(['message' => __('Permission denied.', 'service-dispatch')]);
        }

        $assigned = (int) get_post_meta($job_id, '_sd_assigned_vendor', true);
        $stage = get_post_meta($job_id, '_sd_stage', true);

        if ($stage !== 'posted-to-vendors') {
            wp_send_json_error(['message' => __('This job is not open for claims.', 'service-dispatch')]);
        }

        if ($assigned && $assigned !== $vendor_id) {
            wp_send_json_error(['message' => __('Another provider is already on this job.', 'service-dispatch')]);
        }

        if ($assigned === $vendor_id) {
            if ($stage === 'posted-to-vendors') {
                update_post_meta($job_id, '_sd_stage', 'claimed');
                wp_send_json_success(['message' => __('Your claim is recorded. Confirm YES when ready.', 'service-dispatch')]);
            }
            if ($stage === 'claimed') {
                wp_send_json_success(['message' => __('Already claimed — use Confirm YES to finalize.', 'service-dispatch')]);
            }
            wp_send_json_error(['message' => __('This job is not in a claimable state.', 'service-dispatch')]);
        }

        update_post_meta($job_id, '_sd_assigned_vendor', $vendor_id);
        update_post_meta($job_id, '_sd_stage', 'claimed');

        $vendor = get_userdata($vendor_id);
        SD_Meta_Boxes::add_timeline_event($job_id, 'Job claimed by vendor: ' . $vendor->display_name, '#f97316');

        wp_send_json_success(['message' => __('Job claimed! Confirm YES so the client sees you as assigned.', 'service-dispatch')]);
    }

    /**
     * One step: assign vendor and confirm YES — client & admin see scheduled assignment immediately.
     */
    public static function claim_and_confirm_job() {
        check_ajax_referer('sd_vendor_nonce', 'nonce');

        $job_id = (int) ($_POST['job_id'] ?? 0);
        $vendor_id = get_current_user_id();

        if (!$job_id || !$vendor_id) {
            wp_send_json_error(['message' => __('Invalid request.', 'service-dispatch')]);
        }

        if (!in_array('sd_vendor', wp_get_current_user()->roles, true)) {
            wp_send_json_error(['message' => __('Permission denied.', 'service-dispatch')]);
        }

        $assigned = (int) get_post_meta($job_id, '_sd_assigned_vendor', true);
        $stage = get_post_meta($job_id, '_sd_stage', true);

        if ($stage !== 'posted-to-vendors') {
            wp_send_json_error(['message' => __('This job is not open for claims.', 'service-dispatch')]);
        }

        if ($assigned && $assigned !== $vendor_id) {
            wp_send_json_error(['message' => __('Another provider is already on this job.', 'service-dispatch')]);
        }

        if (!$assigned) {
            update_post_meta($job_id, '_sd_assigned_vendor', $vendor_id);
        }

        update_post_meta($job_id, '_sd_stage', 'scheduled');
        update_post_meta($job_id, '_sd_vendor_confirmed_yes', current_time('mysql'));

        $vendor = get_userdata($vendor_id);
        $name = $vendor ? $vendor->display_name : (string) $vendor_id;
        SD_Meta_Boxes::add_timeline_event($job_id, 'Vendor claimed and confirmed (YES): ' . $name . ' — scheduled', '#06b6d4');

        wp_send_json_success(['message' => __('You are assigned to this job. The client and admin can see you as the provider.', 'service-dispatch')]);
    }

    public static function confirm_job() {
        check_ajax_referer('sd_vendor_nonce', 'nonce');

        $job_id = intval($_POST['job_id'] ?? 0);
        $vendor_id = get_current_user_id();

        if (!$job_id || !$vendor_id) {
            wp_send_json_error(['message' => 'Invalid request.']);
        }

        if (!in_array('sd_vendor', wp_get_current_user()->roles, true)) {
            wp_send_json_error(['message' => __('Permission denied.', 'service-dispatch')]);
        }

        $assigned = get_post_meta($job_id, '_sd_assigned_vendor', true);
        if ((int)$assigned !== $vendor_id) {
            wp_send_json_error(['message' => 'This job is not assigned to you.']);
        }

        $stage = get_post_meta($job_id, '_sd_stage', true);
        if ($stage !== 'claimed' && !($stage === 'posted-to-vendors' && (int) $assigned === $vendor_id)) {
            wp_send_json_error(['message' => __('This job cannot be confirmed at its current stage.', 'service-dispatch')]);
        }

        update_post_meta($job_id, '_sd_stage', 'scheduled');
        update_post_meta($job_id, '_sd_vendor_confirmed_yes', current_time('mysql'));
        SD_Meta_Boxes::add_timeline_event($job_id, 'Vendor confirmed (YES) — job scheduled', '#06b6d4');

        wp_send_json_success(['message' => __('Confirmed! You are assigned to this job. The client and admin can see you as the provider.', 'service-dispatch')]);
    }

    public static function complete_job() {
        check_ajax_referer('sd_vendor_nonce', 'nonce');

        $job_id = intval($_POST['job_id'] ?? 0);
        $vendor_id = get_current_user_id();

        if (!$job_id || !$vendor_id) {
            wp_send_json_error(['message' => 'Invalid request.']);
        }

        if (!in_array('sd_vendor', wp_get_current_user()->roles, true)) {
            wp_send_json_error(['message' => __('Permission denied.', 'service-dispatch')]);
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
