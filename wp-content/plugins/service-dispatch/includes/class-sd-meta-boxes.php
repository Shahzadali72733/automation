<?php
if (!defined('ABSPATH')) exit;

class SD_Meta_Boxes {

    public static function init() {
        add_action('add_meta_boxes', [__CLASS__, 'add_meta_boxes']);
        add_action('save_post_sd_job', [__CLASS__, 'save_meta'], 10, 2);
    }

    public static function add_meta_boxes() {
        add_meta_box('sd_job_details', 'Job Details', [__CLASS__, 'render_job_details'], 'sd_job', 'normal', 'high');
        add_meta_box('sd_job_photos', 'Photos & Documents', [__CLASS__, 'render_photos'], 'sd_job', 'normal', 'high');
        add_meta_box('sd_job_pipeline', 'Pipeline Stage', [__CLASS__, 'render_pipeline_stage'], 'sd_job', 'side', 'high');
        add_meta_box('sd_job_pricing', 'Pricing', [__CLASS__, 'render_pricing'], 'sd_job', 'side', 'high');
        add_meta_box('sd_job_vendor', 'Assigned Vendor', [__CLASS__, 'render_vendor_assignment'], 'sd_job', 'side', 'default');
        add_meta_box('sd_job_admin_actions', 'Admin Actions', [__CLASS__, 'render_admin_actions'], 'sd_job', 'side', 'high');
        add_meta_box('sd_job_admin_review', 'Admin Review', [__CLASS__, 'render_admin_review'], 'sd_job', 'side', 'high');
        add_meta_box('sd_job_form_entry', 'Form Submission', [__CLASS__, 'render_form_entry'], 'sd_job', 'side', 'default');
        add_meta_box('sd_job_timeline', 'Job Timeline', [__CLASS__, 'render_timeline'], 'sd_job', 'normal', 'default');
    }

    public static function render_job_details($post) {
        wp_nonce_field('sd_save_job_meta', 'sd_job_nonce');
        $meta = self::get_all_meta($post->ID);
        ?>
        <div class="sd-meta-grid">
            <div class="sd-meta-row">
                <div class="sd-meta-col">
                    <label><strong>Client Name</strong></label>
                    <input type="text" name="sd_client_name" value="<?php echo esc_attr($meta['client_name']); ?>" class="widefat">
                </div>
                <div class="sd-meta-col">
                    <label><strong>Company</strong></label>
                    <input type="text" name="sd_company_name" value="<?php echo esc_attr($meta['company_name']); ?>" class="widefat">
                </div>
            </div>
            <div class="sd-meta-row">
                <div class="sd-meta-col">
                    <label><strong>Phone</strong></label>
                    <input type="text" name="sd_client_phone" value="<?php echo esc_attr($meta['client_phone']); ?>" class="widefat">
                </div>
                <div class="sd-meta-col">
                    <label><strong>Email</strong></label>
                    <input type="email" name="sd_client_email" value="<?php echo esc_attr($meta['client_email']); ?>" class="widefat">
                </div>
            </div>
            <div class="sd-meta-row">
                <div class="sd-meta-col">
                    <label><strong>Service Address</strong></label>
                    <input type="text" name="sd_service_address" value="<?php echo esc_attr($meta['service_address']); ?>" class="widefat">
                </div>
            </div>
            <div class="sd-meta-row">
                <div class="sd-meta-col-third">
                    <label><strong>City</strong></label>
                    <input type="text" name="sd_city" value="<?php echo esc_attr($meta['city']); ?>" class="widefat">
                </div>
                <div class="sd-meta-col-third">
                    <label><strong>State</strong></label>
                    <input type="text" name="sd_state" value="<?php echo esc_attr($meta['state']); ?>" class="widefat">
                </div>
                <div class="sd-meta-col-third">
                    <label><strong>Zip Code</strong></label>
                    <input type="text" name="sd_zip" value="<?php echo esc_attr($meta['zip']); ?>" class="widefat">
                </div>
            </div>
            <div class="sd-meta-row">
                <div class="sd-meta-col">
                    <label><strong>Service Type</strong></label>
                    <select name="sd_service_type" class="widefat">
                        <option value="">— Select —</option>
                        <?php foreach (SD_Post_Types::SERVICE_TYPES as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($meta['service_type'], $key); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sd-meta-col">
                    <label><strong>Urgency</strong></label>
                    <select name="sd_urgency" class="widefat">
                        <?php foreach (SD_Post_Types::URGENCY_LEVELS as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($meta['urgency'], $key); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="sd-meta-row">
                <div class="sd-meta-col">
                    <label><strong>Preferred Date</strong></label>
                    <input type="date" name="sd_preferred_date" value="<?php echo esc_attr($meta['preferred_date']); ?>" class="widefat">
                </div>
                <div class="sd-meta-col">
                    <label><strong>Time Window</strong></label>
                    <select name="sd_time_window" class="widefat">
                        <option value="">— Any —</option>
                        <option value="morning" <?php selected($meta['time_window'], 'morning'); ?>>Morning (8am–12pm)</option>
                        <option value="afternoon" <?php selected($meta['time_window'], 'afternoon'); ?>>Afternoon (12pm–4pm)</option>
                        <option value="evening" <?php selected($meta['time_window'], 'evening'); ?>>Evening (4pm–8pm)</option>
                    </select>
                </div>
            </div>
            <div class="sd-meta-row">
                <div class="sd-meta-col">
                    <label><strong>On-Site Contact Name</strong></label>
                    <input type="text" name="sd_onsite_contact" value="<?php echo esc_attr($meta['onsite_contact']); ?>" class="widefat">
                </div>
                <div class="sd-meta-col">
                    <label><strong>On-Site Contact Phone</strong></label>
                    <input type="text" name="sd_onsite_phone" value="<?php echo esc_attr($meta['onsite_phone']); ?>" class="widefat">
                </div>
            </div>
            <div class="sd-meta-row">
                <div class="sd-meta-col">
                    <label><strong>Access Instructions</strong></label>
                    <textarea name="sd_access_instructions" rows="3" class="widefat"><?php echo esc_textarea($meta['access_instructions']); ?></textarea>
                </div>
            </div>
            <div class="sd-meta-row">
                <div class="sd-meta-col">
                    <label><strong>Site Name / Store Name</strong></label>
                    <input type="text" name="sd_site_name" value="<?php echo esc_attr($meta['site_name']); ?>" class="widefat">
                </div>
            </div>
        </div>
        <style>
            .sd-meta-grid { display: flex; flex-direction: column; gap: 12px; }
            .sd-meta-row { display: flex; gap: 16px; }
            .sd-meta-col { flex: 1; }
            .sd-meta-col-third { flex: 1; }
            .sd-meta-grid label { display: block; margin-bottom: 4px; font-size: 13px; }
            .sd-meta-grid input, .sd-meta-grid select, .sd-meta-grid textarea { margin-top: 2px; }
        </style>
        <?php
    }

    public static function render_admin_actions($post) {
        $stage = get_post_meta($post->ID, '_sd_stage', true) ?: 'new-request';
        $admin_status = get_post_meta($post->ID, '_sd_admin_status', true);
        $admin_notes = get_post_meta($post->ID, '_sd_admin_notes', true);
        ?>
        <div class="sd-admin-actions-box">
            <?php if ($stage === 'new-request'): ?>
                <p style="font-size:13px;color:#475569;margin-bottom:12px;">This is a new request. Review the details and take action:</p>
                <div style="display:flex;gap:8px;margin-bottom:12px;">
                    <button type="button" class="button button-primary sd-admin-approve-btn" data-job-id="<?php echo $post->ID; ?>" style="flex:1;">Approve</button>
                    <button type="button" class="button sd-admin-reject-btn" data-job-id="<?php echo $post->ID; ?>" style="flex:1;color:#ef4444;border-color:#fca5a5;">Reject</button>
                </div>
                <div style="margin-bottom:8px;">
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">Send notes to client:</label>
                    <textarea id="sd-admin-notes" rows="3" class="widefat" placeholder="Enter feedback or changes needed..."><?php echo esc_textarea($admin_notes); ?></textarea>
                </div>
                <button type="button" class="button sd-admin-notes-btn" data-job-id="<?php echo $post->ID; ?>" style="width:100%;">Send Notes</button>
            <?php else: ?>
                <p style="font-size:13px;color:#64748b;">
                    <strong>Status:</strong>
                    <?php if ($admin_status === 'approved'): ?>
                        <span style="color:#16a34a;">Approved</span>
                    <?php elseif ($admin_status === 'rejected'): ?>
                        <span style="color:#ef4444;">Rejected</span>
                    <?php elseif ($admin_status === 'needs-revision'): ?>
                        <span style="color:#d97706;">Needs Revision</span>
                    <?php else: ?>
                        <span><?php echo esc_html(SD_Post_Types::get_stage_label($stage)); ?></span>
                    <?php endif; ?>
                </p>
                <?php if ($admin_notes): ?>
                    <div style="margin-top:8px;padding:8px 12px;background:#f8fafc;border-radius:6px;font-size:12px;color:#475569;">
                        <strong>Notes:</strong> <?php echo esc_html($admin_notes); ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <script>
        jQuery(function($){
            $('.sd-admin-approve-btn').on('click', function(){
                var btn = $(this);
                if(!confirm('Approve this request?')) return;
                btn.prop('disabled',true).text('Approving...');
                $.post(ajaxurl, {action:'sd_admin_approve', nonce:'<?php echo wp_create_nonce("sd_admin_nonce"); ?>', job_id:btn.data('job-id')}, function(r){
                    if(r.success){ alert(r.data.message); location.reload(); }
                    else { alert(r.data.message||'Error'); btn.prop('disabled',false).text('Approve'); }
                });
            });
            $('.sd-admin-reject-btn').on('click', function(){
                var btn = $(this);
                var reason = prompt('Reason for rejection (optional):','');
                if(reason === null) return;
                btn.prop('disabled',true).text('Rejecting...');
                $.post(ajaxurl, {action:'sd_admin_reject', nonce:'<?php echo wp_create_nonce("sd_admin_nonce"); ?>', job_id:btn.data('job-id'), reason:reason}, function(r){
                    if(r.success){ alert(r.data.message); location.reload(); }
                    else { alert(r.data.message||'Error'); btn.prop('disabled',false).text('Reject'); }
                });
            });
            $('.sd-admin-notes-btn').on('click', function(){
                var btn = $(this);
                var notes = $('#sd-admin-notes').val().trim();
                if(!notes){ alert('Please enter notes.'); return; }
                btn.prop('disabled',true).text('Sending...');
                $.post(ajaxurl, {action:'sd_admin_send_notes', nonce:'<?php echo wp_create_nonce("sd_admin_nonce"); ?>', job_id:btn.data('job-id'), notes:notes}, function(r){
                    if(r.success){ alert(r.data.message); location.reload(); }
                    else { alert(r.data.message||'Error'); btn.prop('disabled',false).text('Send Notes'); }
                });
            });
        });
        </script>
        <?php
    }

    public static function render_photos($post) {
        $photos    = get_post_meta($post->ID, '_sd_photos', true);
        $documents = get_post_meta($post->ID, '_sd_documents', true);
        if (!is_array($photos))    $photos = [];
        if (!is_array($documents)) $documents = [];

        if (empty($photos) && empty($documents)) {
            echo '<p style="color:#9ca3af;font-style:italic;padding:8px 0;">No photos or documents uploaded.</p>';
            return;
        }
        ?>
        <?php if (!empty($photos)): ?>
        <div class="sd-photos-section">
            <h4 style="margin:0 0 10px;font-size:13px;color:#475569;">Photos</h4>
            <div class="sd-photo-grid">
                <?php foreach ($photos as $url): ?>
                    <a href="<?php echo esc_url($url); ?>" target="_blank" class="sd-photo-thumb">
                        <img src="<?php echo esc_url($url); ?>" alt="Job photo">
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($documents)): ?>
        <div class="sd-docs-section" style="margin-top:12px;">
            <h4 style="margin:0 0 10px;font-size:13px;color:#475569;">Documents</h4>
            <ul class="sd-doc-list">
                <?php foreach ($documents as $url):
                    $filename = basename(parse_url($url, PHP_URL_PATH));
                ?>
                    <li><a href="<?php echo esc_url($url); ?>" target="_blank"><span class="dashicons dashicons-media-document"></span> <?php echo esc_html($filename); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <style>
            .sd-photo-grid { display: flex; flex-wrap: wrap; gap: 8px; }
            .sd-photo-thumb { display: block; width: 100px; height: 100px; border-radius: 8px; overflow: hidden; border: 2px solid #e2e8f0; transition: border-color 0.2s; }
            .sd-photo-thumb:hover { border-color: #3b82f6; }
            .sd-photo-thumb img { width: 100%; height: 100%; object-fit: cover; }
            .sd-doc-list { margin: 0; padding: 0; list-style: none; }
            .sd-doc-list li { padding: 6px 8px; background: #f8fafc; border-radius: 6px; margin-bottom: 4px; }
            .sd-doc-list li a { text-decoration: none; color: #1e293b; font-size: 13px; display: flex; align-items: center; gap: 6px; }
            .sd-doc-list li a:hover { color: #3b82f6; }
            .sd-doc-list li .dashicons { font-size: 16px; width: 16px; height: 16px; color: #64748b; }
        </style>
        <?php
    }

    public static function render_admin_review($post) {
        $stage        = get_post_meta($post->ID, '_sd_stage', true) ?: 'new-request';
        $admin_status = get_post_meta($post->ID, '_sd_admin_status', true);
        $admin_notes  = get_post_meta($post->ID, '_sd_admin_notes', true);
        $nonce        = wp_create_nonce('sd_admin_nonce');
        ?>
        <div class="sd-admin-review-box" data-job-id="<?php echo $post->ID; ?>" data-nonce="<?php echo $nonce; ?>">
            <?php if ($admin_status): ?>
            <div class="sd-review-status sd-review-<?php echo esc_attr($admin_status); ?>">
                <strong>Current Status:</strong>
                <?php echo esc_html(ucwords(str_replace('-', ' ', $admin_status))); ?>
            </div>
            <?php endif; ?>

            <?php if ($stage === 'new-request'): ?>
            <p style="font-size:13px;color:#475569;margin:8px 0 14px;">This request is awaiting your review. Approve it to post to vendors, reject it, or send notes to the client.</p>
            <div class="sd-review-actions">
                <button type="button" class="button button-primary sd-admin-approve-btn" style="background:#22c55e;border-color:#16a34a;width:100%;margin-bottom:6px;">
                    <span class="dashicons dashicons-yes-alt" style="margin-top:3px;"></span> Approve &amp; Price
                </button>
                <button type="button" class="button sd-admin-reject-btn" style="color:#ef4444;border-color:#fca5a5;width:100%;margin-bottom:6px;">
                    <span class="dashicons dashicons-dismiss" style="margin-top:3px;"></span> Reject
                </button>
            </div>
            <div class="sd-review-notes" style="margin-top:10px;">
                <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Notes to Client</label>
                <textarea id="sd-admin-notes" rows="3" class="widefat" placeholder="Request changes, ask questions, provide feedback..."><?php echo esc_textarea($admin_notes); ?></textarea>
                <button type="button" class="button sd-admin-notes-btn" style="margin-top:6px;width:100%;">
                    <span class="dashicons dashicons-edit" style="margin-top:3px;"></span> Send Notes to Client
                </button>
            </div>
            <?php elseif ($stage === 'approved-priced'): ?>
            <p style="font-size:13px;color:#059669;margin:8px 0;">This request has been approved. Set pricing and change stage to "Posted to Vendors" when ready.</p>
            <?php elseif ($stage === 'posted-to-vendors'): ?>
            <p style="font-size:13px;color:#d97706;margin:8px 0;">This job has been posted to vendors. Waiting for a vendor to claim it.</p>
            <?php else: ?>
            <p style="font-size:13px;color:#475569;margin:8px 0;">Stage: <strong><?php echo esc_html(SD_Post_Types::get_stage_label($stage)); ?></strong></p>
            <?php endif; ?>

            <?php if ($admin_notes && $stage !== 'new-request'): ?>
            <div style="margin-top:10px;">
                <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:4px;">Admin Notes</label>
                <textarea name="sd_admin_notes_field" rows="3" class="widefat"><?php echo esc_textarea($admin_notes); ?></textarea>
            </div>
            <?php endif; ?>
        </div>

        <style>
            .sd-review-status { padding:8px 12px; border-radius:6px; font-size:13px; margin-bottom:10px; }
            .sd-review-approved { background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; }
            .sd-review-rejected { background:#fef2f2; color:#ef4444; border:1px solid #fecaca; }
            .sd-review-needs-revision { background:#fffbeb; color:#d97706; border:1px solid #fde68a; }
        </style>
        <script>
        jQuery(function($){
            var $box = $('.sd-admin-review-box');
            var jobId = $box.data('job-id');
            var nonce = $box.data('nonce');
            var ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';

            $box.on('click', '.sd-admin-approve-btn', function(){
                if(!confirm('Approve this request? It will move to "Approved / Priced" stage.')) return;
                var $btn = $(this).prop('disabled', true).text('Approving...');
                $.post(ajaxUrl, {action:'sd_admin_approve', nonce:nonce, job_id:jobId}, function(res){
                    if(res.success){ location.reload(); }
                    else { alert(res.data.message||'Error'); $btn.prop('disabled',false).text('Approve & Price'); }
                });
            });

            $box.on('click', '.sd-admin-reject-btn', function(){
                var reason = prompt('Reason for rejection (optional):');
                if(reason === null) return;
                var $btn = $(this).prop('disabled', true).text('Rejecting...');
                $.post(ajaxUrl, {action:'sd_admin_reject', nonce:nonce, job_id:jobId, reason:reason}, function(res){
                    if(res.success){ location.reload(); }
                    else { alert(res.data.message||'Error'); $btn.prop('disabled',false).text('Reject'); }
                });
            });

            $box.on('click', '.sd-admin-notes-btn', function(){
                var notes = $('#sd-admin-notes').val();
                if(!notes){ alert('Please enter notes.'); return; }
                var $btn = $(this).prop('disabled', true).text('Sending...');
                $.post(ajaxUrl, {action:'sd_admin_send_notes', nonce:nonce, job_id:jobId, notes:notes}, function(res){
                    if(res.success){ location.reload(); }
                    else { alert(res.data.message||'Error'); $btn.prop('disabled',false).text('Send Notes to Client'); }
                });
            });
        });
        </script>
        <?php
    }

    public static function render_form_entry($post) {
        $entry_id = get_post_meta($post->ID, '_sd_ff_entry_id', true);
        $form_id  = get_post_meta($post->ID, '_sd_ff_form_id', true);
        $user_id  = get_post_meta($post->ID, '_sd_submitted_by', true);

        if (!$entry_id) {
            echo '<p style="color:#9ca3af;font-style:italic;">No linked form submission.</p>';
            return;
        }

        $entry_url = admin_url('admin.php?page=fluent_forms&route=entries&form_id=' . intval($form_id) . '#/entries/' . intval($entry_id));
        ?>
        <div class="sd-form-entry-box">
            <p><strong>Fluent Forms Entry:</strong> <a href="<?php echo esc_url($entry_url); ?>" target="_blank">#<?php echo intval($entry_id); ?></a></p>
            <?php if ($user_id):
                $user = get_userdata($user_id);
                if ($user): ?>
                    <p><strong>Submitted by:</strong> <a href="<?php echo get_edit_user_link($user_id); ?>"><?php echo esc_html($user->display_name); ?></a> (<?php echo esc_html(implode(', ', $user->roles)); ?>)</p>
                <?php endif;
            endif; ?>
            <p><strong>Submitted:</strong> <?php echo get_the_date('M j, Y g:ia', $post); ?></p>
        </div>
        <style>
            .sd-form-entry-box p { margin: 6px 0; font-size: 13px; }
            .sd-form-entry-box a { color: #3b82f6; text-decoration: none; }
            .sd-form-entry-box a:hover { text-decoration: underline; }
        </style>
        <?php
    }

    public static function render_pipeline_stage($post) {
        $current = get_post_meta($post->ID, '_sd_stage', true) ?: 'new-request';
        ?>
        <div class="sd-pipeline-select">
            <?php foreach (SD_Post_Types::STAGES as $key => $label): ?>
                <label class="sd-stage-option <?php echo $current === $key ? 'active' : ''; ?>" style="--stage-color: <?php echo SD_Post_Types::get_stage_color($key); ?>">
                    <input type="radio" name="sd_stage" value="<?php echo esc_attr($key); ?>" <?php checked($current, $key); ?>>
                    <span class="dashicons <?php echo SD_Post_Types::get_stage_icon($key); ?>"></span>
                    <span class="sd-stage-label"><?php echo esc_html($label); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <style>
            .sd-pipeline-select { display: flex; flex-direction: column; gap: 4px; }
            .sd-stage-option { display: flex; align-items: center; gap: 8px; padding: 8px 10px; border-radius: 6px; cursor: pointer; border: 2px solid transparent; transition: all 0.2s; }
            .sd-stage-option:hover { background: #f0f0f1; }
            .sd-stage-option.active { border-color: var(--stage-color); background: color-mix(in srgb, var(--stage-color) 8%, white); }
            .sd-stage-option input { display: none; }
            .sd-stage-option .dashicons { color: var(--stage-color); font-size: 16px; width: 16px; height: 16px; }
            .sd-stage-label { font-size: 13px; font-weight: 500; }
        </style>
        <?php
    }

    public static function render_pricing($post) {
        $client_price = get_post_meta($post->ID, '_sd_client_price', true);
        $vendor_pay   = get_post_meta($post->ID, '_sd_vendor_pay', true);
        $margin = '';
        if ($client_price && $vendor_pay) {
            $margin = number_format((float)$client_price - (float)$vendor_pay, 2);
        }
        ?>
        <div class="sd-pricing-box">
            <div class="sd-price-field">
                <label><strong>Client Final Price ($)</strong></label>
                <input type="number" step="0.01" name="sd_client_price" value="<?php echo esc_attr($client_price); ?>" class="widefat" id="sd-client-price">
            </div>
            <div class="sd-price-field">
                <label><strong>Vendor Pay Amount ($)</strong></label>
                <input type="number" step="0.01" name="sd_vendor_pay" value="<?php echo esc_attr($vendor_pay); ?>" class="widefat" id="sd-vendor-pay">
            </div>
            <div class="sd-price-margin" id="sd-margin-display" <?php echo $margin ? '' : 'style="display:none"'; ?>>
                <span>Your Margin:</span>
                <strong>$<span id="sd-margin-value"><?php echo esc_html($margin); ?></span></strong>
            </div>
        </div>
        <style>
            .sd-pricing-box { display: flex; flex-direction: column; gap: 10px; }
            .sd-price-field label { display: block; margin-bottom: 4px; font-size: 13px; }
            .sd-price-margin { background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 6px; padding: 8px 12px; text-align: center; font-size: 14px; }
        </style>
        <script>
            jQuery(function($){
                function calcMargin(){
                    var cp = parseFloat($('#sd-client-price').val()) || 0;
                    var vp = parseFloat($('#sd-vendor-pay').val()) || 0;
                    if(cp > 0 && vp > 0){
                        $('#sd-margin-value').text((cp - vp).toFixed(2));
                        $('#sd-margin-display').show();
                    } else { $('#sd-margin-display').hide(); }
                }
                $('#sd-client-price, #sd-vendor-pay').on('input', calcMargin);
            });
        </script>
        <?php
    }

    public static function render_vendor_assignment($post) {
        $assigned_vendor = get_post_meta($post->ID, '_sd_assigned_vendor', true);
        $vendors = get_users(['role' => 'sd_vendor', 'orderby' => 'display_name']);
        ?>
        <div class="sd-vendor-assign">
            <select name="sd_assigned_vendor" class="widefat">
                <option value="">— Unassigned —</option>
                <?php foreach ($vendors as $vendor): ?>
                    <option value="<?php echo $vendor->ID; ?>" <?php selected($assigned_vendor, $vendor->ID); ?>>
                        <?php echo esc_html($vendor->display_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($assigned_vendor):
                $v = get_userdata($assigned_vendor);
                if ($v): ?>
                    <div class="sd-vendor-card" style="margin-top: 10px;">
                        <div class="sd-vendor-avatar"><?php echo get_avatar($assigned_vendor, 40); ?></div>
                        <div>
                            <strong><?php echo esc_html($v->display_name); ?></strong><br>
                            <small><?php echo esc_html($v->user_email); ?></small>
                        </div>
                    </div>
                <?php endif;
            endif; ?>
        </div>
        <style>
            .sd-vendor-card { display: flex; align-items: center; gap: 10px; padding: 8px; background: #f8fafc; border-radius: 6px; }
            .sd-vendor-card img { border-radius: 50%; }
        </style>
        <?php
    }

    public static function render_timeline($post) {
        $timeline = get_post_meta($post->ID, '_sd_timeline', true);
        if (!is_array($timeline)) $timeline = [];
        ?>
        <div class="sd-timeline">
            <?php if (empty($timeline)): ?>
                <p style="color: #9ca3af; font-style: italic;">No timeline events yet.</p>
            <?php else: ?>
                <?php foreach (array_reverse($timeline) as $event): ?>
                    <div class="sd-timeline-item">
                        <div class="sd-timeline-dot" style="background: <?php echo esc_attr($event['color'] ?? '#6b7280'); ?>"></div>
                        <div class="sd-timeline-content">
                            <span class="sd-timeline-time"><?php echo esc_html(date('M j, g:ia', $event['time'])); ?></span>
                            <span class="sd-timeline-text"><?php echo esc_html($event['text']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <style>
            .sd-timeline { display: flex; flex-direction: column; gap: 0; padding-left: 16px; border-left: 2px solid #e5e7eb; }
            .sd-timeline-item { display: flex; align-items: flex-start; gap: 12px; padding: 8px 0; position: relative; }
            .sd-timeline-dot { width: 10px; height: 10px; border-radius: 50%; position: absolute; left: -22px; top: 12px; }
            .sd-timeline-content { display: flex; flex-direction: column; }
            .sd-timeline-time { font-size: 11px; color: #9ca3af; }
            .sd-timeline-text { font-size: 13px; }
        </style>
        <?php
    }

    public static function save_meta($post_id, $post) {
        if (!isset($_POST['sd_job_nonce']) || !wp_verify_nonce($_POST['sd_job_nonce'], 'sd_save_job_meta')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $text_fields = [
            'sd_client_name', 'sd_company_name', 'sd_client_phone', 'sd_client_email',
            'sd_service_address', 'sd_city', 'sd_state', 'sd_zip',
            'sd_service_type', 'sd_urgency', 'sd_preferred_date', 'sd_time_window',
            'sd_onsite_contact', 'sd_onsite_phone', 'sd_site_name',
        ];

        foreach ($text_fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
            }
        }

        if (isset($_POST['sd_access_instructions'])) {
            update_post_meta($post_id, '_sd_access_instructions', sanitize_textarea_field($_POST['sd_access_instructions']));
        }

        if (isset($_POST['sd_stage'])) {
            $old_stage = get_post_meta($post_id, '_sd_stage', true);
            $new_stage = sanitize_text_field($_POST['sd_stage']);
            update_post_meta($post_id, '_sd_stage', $new_stage);

            if ($old_stage !== $new_stage) {
                self::add_timeline_event($post_id, 'Stage changed to: ' . SD_Post_Types::get_stage_label($new_stage), SD_Post_Types::get_stage_color($new_stage));
            }
        }

        if (isset($_POST['sd_client_price'])) {
            update_post_meta($post_id, '_sd_client_price', sanitize_text_field($_POST['sd_client_price']));
        }
        if (isset($_POST['sd_vendor_pay'])) {
            update_post_meta($post_id, '_sd_vendor_pay', sanitize_text_field($_POST['sd_vendor_pay']));
        }
        if (isset($_POST['sd_assigned_vendor'])) {
            $old_vendor = get_post_meta($post_id, '_sd_assigned_vendor', true);
            $new_vendor = sanitize_text_field($_POST['sd_assigned_vendor']);
            update_post_meta($post_id, '_sd_assigned_vendor', $new_vendor);
            if ($old_vendor !== $new_vendor && $new_vendor) {
                $v = get_userdata($new_vendor);
                self::add_timeline_event($post_id, 'Vendor assigned: ' . ($v ? $v->display_name : '#' . $new_vendor), '#f97316');
            }
        }

        if (isset($_POST['sd_admin_notes_field'])) {
            update_post_meta($post_id, '_sd_admin_notes', sanitize_textarea_field($_POST['sd_admin_notes_field']));
        }
    }

    public static function add_timeline_event($post_id, $text, $color = '#6b7280') {
        $timeline = get_post_meta($post_id, '_sd_timeline', true);
        if (!is_array($timeline)) $timeline = [];
        $timeline[] = [
            'time'  => time(),
            'text'  => $text,
            'color' => $color,
            'user'  => get_current_user_id(),
        ];
        update_post_meta($post_id, '_sd_timeline', $timeline);
    }

    /**
     * Core job fields stored from the service request (used in admin + vendor detail API).
     */
    public static function get_all_meta($post_id) {
        $fields = [
            'client_name', 'company_name', 'client_phone', 'client_email',
            'service_address', 'city', 'state', 'zip',
            'service_type', 'urgency', 'preferred_date', 'time_window',
            'onsite_contact', 'onsite_phone', 'access_instructions', 'site_name',
        ];
        $meta = [];
        foreach ($fields as $f) {
            $meta[$f] = get_post_meta($post_id, '_sd_' . $f, true) ?: '';
        }
        return $meta;
    }
}
