<?php
if (!defined('ABSPATH')) exit;

class SD_Roles {

    public static function init() {
        add_action('init', [__CLASS__, 'ensure_roles']);
    }

    public static function create_roles() {
        self::add_vendor_role();
        self::add_client_role();
    }

    public static function ensure_roles() {
        if (!get_role('sd_vendor')) {
            self::add_vendor_role();
        }
        if (!get_role('sd_client')) {
            self::add_client_role();
        }
    }

    private static function add_vendor_role() {
        add_role('sd_vendor', 'Vendor', [
            'read'         => true,
            'upload_files' => true,
        ]);
    }

    private static function add_client_role() {
        add_role('sd_client', 'Client', [
            'read'         => true,
            'upload_files' => true,
        ]);
    }
}
