<?php
/**
 * My Account — vendors use SaaS sidebar; others use default WooCommerce navigation.
 *
 * @package Entaro Child
 */

defined('ABSPATH') || exit;

$user = wp_get_current_user();
$is_sd_vendor = $user && in_array('sd_vendor', (array) $user->roles, true);

if ($is_sd_vendor && class_exists('SD_Vendor_Dashboard')) {
	echo '<div class="sd-wc-vendor-account-wrap">';
	SD_Vendor_Dashboard::render_account_sidebar_wc();
	echo '<div class="woocommerce-MyAccount-content sd-vendor-wc-main">';
	do_action('woocommerce_account_content');
	echo '</div></div>';
} else {
	do_action('woocommerce_account_navigation');
	echo '<div class="woocommerce-MyAccount-content">';
	do_action('woocommerce_account_content');
	echo '</div>';
}
