<?php
if (!defined('ABSPATH')) exit;

class SD_Vendor_Dashboard {

    public static function init() {
        add_shortcode('sd_vendor_dashboard', [__CLASS__, 'render_dashboard']);
    }

    public static function render_dashboard() {
        if (!is_user_logged_in()) {
            return '<div class="sd-login-prompt"><div class="sd-login-card"><span class="dashicons dashicons-lock"></span><h2>Please Log In</h2><p>You need to be logged in as a vendor to access this dashboard.</p><a href="' . wp_login_url(get_permalink()) . '" class="sd-btn sd-btn-primary">Log In</a></div></div>';
        }

        $user = wp_get_current_user();
        if (!in_array('sd_vendor', $user->roles) && !in_array('administrator', $user->roles)) {
            return '<div class="sd-access-denied"><h2>Access Denied</h2><p>This dashboard is for vendors only.</p></div>';
        }

        $vendor_id = $user->ID;
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';

        $available = self::count_available_jobs();
        $my_active = self::count_my_jobs($vendor_id, ['claimed', 'scheduled']);
        $completed = self::count_my_jobs($vendor_id, ['completed-review', 'ready-to-invoice', 'closed-paid']);

        $tabs = [
            'dashboard'  => ['icon' => 'dashicons-dashboard', 'label' => 'Dashboard'],
            'available'  => ['icon' => 'dashicons-megaphone', 'label' => 'Available Jobs', 'count' => $available],
            'my-jobs'    => ['icon' => 'dashicons-clipboard', 'label' => 'My Active Jobs', 'count' => $my_active],
            'completed'  => ['icon' => 'dashicons-yes-alt', 'label' => 'Completed', 'count' => $completed],
            'earnings'   => ['icon' => 'dashicons-money-alt', 'label' => 'Earnings'],
            'profile'    => ['icon' => 'dashicons-admin-users', 'label' => 'Profile'],
        ];

        ob_start();
        ?>
        <div class="sd-saas-wrap sd-vendor-dash">
            <div class="sd-saas-sidebar">
                <div class="sd-sidebar-header">
                    <div class="sd-sidebar-avatar"><?php echo get_avatar($vendor_id, 44); ?></div>
                    <div class="sd-sidebar-info">
                        <strong><?php echo esc_html($user->display_name); ?></strong>
                        <span>Vendor</span>
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
                    <h1><?php echo esc_html($tabs[$tab]['label'] ?? 'Dashboard'); ?></h1>
                </div>

                <?php if ($tab === 'dashboard'): ?>
                    <div class="sd-stats-row">
                        <div class="sd-stat-card sd-stat-blue">
                            <span class="sd-stat-icon dashicons dashicons-megaphone"></span>
                            <div><span class="sd-stat-num"><?php echo $available; ?></span><span class="sd-stat-label">Available Jobs</span></div>
                        </div>
                        <div class="sd-stat-card sd-stat-orange">
                            <span class="sd-stat-icon dashicons dashicons-clipboard"></span>
                            <div><span class="sd-stat-num"><?php echo $my_active; ?></span><span class="sd-stat-label">My Active Jobs</span></div>
                        </div>
                        <div class="sd-stat-card sd-stat-green">
                            <span class="sd-stat-icon dashicons dashicons-yes-alt"></span>
                            <div><span class="sd-stat-num"><?php echo $completed; ?></span><span class="sd-stat-label">Completed</span></div>
                        </div>
                    </div>
                    <?php self::render_recent_activity($vendor_id); ?>
                <?php elseif ($tab === 'available'): self::render_available_jobs(); ?>
                <?php elseif ($tab === 'my-jobs'): self::render_my_jobs($vendor_id); ?>
                <?php elseif ($tab === 'completed'): self::render_completed_jobs($vendor_id); ?>
                <?php elseif ($tab === 'earnings'): self::render_earnings($vendor_id); ?>
                <?php elseif ($tab === 'profile'): self::render_profile($vendor_id); ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function render_recent_activity($vendor_id) {
        $recent = get_posts([
            'post_type' => 'sd_job', 'posts_per_page' => 5, 'orderby' => 'date', 'order' => 'DESC',
            'meta_query' => [['key' => '_sd_assigned_vendor', 'value' => $vendor_id]],
        ]);
        ?>
        <div class="sd-content-card">
            <h3>Recent Activity</h3>
            <?php if (empty($recent)): ?>
                <p class="sd-muted">No activity yet. Check Available Jobs to get started.</p>
            <?php else: ?>
                <div class="sd-list">
                    <?php foreach ($recent as $job):
                        $stage = get_post_meta($job->ID, '_sd_stage', true) ?: 'new-request';
                        $stype = get_post_meta($job->ID, '_sd_service_type', true);
                        $color = SD_Post_Types::get_stage_color($stage);
                    ?>
                    <div class="sd-list-row">
                        <span class="sd-list-dot" style="background:<?php echo $color; ?>"></span>
                        <div class="sd-list-info">
                            <strong>#<?php echo $job->ID; ?> — <?php echo esc_html(SD_Post_Types::SERVICE_TYPES[$stype] ?? $stype); ?></strong>
                            <span><?php echo get_the_date('M j, Y', $job); ?></span>
                        </div>
                        <span class="sd-badge" style="background:<?php echo $color; ?>20;color:<?php echo $color; ?>"><?php echo SD_Post_Types::get_stage_label($stage); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function render_available_jobs() {
        $jobs = get_posts([
            'post_type' => 'sd_job', 'posts_per_page' => -1,
            'meta_query' => [
                ['key' => '_sd_stage', 'value' => 'posted-to-vendors'],
                ['relation' => 'OR', ['key' => '_sd_assigned_vendor', 'compare' => 'NOT EXISTS'], ['key' => '_sd_assigned_vendor', 'value' => '']],
            ],
        ]);
        if (empty($jobs)) {
            echo '<div class="sd-empty-state"><span class="dashicons dashicons-megaphone"></span><h3>No available jobs right now</h3><p>New job opportunities will appear here when posted.</p></div>';
            return;
        }
        echo '<div class="sd-job-grid">';
        foreach ($jobs as $job) { echo self::render_job_card($job, 'available'); }
        echo '</div>';
    }

    private static function render_my_jobs($vendor_id) {
        $jobs = get_posts([
            'post_type' => 'sd_job', 'posts_per_page' => -1,
            'meta_query' => [['key' => '_sd_assigned_vendor', 'value' => $vendor_id], ['key' => '_sd_stage', 'value' => ['claimed', 'scheduled'], 'compare' => 'IN']],
        ]);
        if (empty($jobs)) {
            echo '<div class="sd-empty-state"><span class="dashicons dashicons-clipboard"></span><h3>No active jobs</h3><p>Claim a job from Available Jobs to get started.</p></div>';
            return;
        }
        echo '<div class="sd-job-grid">';
        foreach ($jobs as $job) { echo self::render_job_card($job, 'active'); }
        echo '</div>';
    }

    private static function render_completed_jobs($vendor_id) {
        $jobs = get_posts([
            'post_type' => 'sd_job', 'posts_per_page' => 30,
            'meta_query' => [['key' => '_sd_assigned_vendor', 'value' => $vendor_id], ['key' => '_sd_stage', 'value' => ['completed-review', 'ready-to-invoice', 'closed-paid'], 'compare' => 'IN']],
        ]);
        if (empty($jobs)) {
            echo '<div class="sd-empty-state"><span class="dashicons dashicons-yes-alt"></span><h3>No completed jobs yet</h3><p>Completed jobs and payment status will appear here.</p></div>';
            return;
        }
        echo '<div class="sd-content-card"><table class="sd-table"><thead><tr><th>Job #</th><th>Service</th><th>City</th><th>Pay</th><th>Status</th></tr></thead><tbody>';
        foreach ($jobs as $job) {
            $stage = get_post_meta($job->ID, '_sd_stage', true);
            $stype = get_post_meta($job->ID, '_sd_service_type', true);
            $city  = get_post_meta($job->ID, '_sd_city', true);
            $pay   = get_post_meta($job->ID, '_sd_vendor_pay', true);
            $color = SD_Post_Types::get_stage_color($stage);
            echo '<tr>';
            echo '<td><strong>#' . $job->ID . '</strong></td>';
            echo '<td>' . esc_html(SD_Post_Types::SERVICE_TYPES[$stype] ?? $stype) . '</td>';
            echo '<td>' . esc_html($city ?: '—') . '</td>';
            echo '<td>' . ($pay ? '$' . number_format((float)$pay, 2) : '—') . '</td>';
            echo '<td><span class="sd-badge" style="background:' . $color . '20;color:' . $color . '">' . SD_Post_Types::get_stage_label($stage) . '</span></td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }

    private static function render_earnings($vendor_id) {
        $paid = get_posts(['post_type' => 'sd_job', 'posts_per_page' => -1, 'meta_query' => [['key' => '_sd_assigned_vendor', 'value' => $vendor_id], ['key' => '_sd_stage', 'value' => 'closed-paid']]]);
        $pending = get_posts(['post_type' => 'sd_job', 'posts_per_page' => -1, 'meta_query' => [['key' => '_sd_assigned_vendor', 'value' => $vendor_id], ['key' => '_sd_stage', 'value' => ['completed-review', 'ready-to-invoice'], 'compare' => 'IN']]]);
        $total_earned = 0; $total_pending = 0;
        foreach ($paid as $j) $total_earned += (float)(get_post_meta($j->ID, '_sd_vendor_pay', true) ?: 0);
        foreach ($pending as $j) $total_pending += (float)(get_post_meta($j->ID, '_sd_vendor_pay', true) ?: 0);
        ?>
        <div class="sd-stats-row">
            <div class="sd-stat-card sd-stat-green">
                <span class="sd-stat-icon dashicons dashicons-money-alt"></span>
                <div><span class="sd-stat-num">$<?php echo number_format($total_earned, 2); ?></span><span class="sd-stat-label">Total Earned (<?php echo count($paid); ?> jobs)</span></div>
            </div>
            <div class="sd-stat-card sd-stat-orange">
                <span class="sd-stat-icon dashicons dashicons-clock"></span>
                <div><span class="sd-stat-num">$<?php echo number_format($total_pending, 2); ?></span><span class="sd-stat-label">Pending (<?php echo count($pending); ?> jobs)</span></div>
            </div>
            <div class="sd-stat-card sd-stat-blue">
                <span class="sd-stat-icon dashicons dashicons-chart-area"></span>
                <div><span class="sd-stat-num"><?php echo count($paid) + count($pending); ?></span><span class="sd-stat-label">Total Jobs</span></div>
            </div>
        </div>
        <?php
    }

    private static function render_profile($vendor_id) {
        $user = get_userdata($vendor_id);
        $phone = get_user_meta($vendor_id, 'sd_vendor_phone', true);
        $services = get_user_meta($vendor_id, 'sd_vendor_services', true);
        if (!is_array($services)) $services = [];
        if (isset($_POST['sd_vendor_save_profile']) && wp_verify_nonce($_POST['_sd_profile_nonce'], 'sd_vendor_profile')) {
            $phone = sanitize_text_field($_POST['sd_vendor_phone'] ?? '');
            update_user_meta($vendor_id, 'sd_vendor_phone', $phone);
            $services = array_map('sanitize_text_field', $_POST['sd_vendor_services'] ?? []);
            update_user_meta($vendor_id, 'sd_vendor_services', $services);
            echo '<div class="sd-notice sd-notice-success">Profile updated!</div>';
        }
        ?>
        <div class="sd-content-card" style="max-width:640px;">
            <h3>My Profile</h3>
            <form method="post" class="sd-form">
                <?php wp_nonce_field('sd_vendor_profile', '_sd_profile_nonce'); ?>
                <div class="sd-form-group"><label>Full Name</label><input type="text" value="<?php echo esc_attr($user->display_name); ?>" disabled class="sd-input"></div>
                <div class="sd-form-group"><label>Email</label><input type="email" value="<?php echo esc_attr($user->user_email); ?>" disabled class="sd-input"></div>
                <div class="sd-form-group"><label>Phone Number</label><input type="text" name="sd_vendor_phone" value="<?php echo esc_attr($phone); ?>" class="sd-input" placeholder="+1234567890"></div>
                <div class="sd-form-group">
                    <label>Service Types I Handle</label>
                    <div class="sd-checkbox-grid">
                        <?php foreach (SD_Post_Types::SERVICE_TYPES as $key => $label): ?>
                        <label class="sd-checkbox-item"><input type="checkbox" name="sd_vendor_services[]" value="<?php echo esc_attr($key); ?>" <?php echo in_array($key, $services) ? 'checked' : ''; ?>><span><?php echo esc_html($label); ?></span></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="submit" name="sd_vendor_save_profile" class="sd-btn sd-btn-primary">Save Profile</button>
            </form>
        </div>
        <?php
    }

    private static function render_job_card($job, $type) {
        $id = $job->ID;
        $stype = get_post_meta($id, '_sd_service_type', true);
        $city = get_post_meta($id, '_sd_city', true);
        $state = get_post_meta($id, '_sd_state', true);
        $urgency = get_post_meta($id, '_sd_urgency', true);
        $pay = get_post_meta($id, '_sd_vendor_pay', true);
        $date = get_post_meta($id, '_sd_preferred_date', true);
        $time = get_post_meta($id, '_sd_time_window', true);
        $stage = get_post_meta($id, '_sd_stage', true);
        $address = get_post_meta($id, '_sd_service_address', true);
        $onsite = get_post_meta($id, '_sd_onsite_contact', true);
        $onsite_phone = get_post_meta($id, '_sd_onsite_phone', true);
        $access = get_post_meta($id, '_sd_access_instructions', true);
        $desc = $job->post_content;
        $photos = get_post_meta($id, '_sd_photos', true);

        $service_label = SD_Post_Types::SERVICE_TYPES[$stype] ?? $stype;
        $stage_color = SD_Post_Types::get_stage_color($stage);
        $stage_label = SD_Post_Types::get_stage_label($stage);
        $time_labels = ['morning' => 'Morning (8am–12pm)', 'afternoon' => 'Afternoon (12pm–4pm)', 'evening' => 'Evening (4pm–8pm)'];

        ob_start();
        ?>
        <div class="sd-job-card <?php echo $urgency === 'urgent' ? 'sd-card-urgent' : ($urgency === 'priority' ? 'sd-card-priority' : ''); ?>">
            <div class="sd-jc-top">
                <span class="sd-jc-id">#<?php echo $id; ?></span>
                <?php if ($urgency === 'urgent'): ?><span class="sd-urgency-tag urgent">URGENT</span><?php elseif ($urgency === 'priority'): ?><span class="sd-urgency-tag priority">PRIORITY</span><?php endif; ?>
                <span class="sd-badge" style="background:<?php echo $stage_color; ?>20;color:<?php echo $stage_color; ?>"><?php echo esc_html($stage_label); ?></span>
            </div>
            <h4 class="sd-jc-title"><?php echo esc_html($service_label); ?></h4>

            <?php if ($desc): ?><p class="sd-jc-desc"><?php echo esc_html(wp_trim_words($desc, 30)); ?></p><?php endif; ?>

            <div class="sd-jc-meta">
                <?php if ($city || $state): ?><div><span class="dashicons dashicons-location"></span> <?php echo esc_html(trim("$city, $state", ', ')); ?></div><?php endif; ?>
                <?php if ($pay): ?><div class="sd-jc-pay"><span class="dashicons dashicons-money-alt"></span> $<?php echo number_format((float)$pay, 2); ?></div><?php endif; ?>
                <?php if ($date): ?><div><span class="dashicons dashicons-calendar-alt"></span> <?php echo date('M j, Y', strtotime($date)); ?></div><?php endif; ?>
                <?php if (isset($time_labels[$time])): ?><div><span class="dashicons dashicons-clock"></span> <?php echo $time_labels[$time]; ?></div><?php endif; ?>
            </div>

            <?php if ($type === 'active'): ?>
                <div class="sd-jc-details-box">
                    <?php if ($address): ?><div><span class="dashicons dashicons-admin-home"></span> <?php echo esc_html($address); ?></div><?php endif; ?>
                    <?php if ($onsite): ?><div><span class="dashicons dashicons-admin-users"></span> <?php echo esc_html($onsite); ?> <?php if ($onsite_phone): ?>(<?php echo esc_html($onsite_phone); ?>)<?php endif; ?></div><?php endif; ?>
                    <?php if ($access): ?><div><span class="dashicons dashicons-lock"></span> <?php echo esc_html($access); ?></div><?php endif; ?>
                </div>
                <?php if (is_array($photos) && !empty($photos)): ?>
                <div class="sd-jc-photos"><?php foreach ($photos as $url): ?><a href="<?php echo esc_url($url); ?>" target="_blank"><img src="<?php echo esc_url($url); ?>" alt="Job photo"></a><?php endforeach; ?></div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="sd-jc-actions">
                <?php if ($type === 'available'): ?>
                    <button class="sd-btn sd-btn-primary sd-claim-btn" data-job-id="<?php echo $id; ?>"><span class="dashicons dashicons-flag"></span> Claim Job</button>
                <?php elseif ($type === 'active' && $stage === 'claimed'): ?>
                    <button class="sd-btn sd-btn-primary sd-confirm-btn" data-job-id="<?php echo $id; ?>"><span class="dashicons dashicons-yes"></span> Confirm (YES)</button>
                <?php elseif ($type === 'active' && $stage === 'scheduled'): ?>
                    <button class="sd-btn sd-btn-success sd-complete-btn" data-job-id="<?php echo $id; ?>"><span class="dashicons dashicons-yes-alt"></span> Mark Complete</button>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function count_available_jobs() {
        return count(get_posts(['post_type' => 'sd_job', 'posts_per_page' => -1, 'fields' => 'ids',
            'meta_query' => [['key' => '_sd_stage', 'value' => 'posted-to-vendors'], ['relation' => 'OR', ['key' => '_sd_assigned_vendor', 'compare' => 'NOT EXISTS'], ['key' => '_sd_assigned_vendor', 'value' => '']]],
        ]));
    }

    private static function count_my_jobs($vendor_id, $stages = []) {
        $mq = [['key' => '_sd_assigned_vendor', 'value' => $vendor_id]];
        if (!empty($stages)) $mq[] = ['key' => '_sd_stage', 'value' => $stages, 'compare' => 'IN'];
        return count(get_posts(['post_type' => 'sd_job', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_query' => $mq]));
    }
}
