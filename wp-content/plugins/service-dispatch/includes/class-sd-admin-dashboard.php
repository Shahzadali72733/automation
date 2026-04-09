<?php
if (!defined('ABSPATH')) exit;

class SD_Admin_Dashboard {

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_menu']);
        add_action('wp_dashboard_setup', [__CLASS__, 'add_dashboard_widgets']);
    }

    public static function add_menu() {
        add_menu_page(
            'Service Dispatch',
            'Service Dispatch',
            'manage_options',
            'sd-pipeline',
            [__CLASS__, 'render_pipeline_page'],
            'dashicons-networking',
            26
        );

        add_submenu_page('sd-pipeline', 'Pipeline Board', 'Pipeline Board', 'manage_options', 'sd-pipeline', [__CLASS__, 'render_pipeline_page']);
        add_submenu_page('sd-pipeline', 'All Jobs', 'All Jobs', 'manage_options', 'edit.php?post_type=sd_job');
        add_submenu_page('sd-pipeline', 'Add New Job', 'Add New Job', 'manage_options', 'post-new.php?post_type=sd_job');
        add_submenu_page('sd-pipeline', 'Vendors', 'Vendors', 'manage_options', 'sd-vendors', [__CLASS__, 'render_vendors_page']);
        add_submenu_page('sd-pipeline', 'Clients', 'Clients', 'manage_options', 'sd-clients', [__CLASS__, 'render_clients_page']);
        add_submenu_page('sd-pipeline', 'Form Entries', 'Form Entries', 'manage_options', 'sd-form-entries', [__CLASS__, 'render_form_entries_page']);
        add_submenu_page('sd-pipeline', 'SMS Log', 'SMS Log', 'manage_options', 'sd-sms-log', [__CLASS__, 'render_sms_log_page']);
        add_submenu_page('sd-pipeline', 'Settings', 'Settings', 'manage_options', 'sd-settings', [__CLASS__, 'render_settings_page']);
    }

    public static function add_dashboard_widgets() {
        wp_add_dashboard_widget('sd_overview_widget', 'Service Dispatch — Overview', [__CLASS__, 'render_overview_widget']);
        wp_add_dashboard_widget('sd_recent_jobs_widget', 'Service Dispatch — Recent Jobs', [__CLASS__, 'render_recent_jobs_widget']);
    }

    public static function render_overview_widget() {
        $counts = [];
        foreach (SD_Post_Types::STAGES as $key => $label) {
            $counts[$key] = self::count_jobs_by_stage($key);
        }
        $total = array_sum($counts);
        ?>
        <div class="sd-widget-overview">
            <div class="sd-widget-total">
                <span class="sd-widget-total-num"><?php echo $total; ?></span>
                <span class="sd-widget-total-label">Total Active Jobs</span>
            </div>
            <div class="sd-widget-stages">
                <?php foreach (SD_Post_Types::STAGES as $key => $label): ?>
                    <div class="sd-widget-stage-row">
                        <span class="sd-widget-stage-dot" style="background: <?php echo SD_Post_Types::get_stage_color($key); ?>"></span>
                        <span class="sd-widget-stage-name"><?php echo esc_html($label); ?></span>
                        <span class="sd-widget-stage-count"><?php echo $counts[$key]; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <style>
            .sd-widget-overview { padding: 5px 0; }
            .sd-widget-total { text-align: center; margin-bottom: 16px; }
            .sd-widget-total-num { display: block; font-size: 36px; font-weight: 700; color: #1e293b; line-height: 1; }
            .sd-widget-total-label { font-size: 13px; color: #64748b; }
            .sd-widget-stages { display: flex; flex-direction: column; gap: 6px; }
            .sd-widget-stage-row { display: flex; align-items: center; gap: 8px; padding: 6px 8px; border-radius: 6px; background: #f8fafc; }
            .sd-widget-stage-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
            .sd-widget-stage-name { flex: 1; font-size: 13px; }
            .sd-widget-stage-count { font-weight: 600; font-size: 14px; color: #334155; }
        </style>
        <?php
    }

    public static function render_recent_jobs_widget() {
        $jobs = get_posts([
            'post_type'      => 'sd_job',
            'posts_per_page' => 8,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);
        if (empty($jobs)) {
            echo '<p style="color:#9ca3af;text-align:center;padding:20px 0;">No jobs yet.</p>';
            return;
        }
        echo '<div class="sd-recent-jobs">';
        foreach ($jobs as $job) {
            $stage = get_post_meta($job->ID, '_sd_stage', true) ?: 'new-request';
            $client = get_post_meta($job->ID, '_sd_client_name', true) ?: 'Unknown';
            $service = get_post_meta($job->ID, '_sd_service_type', true);
            $service_label = SD_Post_Types::SERVICE_TYPES[$service] ?? $service;
            $color = SD_Post_Types::get_stage_color($stage);
            $edit_url = get_edit_post_link($job->ID);
            echo '<a href="' . esc_url($edit_url) . '" class="sd-recent-job-row">';
            echo '<span class="sd-rj-dot" style="background:' . esc_attr($color) . '"></span>';
            echo '<span class="sd-rj-title">' . esc_html($job->post_title ?: $client) . '</span>';
            echo '<span class="sd-rj-service">' . esc_html($service_label) . '</span>';
            echo '<span class="sd-rj-badge" style="background:' . esc_attr($color) . '20;color:' . esc_attr($color) . '">' . esc_html(SD_Post_Types::get_stage_label($stage)) . '</span>';
            echo '</a>';
        }
        echo '</div>';
        ?>
        <style>
            .sd-recent-jobs { display: flex; flex-direction: column; gap: 4px; }
            .sd-recent-job-row { display: flex; align-items: center; gap: 10px; padding: 8px; border-radius: 6px; text-decoration: none; color: inherit; transition: background 0.15s; }
            .sd-recent-job-row:hover { background: #f1f5f9; }
            .sd-rj-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
            .sd-rj-title { font-weight: 500; font-size: 13px; flex: 1; color: #1e293b; }
            .sd-rj-service { font-size: 12px; color: #64748b; }
            .sd-rj-badge { font-size: 11px; padding: 2px 8px; border-radius: 10px; font-weight: 500; white-space: nowrap; }
        </style>
        <?php
    }

    public static function render_pipeline_page() {
        ?>
        <div class="wrap sd-pipeline-wrap">
            <div class="sd-pipeline-header">
                <div class="sd-pipeline-header-left">
                    <h1><span class="dashicons dashicons-networking"></span> Service Dispatch Pipeline</h1>
                    <span class="sd-pipeline-subtitle">Drag jobs between stages to update their status</span>
                </div>
                <div class="sd-pipeline-header-right">
                    <a href="<?php echo admin_url('post-new.php?post_type=sd_job'); ?>" class="button button-primary sd-btn-add">
                        <span class="dashicons dashicons-plus-alt2"></span> New Job
                    </a>
                    <button type="button" class="button sd-btn-refresh" id="sd-refresh-pipeline">
                        <span class="dashicons dashicons-update"></span> Refresh
                    </button>
                </div>
            </div>

            <div class="sd-pipeline-stats">
                <?php
                $stat_stages = ['new-request', 'claimed', 'scheduled', 'completed-review', 'closed-paid'];
                foreach ($stat_stages as $sk):
                    $count = self::count_jobs_by_stage($sk);
                    $color = SD_Post_Types::get_stage_color($sk);
                ?>
                <div class="sd-stat-card" style="--stat-color: <?php echo $color; ?>">
                    <span class="sd-stat-num"><?php echo $count; ?></span>
                    <span class="sd-stat-label"><?php echo SD_Post_Types::get_stage_label($sk); ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="sd-pipeline-board" id="sd-pipeline-board">
                <?php foreach (SD_Post_Types::STAGES as $stage_key => $stage_label):
                    $color = SD_Post_Types::get_stage_color($stage_key);
                    $icon  = SD_Post_Types::get_stage_icon($stage_key);
                    $jobs  = self::get_jobs_by_stage($stage_key);
                ?>
                <div class="sd-pipeline-column" data-stage="<?php echo esc_attr($stage_key); ?>">
                    <div class="sd-column-header" style="--col-color: <?php echo $color; ?>">
                        <div class="sd-column-title">
                            <span class="dashicons <?php echo $icon; ?>"></span>
                            <span><?php echo esc_html($stage_label); ?></span>
                        </div>
                        <span class="sd-column-count"><?php echo count($jobs); ?></span>
                    </div>
                    <div class="sd-column-body sd-sortable" data-stage="<?php echo esc_attr($stage_key); ?>">
                        <?php foreach ($jobs as $job):
                            echo self::render_pipeline_card($job);
                        endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    private static function render_pipeline_card($job) {
        $id      = $job->ID;
        $stage   = get_post_meta($id, '_sd_stage', true) ?: 'new-request';
        $client  = get_post_meta($id, '_sd_client_name', true) ?: 'Unknown Client';
        $company = get_post_meta($id, '_sd_company_name', true);
        $stype   = get_post_meta($id, '_sd_service_type', true);
        $urgency = get_post_meta($id, '_sd_urgency', true);
        $price   = get_post_meta($id, '_sd_client_price', true);
        $vendor_id = get_post_meta($id, '_sd_assigned_vendor', true);
        $city    = get_post_meta($id, '_sd_city', true);
        $date    = get_post_meta($id, '_sd_preferred_date', true);

        $service_label = SD_Post_Types::SERVICE_TYPES[$stype] ?? '';
        $urgency_label = SD_Post_Types::URGENCY_LEVELS[$urgency] ?? '';
        $vendor_name = '';
        if ($vendor_id) {
            $v = get_userdata($vendor_id);
            $vendor_name = $v ? $v->display_name : '';
        }

        $urgency_class = '';
        if ($urgency === 'urgent') $urgency_class = 'sd-urgency-urgent';
        elseif ($urgency === 'priority') $urgency_class = 'sd-urgency-priority';

        $edit_url = get_edit_post_link($id);

        ob_start();
        ?>
        <div class="sd-pipeline-card <?php echo $urgency_class; ?>" data-job-id="<?php echo $id; ?>">
            <div class="sd-card-top">
                <span class="sd-card-id">#<?php echo $id; ?></span>
                <?php if ($urgency === 'urgent'): ?>
                    <span class="sd-card-urgency urgent">URGENT</span>
                <?php elseif ($urgency === 'priority'): ?>
                    <span class="sd-card-urgency priority">PRIORITY</span>
                <?php endif; ?>
            </div>
            <a href="<?php echo esc_url($edit_url); ?>" class="sd-card-title"><?php echo esc_html($job->post_title ?: $client); ?></a>
            <?php if ($company): ?>
                <span class="sd-card-company"><?php echo esc_html($company); ?></span>
            <?php endif; ?>
            <?php if ($service_label): ?>
                <div class="sd-card-service">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php echo esc_html($service_label); ?>
                </div>
            <?php endif; ?>
            <div class="sd-card-meta">
                <?php if ($city): ?>
                    <span><span class="dashicons dashicons-location"></span> <?php echo esc_html($city); ?></span>
                <?php endif; ?>
                <?php if ($date): ?>
                    <span><span class="dashicons dashicons-calendar"></span> <?php echo esc_html(date('M j', strtotime($date))); ?></span>
                <?php endif; ?>
                <?php if ($price): ?>
                    <span><span class="dashicons dashicons-money-alt"></span> $<?php echo esc_html(number_format((float)$price, 2)); ?></span>
                <?php endif; ?>
            </div>
            <?php if ($vendor_name): ?>
                <div class="sd-card-vendor">
                    <span class="dashicons dashicons-businessman"></span>
                    <span><?php echo esc_html($vendor_name); ?></span>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function render_vendors_page() {
        $vendors = get_users(['role' => 'sd_vendor', 'orderby' => 'display_name']);
        ?>
        <div class="wrap sd-vendors-wrap">
            <div class="sd-page-header">
                <h1><span class="dashicons dashicons-groups"></span> Vendors</h1>
                <a href="<?php echo admin_url('user-new.php'); ?>" class="button button-primary"><span class="dashicons dashicons-plus-alt2"></span> Add Vendor</a>
            </div>

            <div class="sd-vendors-grid">
                <?php if (empty($vendors)): ?>
                    <div class="sd-empty-state">
                        <span class="dashicons dashicons-groups"></span>
                        <h3>No vendors yet</h3>
                        <p>Add your first vendor to start dispatching jobs.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($vendors as $vendor):
                        $phone = get_user_meta($vendor->ID, 'sd_vendor_phone', true);
                        $service_types = get_user_meta($vendor->ID, 'sd_vendor_services', true);
                        $status = get_user_meta($vendor->ID, 'sd_vendor_status', true) ?: 'active';
                        $jobs_completed = self::count_vendor_jobs($vendor->ID, 'closed-paid');
                        $jobs_active = self::count_vendor_jobs($vendor->ID);
                    ?>
                    <div class="sd-vendor-card">
                        <div class="sd-vc-header">
                            <div class="sd-vc-avatar"><?php echo get_avatar($vendor->ID, 48); ?></div>
                            <div class="sd-vc-info">
                                <h3><?php echo esc_html($vendor->display_name); ?></h3>
                                <span class="sd-vc-email"><?php echo esc_html($vendor->user_email); ?></span>
                            </div>
                            <span class="sd-vc-status sd-vc-status-<?php echo $status; ?>"><?php echo ucfirst($status); ?></span>
                        </div>
                        <?php if ($phone): ?>
                        <div class="sd-vc-detail"><span class="dashicons dashicons-phone"></span> <?php echo esc_html($phone); ?></div>
                        <?php endif; ?>
                        <div class="sd-vc-stats">
                            <div class="sd-vc-stat">
                                <span class="sd-vc-stat-num"><?php echo $jobs_active; ?></span>
                                <span class="sd-vc-stat-label">Active</span>
                            </div>
                            <div class="sd-vc-stat">
                                <span class="sd-vc-stat-num"><?php echo $jobs_completed; ?></span>
                                <span class="sd-vc-stat-label">Completed</span>
                            </div>
                        </div>
                        <div class="sd-vc-actions">
                            <a href="<?php echo get_edit_user_link($vendor->ID); ?>" class="button button-small">Edit</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    public static function render_clients_page() {
        $clients = get_users(['role' => 'sd_client', 'orderby' => 'display_name']);
        ?>
        <div class="wrap sd-clients-wrap">
            <div class="sd-page-header">
                <h1><span class="dashicons dashicons-id-alt"></span> Clients</h1>
                <a href="<?php echo admin_url('user-new.php'); ?>" class="button button-primary"><span class="dashicons dashicons-plus-alt2"></span> Add Client</a>
            </div>
            <div class="sd-vendors-grid">
                <?php if (empty($clients)): ?>
                    <div class="sd-empty-state">
                        <span class="dashicons dashicons-id-alt"></span>
                        <h3>No clients yet</h3>
                        <p>Clients will appear here after they submit their first service request.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($clients as $client):
                        $phone = get_user_meta($client->ID, 'sd_client_phone', true);
                        $company = get_user_meta($client->ID, 'sd_client_company', true);
                        $total_jobs = count(get_posts(['post_type' => 'sd_job', 'meta_key' => '_sd_client_email', 'meta_value' => $client->user_email, 'posts_per_page' => -1, 'fields' => 'ids']));
                    ?>
                    <div class="sd-vendor-card">
                        <div class="sd-vc-header">
                            <div class="sd-vc-avatar"><?php echo get_avatar($client->ID, 48); ?></div>
                            <div class="sd-vc-info">
                                <h3><?php echo esc_html($client->display_name); ?></h3>
                                <span class="sd-vc-email"><?php echo esc_html($client->user_email); ?></span>
                            </div>
                        </div>
                        <?php if ($company): ?>
                        <div class="sd-vc-detail"><span class="dashicons dashicons-building"></span> <?php echo esc_html($company); ?></div>
                        <?php endif; ?>
                        <?php if ($phone): ?>
                        <div class="sd-vc-detail"><span class="dashicons dashicons-phone"></span> <?php echo esc_html($phone); ?></div>
                        <?php endif; ?>
                        <div class="sd-vc-stats">
                            <div class="sd-vc-stat">
                                <span class="sd-vc-stat-num"><?php echo $total_jobs; ?></span>
                                <span class="sd-vc-stat-label">Total Jobs</span>
                            </div>
                        </div>
                        <div class="sd-vc-actions">
                            <a href="<?php echo get_edit_user_link($client->ID); ?>" class="button button-small">Edit</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    public static function render_form_entries_page() {
        $service_form_id = get_option('sd_service_request_form_id');
        $vendor_form_id  = get_option('sd_vendor_form_id');
        $client_form_id  = get_option('sd_client_onboarding_form_id');
        ?>
        <div class="wrap">
            <div class="sd-page-header">
                <h1><span class="dashicons dashicons-list-view"></span> Form Entries</h1>
            </div>
            <div class="sd-form-entries-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;margin-top:20px;">
                <?php if ($service_form_id): ?>
                <a href="<?php echo admin_url('admin.php?page=fluent_forms&route=entries&form_id=' . $service_form_id); ?>" class="sd-fe-card" style="display:block;padding:24px;background:#eff6ff;border:1px solid #93c5fd;border-radius:12px;text-decoration:none;">
                    <span class="dashicons dashicons-clipboard" style="font-size:28px;color:#2563eb;margin-bottom:8px;"></span>
                    <h3 style="margin:0 0 4px;color:#1e3a8a;font-size:16px;">Service Request Entries</h3>
                    <p style="margin:0;color:#1d4ed8;font-size:13px;">View all service request submissions with photos and details</p>
                </a>
                <?php endif; ?>
                <?php if ($vendor_form_id): ?>
                <a href="<?php echo admin_url('admin.php?page=fluent_forms&route=entries&form_id=' . $vendor_form_id); ?>" class="sd-fe-card" style="display:block;padding:24px;background:#fef3c7;border:1px solid #fde68a;border-radius:12px;text-decoration:none;">
                    <span class="dashicons dashicons-groups" style="font-size:28px;color:#d97706;margin-bottom:8px;"></span>
                    <h3 style="margin:0 0 4px;color:#92400e;font-size:16px;">Vendor Registration Entries</h3>
                    <p style="margin:0;color:#a16207;font-size:13px;">View vendor registration applications</p>
                </a>
                <?php endif; ?>
                <?php if ($client_form_id): ?>
                <a href="<?php echo admin_url('admin.php?page=fluent_forms&route=entries&form_id=' . $client_form_id); ?>" class="sd-fe-card" style="display:block;padding:24px;background:#d1fae5;border:1px solid #6ee7b7;border-radius:12px;text-decoration:none;">
                    <span class="dashicons dashicons-id-alt" style="font-size:28px;color:#059669;margin-bottom:8px;"></span>
                    <h3 style="margin:0 0 4px;color:#065f46;font-size:16px;">Client Onboarding Entries</h3>
                    <p style="margin:0;color:#047857;font-size:13px;">View client onboarding submissions</p>
                </a>
                <?php endif; ?>
            </div>
            <?php if (!$service_form_id && !$vendor_form_id && !$client_form_id): ?>
            <div class="sd-empty-state">
                <span class="dashicons dashicons-list-view"></span>
                <h3>No forms created yet</h3>
                <p>Forms will be created automatically on next admin page load.</p>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public static function render_sms_log_page() {
        ?>
        <div class="wrap">
            <div class="sd-page-header">
                <h1><span class="dashicons dashicons-email-alt"></span> SMS Log</h1>
            </div>
            <div class="sd-empty-state">
                <span class="dashicons dashicons-email-alt"></span>
                <h3>SMS Log</h3>
                <p>SMS messages will appear here once Twilio integration is configured in Settings.</p>
            </div>
        </div>
        <?php
    }

    public static function render_settings_page() {
        if (isset($_POST['sd_save_settings']) && wp_verify_nonce($_POST['_sd_settings_nonce'], 'sd_save_settings')) {
            update_option('sd_twilio_sid', sanitize_text_field($_POST['sd_twilio_sid'] ?? ''));
            update_option('sd_twilio_token', sanitize_text_field($_POST['sd_twilio_token'] ?? ''));
            update_option('sd_twilio_phone', sanitize_text_field($_POST['sd_twilio_phone'] ?? ''));
            update_option('sd_admin_phone', sanitize_text_field($_POST['sd_admin_phone'] ?? ''));
            update_option('sd_admin_email', sanitize_email($_POST['sd_admin_email'] ?? ''));
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }
        $sid   = get_option('sd_twilio_sid', '');
        $token = get_option('sd_twilio_token', '');
        $phone = get_option('sd_twilio_phone', '');
        $admin_phone = get_option('sd_admin_phone', '');
        $admin_email = get_option('sd_admin_email', '');
        ?>
        <div class="wrap">
            <div class="sd-page-header">
                <h1><span class="dashicons dashicons-admin-settings"></span> Service Dispatch Settings</h1>
            </div>
            <form method="post" class="sd-settings-form">
                <?php wp_nonce_field('sd_save_settings', '_sd_settings_nonce'); ?>
                <div class="sd-settings-section">
                    <h2>Twilio SMS Configuration</h2>
                    <table class="form-table">
                        <tr><th>Account SID</th><td><input type="text" name="sd_twilio_sid" value="<?php echo esc_attr($sid); ?>" class="regular-text"></td></tr>
                        <tr><th>Auth Token</th><td><input type="password" name="sd_twilio_token" value="<?php echo esc_attr($token); ?>" class="regular-text"></td></tr>
                        <tr><th>Twilio Phone Number</th><td><input type="text" name="sd_twilio_phone" value="<?php echo esc_attr($phone); ?>" class="regular-text" placeholder="+1234567890"></td></tr>
                    </table>
                </div>
                <div class="sd-settings-section">
                    <h2>Admin Notifications</h2>
                    <table class="form-table">
                        <tr><th>Admin Phone (SMS)</th><td><input type="text" name="sd_admin_phone" value="<?php echo esc_attr($admin_phone); ?>" class="regular-text" placeholder="+1234567890"></td></tr>
                        <tr><th>Admin Email</th><td><input type="email" name="sd_admin_email" value="<?php echo esc_attr($admin_email); ?>" class="regular-text"></td></tr>
                    </table>
                </div>
                <p class="submit">
                    <button type="submit" name="sd_save_settings" class="button button-primary">Save Settings</button>
                </p>
            </form>
        </div>
        <?php
    }

    private static function count_jobs_by_stage($stage) {
        return count(get_posts([
            'post_type'      => 'sd_job',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => [['key' => '_sd_stage', 'value' => $stage]],
        ]));
    }

    private static function get_jobs_by_stage($stage) {
        return get_posts([
            'post_type'      => 'sd_job',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => [['key' => '_sd_stage', 'value' => $stage]],
        ]);
    }

    private static function count_vendor_jobs($vendor_id, $stage = null) {
        $meta_query = [['key' => '_sd_assigned_vendor', 'value' => $vendor_id]];
        if ($stage) {
            $meta_query[] = ['key' => '_sd_stage', 'value' => $stage];
        }
        return count(get_posts([
            'post_type'      => 'sd_job',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => $meta_query,
        ]));
    }
}
