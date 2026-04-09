<?php
if (!defined('ABSPATH')) exit;

class SD_Client_Dashboard {

    public static function init() {
        add_shortcode('sd_client_dashboard', [__CLASS__, 'render_dashboard']);
    }

    public static function render_dashboard() {
        if (!is_user_logged_in()) {
            return '<div class="sd-login-prompt"><div class="sd-login-card"><span class="dashicons dashicons-lock"></span><h2>Please Log In</h2><p>Log in to access your service dashboard.</p><a href="' . wp_login_url(get_permalink()) . '" class="sd-btn sd-btn-primary">Log In</a></div></div>';
        }

        $user = wp_get_current_user();
        if (!in_array('sd_client', $user->roles) && !in_array('administrator', $user->roles)) {
            return '<div class="sd-access-denied"><h2>Access Denied</h2><p>This dashboard is for clients only.</p></div>';
        }

        $client_id = $user->ID;
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';

        $active  = self::count_client_jobs($client_id, $user, ['new-request', 'approved-priced', 'posted-to-vendors', 'claimed', 'scheduled']);
        $pending = self::count_client_jobs($client_id, $user, ['completed-review', 'ready-to-invoice']);
        $closed  = self::count_client_jobs($client_id, $user, ['closed-paid']);

        $tabs = [
            'overview' => ['icon' => 'dashicons-dashboard', 'label' => 'Overview'],
            'active'   => ['icon' => 'dashicons-clipboard', 'label' => 'Active Requests', 'count' => $active],
            'invoices' => ['icon' => 'dashicons-money-alt', 'label' => 'Invoices', 'count' => $pending],
            'history'  => ['icon' => 'dashicons-backup', 'label' => 'History'],
            'profile'  => ['icon' => 'dashicons-admin-users', 'label' => 'Profile'],
        ];

        ob_start();
        ?>
        <div class="sd-saas-wrap sd-client-dash">
            <div class="sd-saas-sidebar">
                <div class="sd-sidebar-header">
                    <div class="sd-sidebar-avatar"><?php echo get_avatar($client_id, 44); ?></div>
                    <div class="sd-sidebar-info">
                        <strong><?php echo esc_html($user->display_name); ?></strong>
                        <span>Client</span>
                    </div>
                </div>
                <nav class="sd-sidebar-nav">
                    <?php foreach ($tabs as $key => $t): ?>
                    <a href="?tab=<?php echo $key; ?>" class="sd-nav-item <?php echo $tab === $key ? 'active' : ''; ?>">
                        <span class="dashicons <?php echo $t['icon']; ?>"></span>
                        <span class="sd-nav-label"><?php echo $t['label']; ?></span>
                        <?php if (isset($t['count']) && $t['count'] > 0): ?>
                            <span class="sd-nav-badge"><?php echo $t['count']; ?></span>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                    <a href="<?php echo esc_url(home_url('/service-request/')); ?>" class="sd-nav-item sd-nav-cta">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <span class="sd-nav-label">New Request</span>
                    </a>
                </nav>
                <div class="sd-sidebar-footer">
                    <a href="<?php echo wp_logout_url(home_url()); ?>" class="sd-nav-item sd-nav-logout">
                        <span class="dashicons dashicons-exit"></span>
                        <span class="sd-nav-label">Logout</span>
                    </a>
                </div>
            </div>

            <div class="sd-saas-main">
                <div class="sd-main-header">
                    <h1><?php echo esc_html($tabs[$tab]['label'] ?? 'Overview'); ?></h1>
                    <?php if ($tab === 'overview' || $tab === 'active'): ?>
                        <a href="<?php echo esc_url(home_url('/service-request/')); ?>" class="sd-btn sd-btn-primary"><span class="dashicons dashicons-plus-alt"></span> New Request</a>
                    <?php endif; ?>
                </div>

                <?php if ($tab === 'overview'): ?>
                    <div class="sd-stats-row">
                        <div class="sd-stat-card sd-stat-blue"><span class="sd-stat-icon dashicons dashicons-clipboard"></span><div><span class="sd-stat-num"><?php echo $active; ?></span><span class="sd-stat-label">Active Requests</span></div></div>
                        <div class="sd-stat-card sd-stat-orange"><span class="sd-stat-icon dashicons dashicons-money-alt"></span><div><span class="sd-stat-num"><?php echo $pending; ?></span><span class="sd-stat-label">Pending Invoice</span></div></div>
                        <div class="sd-stat-card sd-stat-green"><span class="sd-stat-icon dashicons dashicons-yes-alt"></span><div><span class="sd-stat-num"><?php echo $closed; ?></span><span class="sd-stat-label">Completed</span></div></div>
                    </div>
                    <?php self::render_overview($client_id, $user); ?>
                <?php elseif ($tab === 'active'): self::render_active_requests($client_id, $user); ?>
                <?php elseif ($tab === 'invoices'): self::render_invoices($client_id, $user); ?>
                <?php elseif ($tab === 'history'): self::render_history($client_id, $user); ?>
                <?php elseif ($tab === 'profile'): self::render_profile($client_id); ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function render_overview($client_id, $user) {
        $recent = self::get_client_jobs($client_id, $user, [], 5);
        $latest = !empty($recent) ? $recent[0] : null;
        $current_stage = $latest ? (get_post_meta($latest->ID, '_sd_stage', true) ?: 'new-request') : '';

        $stages_display = [
            'new-request'       => ['Submitted', 'Your request has been received', 'dashicons-plus-alt'],
            'approved-priced'   => ['Approved', 'Pricing confirmed, finding provider', 'dashicons-yes-alt'],
            'posted-to-vendors' => ['Dispatching', 'Looking for available providers', 'dashicons-megaphone'],
            'claimed'           => ['Provider Found', 'A provider has claimed your job', 'dashicons-flag'],
            'scheduled'         => ['Scheduled', 'Provider confirmed — service is scheduled', 'dashicons-calendar-alt'],
            'completed-review'  => ['Completed', 'Service completed — under review', 'dashicons-clipboard'],
            'ready-to-invoice'  => ['Invoice Sent', 'Invoice ready for payment', 'dashicons-money-alt'],
            'closed-paid'       => ['Closed', 'Payment received — job complete', 'dashicons-shield-alt'],
        ];
        ?>
        <div class="sd-overview-grid">
            <div class="sd-content-card">
                <h3>Service Request Tracker</h3>
                <?php if (!$latest): ?>
                    <p class="sd-muted">Submit your first service request to see the tracker.</p>
                <?php else: ?>
                    <div class="sd-tracker">
                        <?php
                        $found = false;
                        foreach ($stages_display as $skey => $sinfo):
                            $is_current = ($skey === $current_stage);
                            $is_past = !$found && !$is_current && $latest;
                            if ($is_current) $found = true;
                            $cls = $is_current ? 'current' : ($is_past ? 'completed' : 'pending');
                        ?>
                        <div class="sd-tracker-step <?php echo $cls; ?>">
                            <div class="sd-tracker-dot"></div>
                            <div class="sd-tracker-info">
                                <strong><?php echo esc_html($sinfo[0]); ?></strong>
                                <span><?php echo esc_html($sinfo[1]); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php
                    $admin_notes = get_post_meta($latest->ID, '_sd_admin_notes', true);
                    $admin_status = get_post_meta($latest->ID, '_sd_admin_status', true);
                    if ($admin_notes || $admin_status === 'rejected' || $admin_status === 'needs-revision'): ?>
                    <div class="sd-admin-feedback <?php echo $admin_status; ?>">
                        <strong><?php echo $admin_status === 'rejected' ? 'Request Rejected' : 'Admin Notes'; ?></strong>
                        <?php if ($admin_notes): ?><p><?php echo esc_html($admin_notes); ?></p><?php endif; ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="sd-content-card">
                <h3>Recent Requests</h3>
                <?php if (empty($recent)): ?>
                    <p class="sd-muted">No requests yet.</p>
                <?php else: ?>
                    <div class="sd-list">
                        <?php foreach ($recent as $job):
                            $stage = get_post_meta($job->ID, '_sd_stage', true) ?: 'new-request';
                            $stype = get_post_meta($job->ID, '_sd_service_type', true);
                            $color = SD_Post_Types::get_stage_color($stage);
                            $astatus = get_post_meta($job->ID, '_sd_admin_status', true);
                        ?>
                        <div class="sd-list-row">
                            <span class="sd-list-dot" style="background:<?php echo $color; ?>"></span>
                            <div class="sd-list-info">
                                <strong>#<?php echo $job->ID; ?> — <?php echo esc_html(SD_Post_Types::SERVICE_TYPES[$stype] ?? $stype); ?></strong>
                                <span><?php echo get_the_date('M j, Y', $job); ?></span>
                            </div>
                            <?php if ($astatus === 'rejected'): ?>
                                <span class="sd-badge" style="background:#fef2f2;color:#ef4444;">Rejected</span>
                            <?php elseif ($astatus === 'needs-revision'): ?>
                                <span class="sd-badge" style="background:#fffbeb;color:#d97706;">Needs Revision</span>
                            <?php else: ?>
                                <span class="sd-badge" style="background:<?php echo $color; ?>20;color:<?php echo $color; ?>"><?php echo SD_Post_Types::get_stage_label($stage); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    private static function render_active_requests($client_id, $user) {
        $jobs = self::get_client_jobs($client_id, $user, ['new-request', 'approved-priced', 'posted-to-vendors', 'claimed', 'scheduled']);
        if (empty($jobs)) {
            echo '<div class="sd-empty-state"><span class="dashicons dashicons-clipboard"></span><h3>No active requests</h3><p>Submit a new service request to get started.</p></div>';
            return;
        }
        echo '<div class="sd-job-grid">';
        foreach ($jobs as $job) { echo self::render_client_card($job); }
        echo '</div>';
    }

    private static function render_invoices($client_id, $user) {
        $jobs = self::get_client_jobs($client_id, $user, ['ready-to-invoice', 'closed-paid']);
        if (empty($jobs)) {
            echo '<div class="sd-empty-state"><span class="dashicons dashicons-money-alt"></span><h3>No invoices yet</h3><p>Invoices will appear here when services are completed.</p></div>';
            return;
        }
        echo '<div class="sd-content-card"><table class="sd-table"><thead><tr><th>Job #</th><th>Service</th><th>Date</th><th>Amount</th><th>Status</th></tr></thead><tbody>';
        foreach ($jobs as $job) {
            $stage = get_post_meta($job->ID, '_sd_stage', true);
            $stype = get_post_meta($job->ID, '_sd_service_type', true);
            $price = get_post_meta($job->ID, '_sd_client_price', true);
            $is_paid = ($stage === 'closed-paid');
            echo '<tr>';
            echo '<td><strong>#' . $job->ID . '</strong></td>';
            echo '<td>' . esc_html(SD_Post_Types::SERVICE_TYPES[$stype] ?? $stype) . '</td>';
            echo '<td>' . get_the_date('M j, Y', $job) . '</td>';
            echo '<td>' . ($price ? '$' . number_format((float)$price, 2) : '—') . '</td>';
            echo '<td><span class="sd-badge ' . ($is_paid ? 'sd-badge-green' : 'sd-badge-orange') . '">' . ($is_paid ? 'PAID' : 'PENDING') . '</span></td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }

    private static function render_history($client_id, $user) {
        $jobs = self::get_client_jobs($client_id, $user, [], 50);
        if (empty($jobs)) {
            echo '<div class="sd-empty-state"><span class="dashicons dashicons-backup"></span><h3>No history yet</h3><p>Your past service requests will appear here.</p></div>';
            return;
        }
        echo '<div class="sd-content-card"><table class="sd-table"><thead><tr><th>Job #</th><th>Service</th><th>Date</th><th>Amount</th><th>Status</th></tr></thead><tbody>';
        foreach ($jobs as $job) {
            $stage = get_post_meta($job->ID, '_sd_stage', true) ?: 'new-request';
            $stype = get_post_meta($job->ID, '_sd_service_type', true);
            $price = get_post_meta($job->ID, '_sd_client_price', true);
            $color = SD_Post_Types::get_stage_color($stage);
            echo '<tr>';
            echo '<td><strong>#' . $job->ID . '</strong></td>';
            echo '<td>' . esc_html(SD_Post_Types::SERVICE_TYPES[$stype] ?? $stype) . '</td>';
            echo '<td>' . get_the_date('M j, Y', $job) . '</td>';
            echo '<td>' . ($price ? '$' . number_format((float)$price, 2) : '—') . '</td>';
            echo '<td><span class="sd-badge" style="background:' . $color . '20;color:' . $color . '">' . SD_Post_Types::get_stage_label($stage) . '</span></td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }

    private static function render_profile($client_id) {
        $user = get_userdata($client_id);
        $phone = get_user_meta($client_id, 'sd_client_phone', true);
        $company = get_user_meta($client_id, 'sd_client_company', true);
        $billing_email = get_user_meta($client_id, 'sd_client_billing_email', true);
        if (isset($_POST['sd_client_save_profile']) && wp_verify_nonce($_POST['_sd_client_profile_nonce'], 'sd_client_profile')) {
            $phone = sanitize_text_field($_POST['sd_client_phone'] ?? '');
            $company = sanitize_text_field($_POST['sd_client_company'] ?? '');
            $billing_email = sanitize_email($_POST['sd_client_billing_email'] ?? '');
            update_user_meta($client_id, 'sd_client_phone', $phone);
            update_user_meta($client_id, 'sd_client_company', $company);
            update_user_meta($client_id, 'sd_client_billing_email', $billing_email);
            echo '<div class="sd-notice sd-notice-success">Profile updated!</div>';
        }
        ?>
        <div class="sd-content-card" style="max-width:640px;">
            <h3>My Profile</h3>
            <form method="post" class="sd-form">
                <?php wp_nonce_field('sd_client_profile', '_sd_client_profile_nonce'); ?>
                <div class="sd-form-group"><label>Full Name</label><input type="text" value="<?php echo esc_attr($user->display_name); ?>" disabled class="sd-input"></div>
                <div class="sd-form-group"><label>Email</label><input type="email" value="<?php echo esc_attr($user->user_email); ?>" disabled class="sd-input"></div>
                <div class="sd-form-group"><label>Company Name</label><input type="text" name="sd_client_company" value="<?php echo esc_attr($company); ?>" class="sd-input"></div>
                <div class="sd-form-group"><label>Phone Number</label><input type="text" name="sd_client_phone" value="<?php echo esc_attr($phone); ?>" class="sd-input" placeholder="+1234567890"></div>
                <div class="sd-form-group"><label>Billing Email</label><input type="email" name="sd_client_billing_email" value="<?php echo esc_attr($billing_email); ?>" class="sd-input" placeholder="accounts@company.com"></div>
                <button type="submit" name="sd_client_save_profile" class="sd-btn sd-btn-primary">Save Profile</button>
            </form>
        </div>
        <?php
    }

    private static function render_client_card($job) {
        $id = $job->ID;
        $stage = get_post_meta($id, '_sd_stage', true) ?: 'new-request';
        $stype = get_post_meta($id, '_sd_service_type', true);
        $city = get_post_meta($id, '_sd_city', true);
        $state = get_post_meta($id, '_sd_state', true);
        $urgency = get_post_meta($id, '_sd_urgency', true);
        $price = get_post_meta($id, '_sd_client_price', true);
        $date = get_post_meta($id, '_sd_preferred_date', true);
        $admin_notes = get_post_meta($id, '_sd_admin_notes', true);
        $admin_status = get_post_meta($id, '_sd_admin_status', true);
        $desc = $job->post_content;

        $service_label = SD_Post_Types::SERVICE_TYPES[$stype] ?? $stype;
        $stage_color = SD_Post_Types::get_stage_color($stage);
        $stage_label = SD_Post_Types::get_stage_label($stage);
        $stage_order = array_keys(SD_Post_Types::STAGES);
        $current_idx = array_search($stage, $stage_order);
        $pct = $current_idx !== false ? round(($current_idx / (count($stage_order) - 1)) * 100) : 0;

        ob_start();
        ?>
        <div class="sd-job-card">
            <div class="sd-jc-top">
                <span class="sd-jc-id">#<?php echo $id; ?></span>
                <?php if ($urgency === 'urgent'): ?><span class="sd-urgency-tag urgent">URGENT</span><?php elseif ($urgency === 'priority'): ?><span class="sd-urgency-tag priority">PRIORITY</span><?php endif; ?>
                <?php if ($admin_status === 'rejected'): ?>
                    <span class="sd-badge" style="background:#fef2f2;color:#ef4444;">Rejected</span>
                <?php elseif ($admin_status === 'needs-revision'): ?>
                    <span class="sd-badge" style="background:#fffbeb;color:#d97706;">Needs Revision</span>
                <?php else: ?>
                    <span class="sd-badge" style="background:<?php echo $stage_color; ?>20;color:<?php echo $stage_color; ?>"><?php echo esc_html($stage_label); ?></span>
                <?php endif; ?>
            </div>
            <h4 class="sd-jc-title"><?php echo esc_html($service_label); ?></h4>
            <?php if ($desc): ?><p class="sd-jc-desc"><?php echo esc_html(wp_trim_words($desc, 20)); ?></p><?php endif; ?>
            <div class="sd-jc-meta">
                <?php if ($city || $state): ?><div><span class="dashicons dashicons-location"></span> <?php echo esc_html(trim("$city, $state", ', ')); ?></div><?php endif; ?>
                <?php if ($price): ?><div><span class="dashicons dashicons-money-alt"></span> $<?php echo number_format((float)$price, 2); ?></div><?php endif; ?>
                <?php if ($date): ?><div><span class="dashicons dashicons-calendar-alt"></span> <?php echo date('M j, Y', strtotime($date)); ?></div><?php endif; ?>
            </div>
            <?php if ($admin_notes): ?>
                <div class="sd-admin-feedback <?php echo $admin_status; ?>">
                    <strong>Admin Notes:</strong>
                    <p><?php echo esc_html($admin_notes); ?></p>
                </div>
            <?php endif; ?>
            <div class="sd-progress-wrap">
                <div class="sd-progress-bar"><div class="sd-progress-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $stage_color; ?>"></div></div>
                <span class="sd-progress-text"><?php echo $pct; ?>% complete</span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function get_client_jobs($client_id, $user, $stages = [], $limit = -1) {
        $meta_query = [
            'relation' => 'OR',
            ['key' => '_sd_client_email', 'value' => $user->user_email],
            ['key' => '_sd_submitted_by', 'value' => $client_id],
        ];
        $args = ['post_type' => 'sd_job', 'posts_per_page' => $limit, 'orderby' => 'date', 'order' => 'DESC', 'meta_query' => [$meta_query]];
        if (!empty($stages)) {
            $args['meta_query'][] = ['key' => '_sd_stage', 'value' => $stages, 'compare' => 'IN'];
        }
        return get_posts($args);
    }

    private static function count_client_jobs($client_id, $user, $stages = []) {
        $jobs = self::get_client_jobs($client_id, $user, $stages, -1);
        return count($jobs);
    }
}
