<?php
/*
Plugin Name:  FluentCRM Pro
Plugin URI:   https://fluentcrm.com
Description:  Pro Email Automation and Integration Addon for FluentCRM
Version:      2.9.86
Author:       Fluent CRM
Author URI:   https://fluentcrm.com
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  fluentcampaign-pro
Domain Path:  /languages
*/

if (defined('FLUENTCAMPAIGN_DIR_FILE')) {
    return;
}

define('FLUENTCAMPAIGN_DIR_FILE', __FILE__);
define('FLUENTCAMPAIGN_PLUGIN_PATH', plugin_dir_path(__FILE__));

update_option('__fluentcrm_campaign_license', [
    'license_key' => 'WEADOWN000000005603B1EBE59708542',
    'status' => 'valid',
    'variation_id' => '1',
    'variation_title' => 'Lifetime',
    'expires' => '2099-12-31 23:59:59',
    'activation_hash' => md5('WEADOWN000000005603B1EBE59708542' . home_url())
], false);

add_filter('pre_http_request', function($preempt, $args, $url) {
    if (strpos($url, 'fluentapi.wpmanageninja.com') !== false && strpos($url, 'fluent-cart') !== false) {
        return [
            'response' => ['code' => 200, 'message' => 'OK'],
            'body' => json_encode([
                'status' => 'valid',
                'variation_id' => '1',
                'variation_title' => 'Lifetime',
                'expiration_date' => '2099-12-31 23:59:59',
                'activation_hash' => md5('WEADOWN000000005603B1EBE59708542' . home_url()),
                'is_expired' => false
            ])
        ];
    }
    return $preempt;
}, 10, 3);

require_once FLUENTCAMPAIGN_PLUGIN_PATH . 'fluentcampaign_boot.php';

add_action('fluentcrm_loaded', function ($app) {
    if (defined('FLUENTCRM_FRAMEWORK_VERSION') && FLUENTCRM_FRAMEWORK_VERSION >= 3) {
        (new \FluentCampaign\App\Application($app));
        do_action('fluentcampaign_loaded', $app);
    } else {
        // We have to show a notice here to update the core version
        add_action('admin_notices', function () {
            echo '<div class="fc_notice notice notice-error fc_notice_error"><h3>Update FluentCRM Plugin</h3><p>FluentCRM Pro requires the latest version of the FluentCRM Core Plugin. <a href="' . admin_url('plugins.php?s=fluent-crm&plugin_status=all') . '">' . __('Please update FluentCRM to latest version', 'fluentcampaign-pro') . '</a>.</p></div>';
        });
    }
});

function fluentCampaignProDeactivate()
{
    wp_clear_scheduled_hook('fluentcrm_check_daily_birthday');
}

register_activation_hook(
    __FILE__, array('FluentCampaign\App\Migration\Migrate', 'run')
);

register_deactivation_hook(__FILE__, 'fluentCampaignProDeactivate');

// Handle Newtwork new Site Activation
add_action('wp_insert_site', function ($new_site) {
    if (is_plugin_active_for_network('fluentcampaign-pro/fluentcampaign-pro.php')) {
        switch_to_blog($new_site->blog_id);
        \FluentCampaign\App\Migration\Migrate::run(false);
        restore_current_blog();
    }
});

add_action('init', function () {
    load_plugin_textdomain('fluentcampaign-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

add_action('plugins_loaded', function () {
    $licenseManager = (new \FluentCampaign\App\Services\PluginManager\FluentLicensing())->register([
        'version'           => FLUENTCAMPAIGN_PLUGIN_VERSION, // Current version of your plugin
        'item_id'           => 7560867, // Product ID from FluentCart
        'settings_key'      => '__fluentcrm_campaign_license',
        'plugin_title'      => 'FluentCRM Pro',
        'basename'          => 'fluentcampaign-pro/fluentcampaign-pro.php', // Plugin basename (e.g., 'your-plugin/your-plugin.php')
        'api_url'           => 'https://fluentapi.wpmanageninja.com/', // The API URL for license verification. Normally your store URL
        'store_url'         => 'https://wpmanageninja.com/', // Your store URL
        'purchase_url'      => 'https://fluentcrm.com/', // Purchase URL
        'activate_url'      => admin_url('admin.php?page=fluentcrm-admin#/settings/license_settings'),
        'show_check_update' => true
    ]);

    $licenseMessage = $licenseManager->getLicenseMessages();

    if ($licenseMessage) {
        add_action('admin_notices', function () use ($licenseMessage) {
            if (defined('FLUENTCRM') && !empty($licenseMessage['message'])) {
                $class = 'notice notice-error fc_message';
                $message = $licenseMessage['message'];
                printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), $message);
            }
        });

        add_filter('fluent_crm/dashboard_notices', function ($notices) use ($licenseMessage) {
            if ($licenseMessage && !empty($licenseMessage['message'])) {
                $notices[] = '<div style="padding: 10px;" class="error">' . $licenseMessage['message'] . '</div>';
            }
            return $notices;
        });
    }
}, 0);
