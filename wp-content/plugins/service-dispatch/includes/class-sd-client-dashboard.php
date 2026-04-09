<?php
if (!defined('ABSPATH')) exit;

class SD_Client_Dashboard {

    public static function init() {
        add_shortcode('sd_client_dashboard', [__CLASS__, 'render_dashboard']);
    }

    public static function render_dashboard() {
        if (!is_user_logged_in()) {
            return self::render_login_prompt();
        }

        $user = wp_get_current_user();
        if (!in_array('sd_client', $user->roles) && !in_array('administrator', $user->roles)) {
            return '<div class="sd-access-denied"><h2>Access Denied</h2><p>This dashboard is for clients only.</p></div>';
        }

        $client_id = $user->ID;
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';

        ob_start();
        ?>
        <div class="sd-client-dash">
            <div class="sd-dash-header">
                <div class="sd-dash-welcome">
                    <div class="sd-dash-avatar"><?php echo get_avatar($client_id, 56); ?></div>
                    <div>
                        <h2>Welcome, <?php echo esc_html($user->display_name); ?></h2>
                        <span class="sd-dash-role">Client Dashboard</span>
                    </div>
                </div>
                <div class="sd-dash-quick-stats">
                    <?php
                    $active   = self::count_client_jobs($client_id, ['new-request', 'approved-priced', 'posted-to-vendors', 'claimed', 'scheduled']);
                    $pending  = self::count_client_jobs($client_id, ['completed-review', 'ready-to-invoice']);
                    $closed   = self::count_client_jobs($client_id, ['closed-paid']);
                    ?>
                    <div class="sd-qs-card sd-qs-blue">
                        <span class="sd-qs-num"><?php echo $active; ?></span>
                        <span class="sd-qs-label">Active Requests</span>
                    </div>
                    <div class="sd-qs-card sd-qs-orange">
                        <span class="sd-qs-num"><?php echo $pending; ?></span>
                        <span class="sd-qs-label">Pending Invoice</span>
                    </div>
                    <div class="sd-qs-card sd-qs-green">
                        <span class="sd-qs-num"><?php echo $closed; ?></span>
                        <span class="sd-qs-label">Completed</span>
                    </div>
                </div>
            </div>

            <div class="sd-dash-tabs">
                <a href="?tab=overview" class="sd-tab <?php echo $tab === 'overview' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-dashboard"></span> Overview
                </a>
                <a href="?tab=active" class="sd-tab <?php echo $tab === 'active' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-clipboard"></span> Active Requests
                </a>
                <a href="?tab=invoices" class="sd-tab <?php echo $tab === 'invoices' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-money-alt"></span> Invoices
                </a>
                <a href="?tab=history" class="sd-tab <?php echo $tab === 'history' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-backup"></span> History
                </a>
                <a href="?tab=profile" class="sd-tab <?php echo $tab === 'profile' ? 'active' : ''; ?>">
                    <span class="dashicons dashicons-admin-users"></span> Profile
                </a>
            </div>

            <div class="sd-dash-content">
                <?php
                switch ($tab) {
                    case 'active':   self::render_active_requests($client_id, $user); break;
                    case 'invoices': self::render_invoices($client_id, $user); break;
                    case 'history':  self::render_history($client_id, $user); break;
                    case 'profile':  self::render_profile($client_id); break;
                    default:         self::render_overview($client_id, $user); break;
                }
                ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function render_overview($client_id, $user) {
        $recent_jobs = self::get_client_jobs($client_id, $user, [], 5);
        ?>
        <div class="sd-section">
            <div class="sd-overview-grid">
                <div class="sd-overview-card sd-overview-main">
                    <h3>Service Request Tracker</h3>
                    <div class="sd-tracker">
                        <?php
                        $stages_display = [
                            'new-request'       => ['Submitted', 'Your request has been received'],
                            'approved-priced'   => ['Approved', 'Pricing confirmed, finding provider'],
                            'posted-to-vendors' => ['Dispatching', 'Looking for available providers'],
                            'claimed'           => ['Provider Found', 'A provider has claimed your job'],
                            'scheduled'         => ['Scheduled', 'Provider confirmed — service is scheduled'],
                            'completed-review'  => ['Completed', 'Service completed — under review'],
                            'ready-to-invoice'  => ['Invoice Sent', 'Invoice ready for payment'],
                            'closed-paid'       => ['Closed', 'Payment received — job complete'],
                        ];

                        $latest_job = !empty($recent_jobs) ? $recent_jobs[0] : null;
                        $current_stage = $latest_job ? (get_post_meta($latest_job->ID, '_sd_stage', true) ?: 'new-request') : '';

                        $found_current = false;
                        foreach ($stages_display as $skey => $sinfo):
                            $is_current = ($skey === $current_stage);
                            $is_past = !$found_current && !$is_current && $latest_job;
                            if ($is_current) $found_current = true;
                            $class = $is_current ? 'current' : ($is_past ? 'completed' : 'pending');
                        ?>
                        <div class="sd-tracker-step <?php echo $class; ?>">
                            <div class="sd-tracker-dot"></div>
                            <div class="sd-tracker-info">
                                <strong><?php echo esc_html($sinfo[0]); ?></strong>
                                <span><?php echo esc_html($sinfo[1]); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="sd-overview-card">
                    <h3>Recent Requests</h3>
                    <?php if (empty($recent_jobs)): ?>
                        <p class="sd-muted">No service requests yet.</p>
                    <?php else: ?>
                        <div class="sd-mini-list">
                            <?php foreach ($recent_jobs as $job):
                                $stage = get_post_meta($job->ID, '_sd_stage', true) ?: 'new-request';
                                $stype = get_post_meta($job->ID, '_sd_service_type', true);
                                $color = SD_Post_Types::get_stage_color($stage);
                            ?>
                            <div class="sd-mini-item">
                                <span class="sd-mini-dot" style="background:<?php echo $color; ?>"></span>
                                <div class="sd-mini-info">
                                    <strong>#<?php echo $job->ID; ?> — <?php echo esc_html(SD_Post_Types::SERVICE_TYPES[$stype] ?? $stype); ?></strong>
                                    <span class="sd-mini-date"><?php echo get_the_date('M j, Y', $job); ?></span>
                                </div>
                                <span class="sd-mini-badge" style="background:<?php echo $color; ?>20;color:<?php echo $color; ?>"><?php echo SD_Post_Types::get_stage_label($stage); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    private static function render_active_requests($client_id, $user) {
        $jobs = self::get_client_jobs($client_id, $user, ['new-request', 'approved-priced', 'posted-to-vendors', 'claimed', 'scheduled']);
        ?>
        <div class="sd-section">
            <h3 class="sd-section-title">Active Service Requests</h3>
            <?php if (empty($jobs)): ?>
                <div class="sd-empty-card">
                    <span class="dashicons dashicons-clipboard"></span>
                    <h4>No active requests</h4>
                    <p>Submit a new service request to get started.</p>
                </div>
            <?php else: ?>
                <div class="sd-job-grid">
                    <?php foreach ($jobs as $job): echo self::render_client_job_card($job); endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function render_invoices($client_id, $user) {
        $jobs = self::get_client_jobs($client_id, $user, ['ready-to-invoice', 'closed-paid']);
        ?>
        <div class="sd-section">
            <h3 class="sd-section-title">Invoices & Payments</h3>
            <?php if (empty($jobs)): ?>
                <div class="sd-empty-card">
                    <span class="dashicons dashicons-money-alt"></span>
                    <h4>No invoices yet</h4>
                    <p>Invoices will appear here when your completed services are ready for payment.</p>
                </div>
            <?php else: ?>
                <div class="sd-invoice-list">
                    <?php foreach ($jobs as $job):
                        $stage = get_post_meta($job->ID, '_sd_stage', true);
                        $price = get_post_meta($job->ID, '_sd_client_price', true);
                        $stype = get_post_meta($job->ID, '_sd_service_type', true);
                        $is_paid = ($stage === 'closed-paid');
                    ?>
                    <div class="sd-invoice-row <?php echo $is_paid ? 'paid' : 'unpaid'; ?>">
                        <div class="sd-inv-left">
                            <span class="sd-inv-icon dashicons <?php echo $is_paid ? 'dashicons-yes-alt' : 'dashicons-money-alt'; ?>"></span>
                            <div>
                                <strong>#<?php echo $job->ID; ?> — <?php echo esc_html(SD_Post_Types::SERVICE_TYPES[$stype] ?? $stype); ?></strong>
                                <span class="sd-inv-date"><?php echo get_the_date('M j, Y', $job); ?></span>
                            </div>
                        </div>
                        <div class="sd-inv-right">
                            <?php if ($price): ?>
                                <span class="sd-inv-amount">$<?php echo esc_html(number_format((float)$price, 2)); ?></span>
                            <?php endif; ?>
                            <span class="sd-inv-status <?php echo $is_paid ? 'paid' : 'unpaid'; ?>">
                                <?php echo $is_paid ? 'PAID' : 'PENDING'; ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function render_history($client_id, $user) {
        $jobs = self::get_client_jobs($client_id, $user, [], 50);
        ?>
        <div class="sd-section">
            <h3 class="sd-section-title">Request History</h3>
            <?php if (empty($jobs)): ?>
                <div class="sd-empty-card">
                    <span class="dashicons dashicons-backup"></span>
                    <h4>No history yet</h4>
                    <p>Your completed and past service requests will appear here.</p>
                </div>
            <?php else: ?>
                <div class="sd-history-table-wrap">
                    <table class="sd-history-table">
                        <thead>
                            <tr>
                                <th>Job #</th>
                                <th>Service</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jobs as $job):
                                $stage = get_post_meta($job->ID, '_sd_stage', true) ?: 'new-request';
                                $price = get_post_meta($job->ID, '_sd_client_price', true);
                                $stype = get_post_meta($job->ID, '_sd_service_type', true);
                                $color = SD_Post_Types::get_stage_color($stage);
                            ?>
                            <tr>
                                <td><strong>#<?php echo $job->ID; ?></strong></td>
                                <td><?php echo esc_html(SD_Post_Types::SERVICE_TYPES[$stype] ?? $stype); ?></td>
                                <td><?php echo get_the_date('M j, Y', $job); ?></td>
                                <td><?php echo $price ? '$' . esc_html(number_format((float)$price, 2)) : '—'; ?></td>
                                <td><span class="sd-tbl-badge" style="background:<?php echo $color; ?>20;color:<?php echo $color; ?>"><?php echo SD_Post_Types::get_stage_label($stage); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function render_profile($client_id) {
        $user = get_userdata($client_id);
        $phone   = get_user_meta($client_id, 'sd_client_phone', true);
        $company = get_user_meta($client_id, 'sd_client_company', true);
        $billing_email = get_user_meta($client_id, 'sd_client_billing_email', true);

        if (isset($_POST['sd_client_save_profile']) && wp_verify_nonce($_POST['_sd_client_profile_nonce'], 'sd_client_profile')) {
            $phone   = sanitize_text_field($_POST['sd_client_phone'] ?? '');
            $company = sanitize_text_field($_POST['sd_client_company'] ?? '');
            $billing_email = sanitize_email($_POST['sd_client_billing_email'] ?? '');
            update_user_meta($client_id, 'sd_client_phone', $phone);
            update_user_meta($client_id, 'sd_client_company', $company);
            update_user_meta($client_id, 'sd_client_billing_email', $billing_email);
            echo '<div class="sd-notice sd-notice-success">Profile updated!</div>';
        }
        ?>
        <div class="sd-section">
            <h3 class="sd-section-title">My Profile</h3>
            <form method="post" class="sd-profile-form">
                <?php wp_nonce_field('sd_client_profile', '_sd_client_profile_nonce'); ?>
                <div class="sd-form-row">
                    <label>Full Name</label>
                    <input type="text" value="<?php echo esc_attr($user->display_name); ?>" disabled class="sd-input">
                </div>
                <div class="sd-form-row">
                    <label>Email</label>
                    <input type="email" value="<?php echo esc_attr($user->user_email); ?>" disabled class="sd-input">
                </div>
                <div class="sd-form-row">
                    <label>Company Name</label>
                    <input type="text" name="sd_client_company" value="<?php echo esc_attr($company); ?>" class="sd-input">
                </div>
                <div class="sd-form-row">
                    <label>Phone Number</label>
                    <input type="text" name="sd_client_phone" value="<?php echo esc_attr($phone); ?>" class="sd-input" placeholder="+1234567890">
                </div>
                <div class="sd-form-row">
                    <label>Billing Email</label>
                    <input type="email" name="sd_client_billing_email" value="<?php echo esc_attr($billing_email); ?>" class="sd-input" placeholder="accounts@company.com">
                </div>
                <button type="submit" name="sd_client_save_profile" class="sd-btn sd-btn-primary">Save Profile</button>
            </form>
        </div>
        <?php
    }

    private static function render_client_job_card($job) {
        $id      = $job->ID;
        $stage   = get_post_meta($id, '_sd_stage', true) ?: 'new-request';
        $stype   = get_post_meta($id, '_sd_service_type', true);
        $city    = get_post_meta($id, '_sd_city', true);
        $state   = get_post_meta($id, '_sd_state', true);
        $urgency = get_post_meta($id, '_sd_urgency', true);
        $price   = get_post_meta($id, '_sd_client_price', true);
        $date    = get_post_meta($id, '_sd_preferred_date', true);

        $service_label = SD_Post_Types::SERVICE_TYPES[$stype] ?? $stype;
        $stage_color   = SD_Post_Types::get_stage_color($stage);
        $stage_label   = SD_Post_Types::get_stage_label($stage);

        ob_start();
        ?>
        <div class="sd-job-card">
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
                <?php if ($price): ?>
                    <div class="sd-jc-detail"><span class="dashicons dashicons-money-alt"></span> $<?php echo esc_html(number_format((float)$price, 2)); ?></div>
                <?php endif; ?>
                <?php if ($date): ?>
                    <div class="sd-jc-detail"><span class="dashicons dashicons-calendar-alt"></span> <?php echo esc_html(date('M j, Y', strtotime($date))); ?></div>
                <?php endif; ?>
            </div>

            <div class="sd-jc-progress">
                <div class="sd-progress-bar">
                    <?php
                    $stage_order = array_keys(SD_Post_Types::STAGES);
                    $current_idx = array_search($stage, $stage_order);
                    $total = count($stage_order) - 1;
                    $pct = $current_idx !== false ? round(($current_idx / $total) * 100) : 0;
                    ?>
                    <div class="sd-progress-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $stage_color; ?>"></div>
                </div>
                <span class="sd-progress-text"><?php echo $pct; ?>% complete</span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function get_client_jobs($client_id, $user, $stages = [], $limit = -1) {
        $meta_query = [
            ['key' => '_sd_client_email', 'value' => $user->user_email],
        ];
        if (!empty($stages)) {
            $meta_query[] = ['key' => '_sd_stage', 'value' => $stages, 'compare' => 'IN'];
        }
        return get_posts([
            'post_type'      => 'sd_job',
            'posts_per_page' => $limit,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => $meta_query,
        ]);
    }

    private static function count_client_jobs($client_id, $stages = []) {
        $user = get_userdata($client_id);
        $meta_query = [
            ['key' => '_sd_client_email', 'value' => $user->user_email],
        ];
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

    private static function render_login_prompt() {
        ob_start();
        ?>
        <div class="sd-login-prompt">
            <div class="sd-login-card">
                <span class="dashicons dashicons-lock"></span>
                <h2>Please Log In</h2>
                <p>You need to be logged in to access your dashboard.</p>
                <a href="<?php echo wp_login_url(get_permalink()); ?>" class="sd-btn sd-btn-primary">Log In</a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
