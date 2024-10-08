<?php
/**
 * Plugin Name: SodaBag
 * Plugin URI: https://example.com/sodabag
 * Description: A comprehensive WordPress plugin to collect, manage, and showcase customer stories.
 * Version: 1.0.1
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: sodabag
 */

if (!defined('WPINC')) {
    die;
}

define('SODABAG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SODABAG_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once SODABAG_PLUGIN_DIR . 'includes/class-sodabag-core.php';

function run_sodabag() {
    $plugin = new SodaBag_Core();
    $plugin->run();
}

register_activation_hook(__FILE__, array('SodaBag_Core', 'activate'));
register_deactivation_hook(__FILE__, array('SodaBag_Core', 'deactivate'));

run_sodabag();

function sodabag_rest_api_init() {
    $plugin = new SodaBag_Core();
    $plugin_public = new SodaBag_Public($plugin->get_plugin_name());
    $plugin_public->register_rest_routes();
}
add_action('rest_api_init', 'sodabag_rest_api_init');
