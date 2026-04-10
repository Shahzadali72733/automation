<?php
if (!defined('ABSPATH')) exit;

class SD_Vendor_Dashboard {

    public static function init() {
        add_shortcode('sd_vendor_dashboard', [__CLASS__, 'render_dashboard']);
    }

    /**
     * WooCommerce "vendor-portal" endpoint: full layout on standalone pages,
     * main column only when already inside My Account (sidebar from theme template).
     */
    public static function render_vendor_portal_endpoint_output() {
        if (self::is_wc_vendor_portal_endpoint()) {
            return self::render_main_only();
        }
        return self::render_dashboard();
    }

    private static function is_wc_vendor_portal_endpoint() {
        if (!function_exists('is_account_page') || !is_account_page()) {
            return false;
        }
        if (!function_exists('WC') || !WC()->query) {
            return false;
        }
        return WC()->query->get_current_endpoint() === 'vendor-portal';
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
        $tab = self::get_requested_tab();

        ob_start();
        ?>
        <div class="sd-saas-wrap sd-vendor-dash">
            <?php self::render_sidebar($vendor_id, $tab, 'standalone'); ?>
            <div class="sd-saas-main">
                <?php self::render_main_inner($vendor_id, $tab); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Sidebar + nav for WooCommerce My Account (vendors only). Used by theme template override.
     */
    public static function render_account_sidebar_wc() {
        $user = wp_get_current_user();
        if (!in_array('sd_vendor', $user->roles, true)) {
            return;
        }
        $vendor_id = $user->ID;
        $tab = self::get_requested_tab();
        self::render_sidebar($vendor_id, $tab, 'woocommerce');
    }

    /**
     * @param string $mode 'standalone' (relative ?tab=) or 'woocommerce' (account endpoint URLs).
     */
    private static function render_sidebar($vendor_id, $tab, $mode) {
        $user = get_userdata($vendor_id);
        $tabs = self::get_tabs_config($vendor_id);
        $wc_ep = (function_exists('WC') && WC()->query) ? WC()->query->get_current_endpoint() : '';

        if ($mode === 'woocommerce' && function_exists('wc_get_account_endpoint_url')) {
            $portal_base = wc_get_account_endpoint_url('vendor-portal');
        } else {
            $portal_base = '';
        }

        ?>
        <div class="sd-saas-sidebar">
            <div class="sd-sidebar-header">
                <div class="sd-sidebar-avatar"><?php echo get_avatar($vendor_id, 44); ?></div>
                <div class="sd-sidebar-info">
                    <strong><?php echo esc_html($user->display_name); ?></strong>
                    <span><?php esc_html_e('Vendor', 'service-dispatch'); ?></span>
                </div>
            </div>
            <nav class="sd-sidebar-nav">
                <?php foreach ($tabs as $key => $t) :
                    if ($mode === 'woocommerce' && $portal_base) {
                        $href = esc_url(add_query_arg('tab', rawurlencode($key), $portal_base));
                    } else {
                        $href = esc_url(add_query_arg('tab', rawurlencode($key)));
                    }
                    $portal_here = ($mode === 'standalone') || ($wc_ep === 'vendor-portal');
                    $active = $portal_here && ($tab === $key);
                    ?>
                    <a href="<?php echo $href; ?>" class="sd-nav-item <?php echo $active ? 'active' : ''; ?>">
                        <span class="dashicons <?php echo esc_attr($t['icon']); ?>"></span>
                        <span class="sd-nav-label"><?php echo esc_html($t['label']); ?></span>
                        <?php if (isset($t['count']) && $t['count'] > 0) : ?>
                            <span class="sd-nav-badge"><?php echo (int) $t['count']; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>

                <?php if ($mode === 'woocommerce' && function_exists('wc_get_account_endpoint_url')) : ?>
                    <div class="sd-sidebar-nav-divider" role="presentation"></div>
                    <span class="sd-sidebar-nav-heading"><?php esc_html_e('Account', 'service-dispatch'); ?></span>
                    <?php
                    $addr_url = wc_get_account_endpoint_url('edit-address');
                    $acct_url = wc_get_account_endpoint_url('edit-account');
                    ?>
                    <a href="<?php echo esc_url($addr_url); ?>" class="sd-nav-item <?php echo $wc_ep === 'edit-address' ? 'active' : ''; ?>">
                        <span class="dashicons dashicons-location"></span>
                        <span class="sd-nav-label"><?php esc_html_e('Addresses', 'woocommerce'); ?></span>
                    </a>
                    <?php
                    $wc_menu_items = function_exists('wc_get_account_menu_items') ? wc_get_account_menu_items() : [];
                    if (!empty($wc_menu_items['payment-methods'])) :
                        ?>
                        <a href="<?php echo esc_url(wc_get_account_endpoint_url('payment-methods')); ?>" class="sd-nav-item <?php echo $wc_ep === 'payment-methods' ? 'active' : ''; ?>">
                            <span class="dashicons dashicons-money"></span>
                            <span class="sd-nav-label"><?php echo esc_html($wc_menu_items['payment-methods']); ?></span>
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo esc_url($acct_url); ?>" class="sd-nav-item <?php echo $wc_ep === 'edit-account' ? 'active' : ''; ?>">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <span class="sd-nav-label"><?php esc_html_e('Account details', 'woocommerce'); ?></span>
                    </a>
                <?php endif; ?>
            </nav>
            <div class="sd-sidebar-footer">
                <a href="<?php echo esc_url(wc_logout_url(wc_get_page_permalink('myaccount'))); ?>" class="sd-nav-item sd-nav-logout">
                    <span class="dashicons dashicons-exit"></span>
                    <span class="sd-nav-label"><?php esc_html_e('Log out', 'woocommerce'); ?></span>
                </a>
            </div>
        </div>
        <?php
    }

    private static function get_requested_tab() {
        $allowed = ['dashboard', 'available', 'my-jobs', 'completed', 'earnings', 'profile'];
        if (!isset($_GET['tab'])) {
            return 'dashboard';
        }
        $t = sanitize_key(wp_unslash($_GET['tab']));
        return in_array($t, $allowed, true) ? $t : 'dashboard';
    }

    private static function get_tabs_config($vendor_id) {
        $available = self::count_available_jobs();
        $my_active = self::count_my_jobs($vendor_id, ['claimed', 'scheduled', 'posted-to-vendors']);
        $completed = self::count_my_jobs($vendor_id, ['completed-review', 'ready-to-invoice', 'closed-paid']);

        return [
            'dashboard' => ['icon' => 'dashicons-dashboard', 'label' => __('Dashboard', 'service-dispatch')],
            'available' => ['icon' => 'dashicons-megaphone', 'label' => __('Available Jobs', 'service-dispatch'), 'count' => $available],
            'my-jobs'   => ['icon' => 'dashicons-clipboard', 'label' => __('My Active Jobs', 'service-dispatch'), 'count' => $my_active],
            'completed' => ['icon' => 'dashicons-yes-alt', 'label' => __('Completed', 'service-dispatch'), 'count' => $completed],
            'earnings'  => ['icon' => 'dashicons-money-alt', 'label' => __('Earnings', 'service-dispatch')],
            'profile'   => ['icon' => 'dashicons-admin-users', 'label' => __('Profile', 'service-dispatch')],
        ];
    }

    public static function render_main_only() {
        if (!is_user_logged_in()) {
            return '';
        }
        $user = wp_get_current_user();
        if (!in_array('sd_vendor', $user->roles) && !in_array('administrator', $user->roles)) {
            return '<p>' . esc_html__('Access denied.', 'service-dispatch') . '</p>';
        }
        $vendor_id = $user->ID;
        $tab = self::get_requested_tab();
        ob_start();
        self::render_main_inner($vendor_id, $tab);
        return ob_get_clean();
    }

    private static function render_main_inner($vendor_id, $tab) {
        $tabs = self::get_tabs_config($vendor_id);
        $title = $tabs[$tab]['label'] ?? __('Dashboard', 'service-dispatch');
        ?>
        <div class="sd-main-header">
            <h1><?php echo esc_html($title); ?></h1>
        </div>

        <?php if ($tab === 'dashboard') : ?>
            <?php
            $available = self::count_available_jobs();
            $my_active = self::count_my_jobs($vendor_id, ['claimed', 'scheduled', 'posted-to-vendors']);
            $completed = self::count_my_jobs($vendor_id, ['completed-review', 'ready-to-invoice', 'closed-paid']);
            ?>
            <div class="sd-stats-row">
                <div class="sd-stat-card sd-stat-blue">
                    <span class="sd-stat-icon dashicons dashicons-megaphone"></span>
                    <div><span class="sd-stat-num"><?php echo (int) $available; ?></span><span class="sd-stat-label"><?php esc_html_e('Available Jobs', 'service-dispatch'); ?></span></div>
                </div>
                <div class="sd-stat-card sd-stat-orange">
                    <span class="sd-stat-icon dashicons dashicons-clipboard"></span>
                    <div><span class="sd-stat-num"><?php echo (int) $my_active; ?></span><span class="sd-stat-label"><?php esc_html_e('My Active Jobs', 'service-dispatch'); ?></span></div>
                </div>
                <div class="sd-stat-card sd-stat-green">
                    <span class="sd-stat-icon dashicons dashicons-yes-alt"></span>
                    <div><span class="sd-stat-num"><?php echo (int) $completed; ?></span><span class="sd-stat-label"><?php esc_html_e('Completed', 'service-dispatch'); ?></span></div>
                </div>
            </div>
            <?php self::render_recent_activity($vendor_id); ?>
        <?php elseif ($tab === 'available') : ?>
            <?php self::render_available_jobs(); ?>
        <?php elseif ($tab === 'my-jobs') : ?>
            <?php self::render_my_jobs($vendor_id); ?>
        <?php elseif ($tab === 'completed') : ?>
            <?php self::render_completed_jobs($vendor_id); ?>
        <?php elseif ($tab === 'earnings') : ?>
            <?php self::render_earnings($vendor_id); ?>
        <?php elseif ($tab === 'profile') : ?>
            <?php self::render_profile($vendor_id); ?>
        <?php endif; ?>
        <?php
    }

    private static function render_recent_activity($vendor_id) {
        $vid = absint($vendor_id);
        $recent = get_posts([
            'post_type'      => 'sd_job',
            'posts_per_page' => 8,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => [
                'relation' => 'OR',
                [
                    'key'     => '_sd_assigned_vendor',
                    'value'   => $vid,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ],
                [
                    'relation' => 'AND',
                    [
                        'key'   => '_sd_stage',
                        'value' => 'posted-to-vendors',
                    ],
                    [
                        'relation' => 'OR',
                        ['key' => '_sd_assigned_vendor', 'compare' => 'NOT EXISTS'],
                        ['key' => '_sd_assigned_vendor', 'value' => ''],
                        ['key' => '_sd_assigned_vendor', 'value' => '0'],
                    ],
                ],
                [
                    'relation' => 'AND',
                    [
                        'key'   => '_sd_stage',
                        'value' => 'approved-priced',
                    ],
                    [
                        'relation' => 'OR',
                        ['key' => '_sd_assigned_vendor', 'compare' => 'NOT EXISTS'],
                        ['key' => '_sd_assigned_vendor', 'value' => ''],
                        ['key' => '_sd_assigned_vendor', 'value' => '0'],
                    ],
                ],
            ],
        ]);
        ?>
        <div class="sd-content-card">
            <h3><?php esc_html_e('Recent Activity', 'service-dispatch'); ?></h3>
            <?php if (empty($recent)) : ?>
                <p class="sd-muted"><?php esc_html_e('No activity yet. Check Available Jobs to get started.', 'service-dispatch'); ?></p>
            <?php else : ?>
                <div class="sd-list">
                    <?php foreach ($recent as $job) :
                        $stage = get_post_meta($job->ID, '_sd_stage', true) ?: 'new-request';
                        $stype = get_post_meta($job->ID, '_sd_service_type', true);
                        $color = SD_Post_Types::get_stage_color($stage);
                        $assigned = (int) get_post_meta($job->ID, '_sd_assigned_vendor', true);
                        $open_claim = ($stage === 'posted-to-vendors' && $assigned === 0);
                        $stale_mine = ($stage === 'posted-to-vendors' && $assigned === $vid);
                        ?>
                    <div class="sd-list-row">
                        <span class="sd-list-dot" style="background:<?php echo esc_attr($color); ?>"></span>
                        <div class="sd-list-info">
                            <strong>#<?php echo (int) $job->ID; ?> — <?php echo esc_html(SD_Post_Types::SERVICE_TYPES[$stype] ?? $stype); ?></strong>
                            <span><?php echo esc_html(get_the_date('M j, Y', $job)); ?></span>
                        </div>
                        <div class="sd-list-row-actions">
                            <?php if ($open_claim) : ?>
                                <button type="button" class="sd-btn sd-btn-primary sd-claim-btn sd-list-mini-btn" data-job-id="<?php echo (int) $job->ID; ?>"><?php esc_html_e('Claim', 'service-dispatch'); ?></button>
                                <button type="button" class="sd-btn sd-btn-success sd-claim-yes-btn sd-list-mini-btn" data-job-id="<?php echo (int) $job->ID; ?>"><?php esc_html_e('Claim YES', 'service-dispatch'); ?></button>
                            <?php elseif ($stale_mine) : ?>
                                <button type="button" class="sd-btn sd-btn-primary sd-confirm-btn sd-list-mini-btn" data-job-id="<?php echo (int) $job->ID; ?>"><?php esc_html_e('Confirm YES', 'service-dispatch'); ?></button>
                                <button type="button" class="sd-btn sd-btn-success sd-claim-yes-btn sd-list-mini-btn" data-job-id="<?php echo (int) $job->ID; ?>"><?php esc_html_e('Claim YES', 'service-dispatch'); ?></button>
                            <?php endif; ?>
                            <button type="button" class="sd-btn sd-btn-secondary sd-job-details-btn sd-list-details-btn" data-job-id="<?php echo (int) $job->ID; ?>"><?php esc_html_e('Details', 'service-dispatch'); ?></button>
                            <span class="sd-badge" style="background:<?php echo esc_attr($color); ?>20;color:<?php echo esc_attr($color); ?>"><?php echo esc_html(SD_Post_Types::get_stage_label($stage)); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Jobs vendors should see on “Available Jobs”: posted (open or yours to finish claiming) and unassigned approved requests.
     */
    private static function get_available_jobs_for_vendor() {
        $me = get_current_user_id();
        $jobs = get_posts([
            'post_type'      => 'sd_job',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => [
                [
                    'key'     => '_sd_stage',
                    'value'   => ['posted-to-vendors', 'approved-priced'],
                    'compare' => 'IN',
                ],
            ],
        ]);

        return array_values(array_filter($jobs, function ($job) use ($me) {
            $jid      = $job->ID;
            $assigned = (int) get_post_meta($jid, '_sd_assigned_vendor', true);
            $stage    = get_post_meta($jid, '_sd_stage', true);
            if ($stage === 'approved-priced') {
                return $assigned === 0;
            }
            if ($stage === 'posted-to-vendors') {
                if ($assigned === 0) {
                    return true;
                }
                return $assigned === (int) $me;
            }
            return false;
        }));
    }

    private static function render_available_jobs() {
        $jobs = self::get_available_jobs_for_vendor();
        if (empty($jobs)) {
            echo '<div class="sd-empty-state"><span class="dashicons dashicons-megaphone"></span><h3>' . esc_html__('No available jobs right now', 'service-dispatch') . '</h3><p>' . esc_html__('New job opportunities will appear here when posted.', 'service-dispatch') . '</p></div>';
            return;
        }
        echo '<div class="sd-job-grid">';
        foreach ($jobs as $job) {
            echo self::render_job_card($job, 'available');
        }
        echo '</div>';
    }

    private static function render_my_jobs($vendor_id) {
        $vid = absint($vendor_id);
        $jobs = get_posts([
            'post_type'      => 'sd_job',
            'posts_per_page' => -1,
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'     => '_sd_assigned_vendor',
                    'value'   => $vid,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ],
                [
                    'key'     => '_sd_stage',
                    'value'   => ['claimed', 'scheduled', 'posted-to-vendors'],
                    'compare' => 'IN',
                ],
            ],
        ]);
        if (empty($jobs)) {
            echo '<div class="sd-empty-state"><span class="dashicons dashicons-clipboard"></span><h3>' . esc_html__('No active jobs', 'service-dispatch') . '</h3><p>' . esc_html__('Claim a job from Available Jobs to get started.', 'service-dispatch') . '</p></div>';
            return;
        }
        echo '<div class="sd-job-grid">';
        foreach ($jobs as $job) {
            echo self::render_job_card($job, 'active');
        }
        echo '</div>';
    }

    private static function render_completed_jobs($vendor_id) {
        $jobs = get_posts([
            'post_type' => 'sd_job', 'posts_per_page' => 30,
            'meta_query' => [['key' => '_sd_assigned_vendor', 'value' => $vendor_id], ['key' => '_sd_stage', 'value' => ['completed-review', 'ready-to-invoice', 'closed-paid'], 'compare' => 'IN']],
        ]);
        if (empty($jobs)) {
            echo '<div class="sd-empty-state"><span class="dashicons dashicons-yes-alt"></span><h3>' . esc_html__('No completed jobs yet', 'service-dispatch') . '</h3><p>' . esc_html__('Completed jobs and payment status will appear here.', 'service-dispatch') . '</p></div>';
            return;
        }
        echo '<div class="sd-content-card"><table class="sd-table"><thead><tr><th>' . esc_html__('Job #', 'service-dispatch') . '</th><th>' . esc_html__('Service', 'service-dispatch') . '</th><th>' . esc_html__('City', 'service-dispatch') . '</th><th>' . esc_html__('Pay', 'service-dispatch') . '</th><th>' . esc_html__('Status', 'service-dispatch') . '</th></tr></thead><tbody>';
        foreach ($jobs as $job) {
            $stage = get_post_meta($job->ID, '_sd_stage', true);
            $stype = get_post_meta($job->ID, '_sd_service_type', true);
            $city  = get_post_meta($job->ID, '_sd_city', true);
            $pay   = get_post_meta($job->ID, '_sd_vendor_pay', true);
            $color = SD_Post_Types::get_stage_color($stage);
            echo '<tr>';
            echo '<td><strong>#' . (int) $job->ID . '</strong></td>';
            echo '<td>' . esc_html(SD_Post_Types::SERVICE_TYPES[$stype] ?? $stype) . '</td>';
            echo '<td>' . esc_html($city ?: '—') . '</td>';
            echo '<td>' . ($pay ? '$' . number_format((float) $pay, 2) : '—') . '</td>';
            echo '<td><span class="sd-badge" style="background:' . esc_attr($color) . '20;color:' . esc_attr($color) . '">' . esc_html(SD_Post_Types::get_stage_label($stage)) . '</span></td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }

    private static function render_earnings($vendor_id) {
        $paid = get_posts(['post_type' => 'sd_job', 'posts_per_page' => -1, 'meta_query' => [['key' => '_sd_assigned_vendor', 'value' => $vendor_id], ['key' => '_sd_stage', 'value' => 'closed-paid']]]);
        $pending = get_posts(['post_type' => 'sd_job', 'posts_per_page' => -1, 'meta_query' => [['key' => '_sd_assigned_vendor', 'value' => $vendor_id], ['key' => '_sd_stage', 'value' => ['completed-review', 'ready-to-invoice'], 'compare' => 'IN']]]);
        $total_earned = 0;
        $total_pending = 0;
        foreach ($paid as $j) {
            $total_earned += (float) (get_post_meta($j->ID, '_sd_vendor_pay', true) ?: 0);
        }
        foreach ($pending as $j) {
            $total_pending += (float) (get_post_meta($j->ID, '_sd_vendor_pay', true) ?: 0);
        }
        ?>
        <div class="sd-stats-row">
            <div class="sd-stat-card sd-stat-green">
                <span class="sd-stat-icon dashicons dashicons-money-alt"></span>
                <div><span class="sd-stat-num">$<?php echo esc_html(number_format($total_earned, 2)); ?></span><span class="sd-stat-label"><?php echo esc_html(sprintf(__('Total Earned (%d jobs)', 'service-dispatch'), count($paid))); ?></span></div>
            </div>
            <div class="sd-stat-card sd-stat-orange">
                <span class="sd-stat-icon dashicons dashicons-clock"></span>
                <div><span class="sd-stat-num">$<?php echo esc_html(number_format($total_pending, 2)); ?></span><span class="sd-stat-label"><?php echo esc_html(sprintf(__('Pending (%d jobs)', 'service-dispatch'), count($pending))); ?></span></div>
            </div>
            <div class="sd-stat-card sd-stat-blue">
                <span class="sd-stat-icon dashicons dashicons-chart-area"></span>
                <div><span class="sd-stat-num"><?php echo (int) (count($paid) + count($pending)); ?></span><span class="sd-stat-label"><?php esc_html_e('Total Jobs', 'service-dispatch'); ?></span></div>
            </div>
        </div>
        <?php
    }

    private static function render_profile($vendor_id) {
        $user = get_userdata($vendor_id);
        $phone = get_user_meta($vendor_id, 'sd_vendor_phone', true);
        $services = get_user_meta($vendor_id, 'sd_vendor_services', true);
        if (!is_array($services)) {
            $services = [];
        }
        if (isset($_POST['sd_vendor_save_profile']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_sd_profile_nonce'] ?? '')), 'sd_vendor_profile')) {
            $phone = sanitize_text_field(wp_unslash($_POST['sd_vendor_phone'] ?? ''));
            update_user_meta($vendor_id, 'sd_vendor_phone', $phone);
            $services = array_map('sanitize_text_field', wp_unslash($_POST['sd_vendor_services'] ?? []));
            update_user_meta($vendor_id, 'sd_vendor_services', $services);
            echo '<div class="sd-notice sd-notice-success">' . esc_html__('Profile updated!', 'service-dispatch') . '</div>';
        }
        ?>
        <div class="sd-content-card" style="max-width:640px;">
            <h3><?php esc_html_e('My Profile', 'service-dispatch'); ?></h3>
            <form method="post" class="sd-form">
                <?php wp_nonce_field('sd_vendor_profile', '_sd_profile_nonce'); ?>
                <div class="sd-form-group"><label><?php esc_html_e('Full Name', 'service-dispatch'); ?></label><input type="text" value="<?php echo esc_attr($user->display_name); ?>" disabled class="sd-input"></div>
                <div class="sd-form-group"><label><?php esc_html_e('Email', 'service-dispatch'); ?></label><input type="email" value="<?php echo esc_attr($user->user_email); ?>" disabled class="sd-input"></div>
                <div class="sd-form-group"><label><?php esc_html_e('Phone Number', 'service-dispatch'); ?></label><input type="text" name="sd_vendor_phone" value="<?php echo esc_attr($phone); ?>" class="sd-input" placeholder="+1234567890"></div>
                <div class="sd-form-group">
                    <label><?php esc_html_e('Service Types I Handle', 'service-dispatch'); ?></label>
                    <div class="sd-checkbox-grid">
                        <?php foreach (SD_Post_Types::SERVICE_TYPES as $key => $label) : ?>
                            <label class="sd-checkbox-item"><input type="checkbox" name="sd_vendor_services[]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, $services, true)); ?>><span><?php echo esc_html($label); ?></span></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="submit" name="sd_vendor_save_profile" class="sd-btn sd-btn-primary"><?php esc_html_e('Save Profile', 'service-dispatch'); ?></button>
            </form>
        </div>
        <?php
    }

    private static function render_job_card($job, $type) {
        $id = $job->ID;
        $me = get_current_user_id();
        $stype = get_post_meta($id, '_sd_service_type', true);
        $city = get_post_meta($id, '_sd_city', true);
        $state = get_post_meta($id, '_sd_state', true);
        $urgency = get_post_meta($id, '_sd_urgency', true);
        $pay = get_post_meta($id, '_sd_vendor_pay', true);
        $date = get_post_meta($id, '_sd_preferred_date', true);
        $time = get_post_meta($id, '_sd_time_window', true);
        $stage = get_post_meta($id, '_sd_stage', true);
        $assigned_vendor = (int) get_post_meta($id, '_sd_assigned_vendor', true);
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
                <span class="sd-jc-id">#<?php echo (int) $id; ?></span>
                <?php if ($urgency === 'urgent') : ?><span class="sd-urgency-tag urgent">URGENT</span><?php elseif ($urgency === 'priority') : ?><span class="sd-urgency-tag priority">PRIORITY</span><?php endif; ?>
                <span class="sd-badge" style="background:<?php echo esc_attr($stage_color); ?>20;color:<?php echo esc_attr($stage_color); ?>"><?php echo esc_html($stage_label); ?></span>
            </div>
            <h4 class="sd-jc-title"><?php echo esc_html($service_label); ?></h4>

            <?php if ($desc) : ?><p class="sd-jc-desc"><?php echo esc_html(wp_trim_words($desc, 30)); ?></p><?php endif; ?>

            <div class="sd-jc-meta">
                <?php if ($city || $state) : ?><div><span class="dashicons dashicons-location"></span> <?php echo esc_html(trim("$city, $state", ', ')); ?></div><?php endif; ?>
                <?php if ($pay) : ?><div class="sd-jc-pay"><span class="dashicons dashicons-money-alt"></span> $<?php echo esc_html(number_format((float) $pay, 2)); ?></div><?php endif; ?>
                <?php if ($date) : ?><div><span class="dashicons dashicons-calendar-alt"></span> <?php echo esc_html(date('M j, Y', strtotime($date))); ?></div><?php endif; ?>
                <?php if (isset($time_labels[$time])) : ?><div><span class="dashicons dashicons-clock"></span> <?php echo esc_html($time_labels[$time]); ?></div><?php endif; ?>
            </div>

            <?php if ($type === 'active' || ($type === 'available' && $assigned_vendor === $me && $stage === 'posted-to-vendors')) : ?>
                <div class="sd-jc-details-box">
                    <?php if ($address) : ?><div><span class="dashicons dashicons-admin-home"></span> <?php echo esc_html($address); ?></div><?php endif; ?>
                    <?php if ($onsite) : ?><div><span class="dashicons dashicons-admin-users"></span> <?php echo esc_html($onsite); ?> <?php if ($onsite_phone) : ?>(<?php echo esc_html($onsite_phone); ?>)<?php endif; ?></div><?php endif; ?>
                    <?php if ($access) : ?><div><span class="dashicons dashicons-lock"></span> <?php echo esc_html($access); ?></div><?php endif; ?>
                </div>
                <?php if (is_array($photos) && !empty($photos)) : ?>
                    <div class="sd-jc-photos"><?php foreach ($photos as $url) : ?><a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer"><img src="<?php echo esc_url($url); ?>" alt=""></a><?php endforeach; ?></div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($type === 'available' && $stage === 'approved-priced' && $assigned_vendor === 0) : ?>
                <p class="sd-muted sd-jc-pending-dispatch"><?php esc_html_e('Awaiting admin dispatch — you can review details; claiming opens after the job is posted to vendors.', 'service-dispatch'); ?></p>
            <?php elseif ($type === 'available' && $stage === 'posted-to-vendors' && $assigned_vendor > 0 && $assigned_vendor !== $me) : ?>
                <p class="sd-job-taken-notice"><?php esc_html_e('Another provider is already on this job.', 'service-dispatch'); ?></p>
            <?php endif; ?>

            <div class="sd-jc-actions">
                <?php if ($type === 'available' && $stage === 'posted-to-vendors' && $assigned_vendor === 0) : ?>
                    <div class="sd-jc-claim-row">
                        <button type="button" class="sd-btn sd-btn-primary sd-claim-btn" data-job-id="<?php echo (int) $id; ?>"><span class="dashicons dashicons-flag"></span> <?php esc_html_e('Claim', 'service-dispatch'); ?></button>
                        <button type="button" class="sd-btn sd-btn-success sd-claim-yes-btn" data-job-id="<?php echo (int) $id; ?>"><span class="dashicons dashicons-yes"></span> <?php esc_html_e('Claim & confirm YES', 'service-dispatch'); ?></button>
                    </div>
                <?php elseif (($type === 'available' || $type === 'active') && $stage === 'posted-to-vendors' && $assigned_vendor === $me) : ?>
                    <div class="sd-jc-claim-row">
                        <button type="button" class="sd-btn sd-btn-primary sd-confirm-btn" data-job-id="<?php echo (int) $id; ?>"><span class="dashicons dashicons-yes"></span> <?php esc_html_e('Confirm (YES)', 'service-dispatch'); ?></button>
                        <button type="button" class="sd-btn sd-btn-success sd-claim-yes-btn" data-job-id="<?php echo (int) $id; ?>"><span class="dashicons dashicons-yes"></span> <?php esc_html_e('Claim & confirm YES', 'service-dispatch'); ?></button>
                    </div>
                <?php elseif ($type === 'active' && $stage === 'claimed') : ?>
                    <button type="button" class="sd-btn sd-btn-primary sd-confirm-btn" data-job-id="<?php echo (int) $id; ?>"><span class="dashicons dashicons-yes"></span> <?php esc_html_e('Confirm (YES)', 'service-dispatch'); ?></button>
                <?php elseif ($type === 'active' && $stage === 'scheduled') : ?>
                    <button type="button" class="sd-btn sd-btn-success sd-complete-btn" data-job-id="<?php echo (int) $id; ?>"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('Mark Complete', 'service-dispatch'); ?></button>
                <?php endif; ?>
                <button type="button" class="sd-btn sd-btn-secondary sd-job-details-btn" data-job-id="<?php echo (int) $id; ?>">
                    <span class="dashicons dashicons-visibility"></span> <?php esc_html_e('Full details', 'service-dispatch'); ?>
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function count_available_jobs() {
        return count(self::get_available_jobs_for_vendor());
    }

    private static function count_my_jobs($vendor_id, $stages = []) {
        $vid = absint($vendor_id);
        $mq = [
            [
                'key'     => '_sd_assigned_vendor',
                'value'   => $vid,
                'compare' => '=',
                'type'    => 'NUMERIC',
            ],
        ];
        if (!empty($stages)) {
            $mq[] = ['key' => '_sd_stage', 'value' => $stages, 'compare' => 'IN'];
        }
        return count(get_posts(['post_type' => 'sd_job', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_query' => $mq]));
    }
}
