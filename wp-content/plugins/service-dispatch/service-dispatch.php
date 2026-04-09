<?php
/**
 * Plugin Name: Service Dispatch Automation
 * Description: Fully automated job management system — client requests, vendor dispatch, SMS automation, invoicing, and payment tracking.
 * Version: 1.0.0
 * Author: Automation Team
 * Text Domain: service-dispatch
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) exit;

define('SD_VERSION', '1.0.0');
define('SD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SD_PLUGIN_BASENAME', plugin_basename(__FILE__));

final class ServiceDispatch {

    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies() {
        require_once SD_PLUGIN_DIR . 'includes/class-sd-post-types.php';
        require_once SD_PLUGIN_DIR . 'includes/class-sd-roles.php';
        require_once SD_PLUGIN_DIR . 'includes/class-sd-meta-boxes.php';
        require_once SD_PLUGIN_DIR . 'includes/class-sd-admin-dashboard.php';
        require_once SD_PLUGIN_DIR . 'includes/class-sd-vendor-dashboard.php';
        require_once SD_PLUGIN_DIR . 'includes/class-sd-client-dashboard.php';
        require_once SD_PLUGIN_DIR . 'includes/class-sd-ajax-handlers.php';
    }

    private function init_hooks() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        SD_Post_Types::init();
        SD_Roles::init();
        SD_Meta_Boxes::init();
        SD_Admin_Dashboard::init();
        SD_Vendor_Dashboard::init();
        SD_Client_Dashboard::init();
        SD_Ajax_Handlers::init();

        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function activate() {
        SD_Post_Types::register();
        SD_Roles::create_roles();
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function enqueue_frontend_assets() {
        global $post;
        $content = ($post && !empty($post->post_content)) ? $post->post_content : '';

        if (is_page('vendor-dashboard') || has_shortcode($content, 'sd_vendor_dashboard')) {
            wp_enqueue_style('dashicons');
            wp_enqueue_style('sd-vendor-dashboard', SD_PLUGIN_URL . 'assets/css/vendor-dashboard.css', [], SD_VERSION);
            wp_enqueue_script('sd-vendor-dashboard', SD_PLUGIN_URL . 'assets/js/vendor-dashboard.js', ['jquery'], SD_VERSION, true);
            wp_localize_script('sd-vendor-dashboard', 'sdVendor', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('sd_vendor_nonce'),
            ]);
        }
        if (is_page('client-dashboard') || has_shortcode($content, 'sd_client_dashboard')) {
            wp_enqueue_style('dashicons');
            wp_enqueue_style('sd-client-dashboard', SD_PLUGIN_URL . 'assets/css/client-dashboard.css', [], SD_VERSION);
            wp_enqueue_script('sd-client-dashboard', SD_PLUGIN_URL . 'assets/js/client-dashboard.js', ['jquery'], SD_VERSION, true);
            wp_localize_script('sd-client-dashboard', 'sdClient', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('sd_client_nonce'),
            ]);
        }
    }

    public function enqueue_admin_assets($hook) {
        $screen = get_current_screen();
        if (!$screen) return;

        $allowed = ['sd_job', 'toplevel_page_sd-pipeline', 'service-dispatch_page_sd-pipeline'];
        if ($screen->post_type === 'sd_job' || in_array($screen->id, $allowed)) {
            wp_enqueue_style('sd-admin-dashboard', SD_PLUGIN_URL . 'assets/css/admin-dashboard.css', [], SD_VERSION);
            wp_enqueue_script('sd-admin-dashboard', SD_PLUGIN_URL . 'assets/js/admin-dashboard.js', ['jquery', 'jquery-ui-sortable'], SD_VERSION, true);
            wp_localize_script('sd-admin-dashboard', 'sdAdmin', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('sd_admin_nonce'),
            ]);
        }
    }
}

ServiceDispatch::instance();
