<?php
if (!defined('ABSPATH')) exit;

class SD_Vendor_Dashboard {

    public static function init() {
        add_shortcode('sd_vendor_dashboard', [__CLASS__, 'render_dashboard']);
    }

    public static function render_dashboard() {
        if (!is_user_logged_in()) {
            return self::render_login_prompt('vendor');
        }

        $user = wp_get_current_user();
        if (!in_array('sd_vendor', $user->roles) && !in_array('administrator', $user->roles)) {
            return '<div class="sd-access-denied"><h2>Access Denied</h2><p>This dashboard is for vendors only.</p></div>';
        }

        $vendor_id = $user->ID;
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'available';

        ob_start();
        ?>
        <div class="sd-vendor-dash">
            <div class="sd-dash-header">
                <div class="sd-dash-welcome">
                    <div class="sd-dash-avatar"><?php echo get_avatar($vendor_id, 56); ?></div>
                    <div>
                        <h2>Welcome back, <?php echo esc_html($user->display_name); ?></h2>
                        <span class="sd-dash-role">Vendor Dashboard</span>
                    </div>
                </div>
                <div class="sd-dash-quick-stats">
                    <?php
                    $available = self::count_available_jobs($vendor_id);
                    $my_active = self::count_my_jobs($vendor_id, ['claimed', 'scheduled']);
                    $completed = self::count_my_jobs($vendor_id, ['completed-review', 'ready-to-invoice', 'closed-paid']);
                    ?>
                    <div class="sd-qs-card sd-qs-blue">
                        <span class="sd-qs-num"><?php echo $available; ?></span>
                        <span class="sd-qs-label">Available Jobs</span>
                    </div>
                    <div class="sd-qs-card sd-qs-orange">
                        <span class="sd-qs-num"><?php echo $my_active; ?></span>
                        <span class="sd-qs-label">My Active Jobs</span>
                    </div>
                    <div class="sd-qs-card sd-qs-green">
                        <span class="sd-qs-num"><?php echo $completed; ?></span>
                        <span class="sd-qs-label">Completed</span>
                    </div>
                </div>
            </div>

            <div class="sd-dash-tabs">
                <a href="?tab=available" class="sd-tab <?php echo $tab === 'available' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-megaphone"></span> Available Jobs
                </a>
                <a href="?tab=my-jobs" class="sd-tab <?php echo $tab === 'my-jobs' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-clipboard"></span> My Jobs
                </a>
                <a href="?tab=completed" class="sd-tab <?php echo $tab === 'completed' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-yes-alt"></span> Completed
                </a>
                <a href="?tab=earnings" class="sd-tab <?php echo $tab === 'earnings' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-money-alt"></span> Earnings
                </a>
                <a href="?tab=profile" class="sd-tab <?php echo $tab === 'profile' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-admin-users"></span> Profile
                </a>
            </div>

            <div class="sd-dash-content">
                <?php
                switch ($tab) {
                    case 'my-jobs':    self::render_my_jobs($vendor_id); break;
                    case 'completed':  self::render_completed_jobs($vendor_id); break;
                    case 'earnings':   self::render_earnings($vendor_id); break;
                    case 'profile':    self::render_profile($vendor_id); break;
                    default:           self::render_available_jobs($vendor_id); break;
                }
                ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function render_available_jobs($vendor_id) {
        $jobs = get_posts([
            'post_type'      => 'sd_job',
            'posts_per_page' => -1,
            'meta_query'     => [
                ['key' => '_sd_stage', 'value' => 'posted-to-vendors'],
                [
                    'relation' => 'OR',
                    ['key' => '_sd_assigned_vendor', 'compare' => 'NOT EXISTS'],
                    ['key' => '_sd_assigned_vendor', 'value' => ''],
                ],
            ],
        ]);
        ?>
        <div class="sd-section">
            <h3 class="sd-section-title">Available Jobs</h3>
            <?php if (empty($jobs)): ?>
                <div class="sd-empty-card">
                    <span class="dashicons dashicons-megaphone"></span>
                    <h4>No available jobs right now</h4>
                    <p>New job opportunities will appear here when posted. You'll also receive an SMS notification.</p>
                </div>
            <?php else: ?>
                <div class="sd-job-grid">
                    <?php foreach ($jobs as $job): echo self::render_job_card($job, 'available', $vendor_id); endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function render_my_jobs($vendor_id) {
        $jobs = get_posts([
            'post_type'      => 'sd_job',
            'posts_per_page' => -1,
            'meta_query'     => [
                ['key' => '_sd_assigned_vendor', 'value' => $vendor_id],
                ['key' => '_sd_stage', 'value' => ['claimed', 'scheduled'], 'compare' => 'IN'],
            ],
        ]);
        ?>
        <div class="sd-section">
            <h3 class="sd-section-title">My Active Jobs</h3>
            <?php if (empty($jobs)): ?>
                <div class="sd-empty-card">
                    <span class="dashicons dashicons-clipboard"></span>
                    <h4>No active jobs</h4>
                    <p>Claim a job from the Available Jobs tab to get started.</p>
                </div>
            <?php else: ?>
                <div class="sd-job-grid">
                    <?php foreach ($jobs as $job): echo self::render_job_card($job, 'active', $vendor_id); endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function render_completed_jobs($vendor_id) {
        $jobs = get_posts([
            'post_type'      => 'sd_job',
            'posts_per_page' => 20,
            'meta_query'     => [
                ['key' => '_sd_assigned_vendor', 'value' => $vendor_id],
                ['key' => '_sd_stage', 'value' => ['completed-review', 'ready-to-invoice', 'closed-paid'], 'compare' => 'IN'],
            ],
        ]);
        ?>
        <div class="sd-section">
            <h3 class="sd-section-title">Completed Jobs</h3>
            <?php if (empty($jobs)): ?>
                <div class="sd-empty-card">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <h4>No completed jobs yet</h4>
                    <p>Completed jobs and their payment status will appear here.</p>
                </div>
            <?php else: ?>
                <div class="sd-job-list">
                    <?php foreach ($jobs as $job):
                        $stage = get_post_meta($job->ID, '_sd_stage', true);
                        $pay   = get_post_meta($job->ID, '_sd_vendor_pay', true);
                        $stype = get_post_meta($job->ID, '_sd_service_type', true);
                        $city  = get_post_meta($job->ID, '_sd_city', true);
                        $color = SD_Post_Types::get_stage_color($stage);
                    ?>
                    <div class="sd-list-item">
                        <div class="sd-li-left">
                            <span class="sd-li-dot" style="background:<?php echo $color; ?>"></span>
                            <div>
                                <strong>#<?php echo $job->ID; ?> — <?php echo esc_html(SD_Post_Types::SERVICE_TYPES[$stype] ?? $stype); ?></strong>
                                <?php if ($city): ?><span class="sd-li-sub"><?php echo esc_html($city); ?></span><?php endif; ?>
                            </div>
                        </div>
                        <div class="sd-li-right">
                            <?php if ($pay): ?><span class="sd-li-pay">$<?php echo esc_html(number_format((float)$pay, 2)); ?></span><?php endif; ?>
                            <span class="sd-li-badge" style="background:<?php echo $color; ?>20;color:<?php echo $color; ?>"><?php echo SD_Post_Types::get_stage_label($stage); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function render_earnings($vendor_id) {
        $paid_jobs = get_posts([
            'post_type'      => 'sd_job',
            'posts_per_page' => -1,
            'meta_query'     => [
                ['key' => '_sd_assigned_vendor', 'value' => $vendor_id],
                ['key' => '_sd_stage', 'value' => 'closed-paid'],
            ],
        ]);

        $pending_jobs = get_posts([
            'post_type'      => 'sd_job',
            'posts_per_page' => -1,
            'meta_query'     => [
                ['key' => '_sd_assigned_vendor', 'value' => $vendor_id],
                ['key' => '_sd_stage', 'value' => ['completed-review', 'ready-to-invoice'], 'compare' => 'IN'],
            ],
        ]);

        $total_earned  = 0;
        $total_pending = 0;
        foreach ($paid_jobs as $j) {
            $total_earned += (float)(get_post_meta($j->ID, '_sd_vendor_pay', true) ?: 0);
        }
        foreach ($pending_jobs as $j) {
            $total_pending += (float)(get_post_meta($j->ID, '_sd_vendor_pay', true) ?: 0);
        }
        ?>
        <div class="sd-section">
            <h3 class="sd-section-title">Earnings</h3>
            <div class="sd-earnings-cards">
                <div class="sd-earn-card sd-earn-green">
                    <span class="sd-earn-label">Total Earned</span>
                    <span class="sd-earn-amount">$<?php echo number_format($total_earned, 2); ?></span>
                    <span class="sd-earn-sub"><?php echo count($paid_jobs); ?> paid jobs</span>
                </div>
                <div class="sd-earn-card sd-earn-yellow">
                    <span class="sd-earn-label">Pending Payout</span>
                    <span class="sd-earn-amount">$<?php echo number_format($total_pending, 2); ?></span>
                    <span class="sd-earn-sub"><?php echo count($pending_jobs); ?> jobs awaiting payment</span>
                </div>
                <div class="sd-earn-card sd-earn-blue">
                    <span class="sd-earn-label">Total Jobs</span>
                    <span class="sd-earn-amount"><?php echo count($paid_jobs) + count($pending_jobs); ?></span>
                    <span class="sd-earn-sub">All time</span>
                </div>
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
        <div class="sd-section">
            <h3 class="sd-section-title">My Profile</h3>
            <form method="post" class="sd-profile-form">
                <?php wp_nonce_field('sd_vendor_profile', '_sd_profile_nonce'); ?>
                <div class="sd-form-row">
                    <label>Full Name</label>
                    <input type="text" value="<?php echo esc_attr($user->display_name); ?>" disabled class="sd-input">
                </div>
                <div class="sd-form-row">
                    <label>Email</label>
                    <input type="email" value="<?php echo esc_attr($user->user_email); ?>" disabled class="sd-input">
                </div>
                <div class="sd-form-row">
                    <label>Phone Number</label>
                    <input type="text" name="sd_vendor_phone" value="<?php echo esc_attr($phone); ?>" class="sd-input" placeholder="+1234567890">
                </div>
                <div class="sd-form-row">
                    <label>Service Types I Handle</label>
                    <div class="sd-checkbox-grid">
                        <?php foreach (SD_Post_Types::SERVICE_TYPES as $key => $label): ?>
                            <label class="sd-checkbox-item">
                                <input type="checkbox" name="sd_vendor_services[]" value="<?php echo esc_attr($key); ?>" <?php echo in_array($key, $services) ? 'checked' : ''; ?>>
                                <span><?php echo esc_html($label); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="submit" name="sd_vendor_save_profile" class="sd-btn sd-btn-primary">Save Profile</button>
            </form>
        </div>
        <?php
    }

    private static function render_job_card($job, $type, $vendor_id) {
        $id      = $job->ID;
        $stype   = get_post_meta($id, '_sd_service_type', true);
        $city    = get_post_meta($id, '_sd_city', true);
        $state   = get_post_meta($id, '_sd_state', true);
        $urgency = get_post_meta($id, '_sd_urgency', true);
        $pay     = get_post_meta($id, '_sd_vendor_pay', true);
        $date    = get_post_meta($id, '_sd_preferred_date', true);
        $time    = get_post_meta($id, '_sd_time_window', true);
        $stage   = get_post_meta($id, '_sd_stage', true);
        $address = get_post_meta($id, '_sd_service_address', true);
        $onsite  = get_post_meta($id, '_sd_onsite_contact', true);
        $onsite_phone = get_post_meta($id, '_sd_onsite_phone', true);
        $access  = get_post_meta($id, '_sd_access_instructions', true);

        $service_label = SD_Post_Types::SERVICE_TYPES[$stype] ?? $stype;
        $urgency_label = SD_Post_Types::URGENCY_LEVELS[$urgency] ?? '';
        $stage_label   = SD_Post_Types::get_stage_label($stage);
        $stage_color   = SD_Post_Types::get_stage_color($stage);

        $time_labels = ['morning' => 'Morning (8am–12pm)', 'afternoon' => 'Afternoon (12pm–4pm)', 'evening' => 'Evening (4pm–8pm)'];
        $time_label  = $time_labels[$time] ?? '';

        ob_start();
        ?>
        <div class="sd-job-card <?php echo $urgency === 'urgent' ? 'sd-card-urgent' : ($urgency === 'priority' ? 'sd-card-priority' : ''); ?>">
            <div class="sd-jc-header">
                <span class="sd-jc-id">#<?php echo $id; ?></span>
                <?php if ($urgency === 'urgent'): ?>
                    <span class="sd-jc-urgency urgent">URGENT</span>
                <?php elseif ($urgency === 'priority'): ?>
                    <span class="sd-jc-urgency priority">PRIORITY</span>
                <?php endif; ?>
                <span class="sd-jc-badge" style="background:<?php echo $stage_color; ?>20;color:<?php echo $stage_color; ?>"><?php echo esc_html($stage_label); ?></span>
            </div>

            <h4 class="sd-jc-service"><?php echo esc_html($service_label); ?></h4>

            <div class="sd-jc-details">
                <?php if ($city || $state): ?>
                    <div class="sd-jc-detail"><span class="dashicons dashicons-location"></span> <?php echo esc_html(trim("$city, $state", ', ')); ?></div>
                <?php endif; ?>
                <?php if ($pay): ?>
                    <div class="sd-jc-detail sd-jc-pay"><span class="dashicons dashicons-money-alt"></span> $<?php echo esc_html(number_format((float)$pay, 2)); ?></div>
                <?php endif; ?>
                <?php if ($date): ?>
                    <div class="sd-jc-detail"><span class="dashicons dashicons-calendar-alt"></span> <?php echo esc_html(date('M j, Y', strtotime($date))); ?></div>
                <?php endif; ?>
                <?php if ($time_label): ?>
                    <div class="sd-jc-detail"><span class="dashicons dashicons-clock"></span> <?php echo esc_html($time_label); ?></div>
                <?php endif; ?>
            </div>

            <?php if ($type === 'active'): ?>
                <div class="sd-jc-full-details">
                    <?php if ($address): ?>
                        <div class="sd-jc-detail"><span class="dashicons dashicons-admin-home"></span> <?php echo esc_html($address); ?></div>
                    <?php endif; ?>
                    <?php if ($onsite): ?>
                        <div class="sd-jc-detail"><span class="dashicons dashicons-admin-users"></span> <?php echo esc_html($onsite); ?> <?php if ($onsite_phone): ?>(<?php echo esc_html($onsite_phone); ?>)<?php endif; ?></div>
                    <?php endif; ?>
                    <?php if ($access): ?>
                        <div class="sd-jc-detail"><span class="dashicons dashicons-lock"></span> <?php echo esc_html($access); ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="sd-jc-actions">
                <?php if ($type === 'available'): ?>
                    <button class="sd-btn sd-btn-primary sd-claim-btn" data-job-id="<?php echo $id; ?>">
                        <span class="dashicons dashicons-flag"></span> Claim This Job
                    </button>
                <?php elseif ($type === 'active' && $stage === 'claimed'): ?>
                    <button class="sd-btn sd-btn-primary sd-confirm-btn" data-job-id="<?php echo $id; ?>">
                        <span class="dashicons dashicons-yes"></span> Confirm (YES)
                    </button>
                <?php elseif ($type === 'active' && $stage === 'scheduled'): ?>
                    <button class="sd-btn sd-btn-success sd-complete-btn" data-job-id="<?php echo $id; ?>">
                        <span class="dashicons dashicons-yes-alt"></span> Mark Complete (DONE)
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function count_available_jobs($vendor_id) {
        return count(get_posts([
            'post_type'      => 'sd_job',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => [
                ['key' => '_sd_stage', 'value' => 'posted-to-vendors'],
                [
                    'relation' => 'OR',
                    ['key' => '_sd_assigned_vendor', 'compare' => 'NOT EXISTS'],
                    ['key' => '_sd_assigned_vendor', 'value' => ''],
                ],
            ],
        ]));
    }

    private static function count_my_jobs($vendor_id, $stages = []) {
        $meta_query = [['key' => '_sd_assigned_vendor', 'value' => $vendor_id]];
        if (!empty($stages)) {
            $meta_query[] = ['key' => '_sd_stage', 'value' => $stages, 'compare' => 'IN'];
        }
        return count(get_posts([
            'post_type'      => 'sd_job',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => $meta_query,
        ]));
    }

    private static function render_login_prompt($role) {
        ob_start();
        ?>
        <div class="sd-login-prompt">
            <div class="sd-login-card">
                <span class="dashicons dashicons-lock"></span>
                <h2>Please Log In</h2>
                <p>You need to be logged in as a <?php echo $role; ?> to access this dashboard.</p>
                <a href="<?php echo wp_login_url(get_permalink()); ?>" class="sd-btn sd-btn-primary">Log In</a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
