<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete custom role
remove_role('business_owner');

// Delete custom database tables
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}soda_campaigns");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}soda_stories");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}soda_analytics");

// Delete plugin options
delete_option('sodabag');
delete_option('sodabag_db_version');

// Delete custom pages
$pages = array('business-owner-dashboard', 'sodabag-login-register', 'sodabag-submission', 'sodabag-qr-redirect', 'sodabag-campaign-details', 'sodabag-campaign-stories', 'sodabag-analytics');
foreach ($pages as $slug) {
    $page = get_page_by_path($slug);
    if ($page) {
        wp_delete_post($page->ID, true);
    }
}