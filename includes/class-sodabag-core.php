<?php

class SodaBag_Core {
    protected $loader;
    protected $plugin_name;
    protected $plugin_public;

    public function __construct() {
        $this->plugin_name = 'sodabag';
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->check_for_db_update();
    }

    private function load_dependencies() {
        require_once SODABAG_PLUGIN_DIR . 'includes/class-sodabag-loader.php';
        require_once SODABAG_PLUGIN_DIR . 'includes/class-sodabag-admin.php';
        require_once SODABAG_PLUGIN_DIR . 'includes/class-sodabag-public.php';
        require_once SODABAG_PLUGIN_DIR . 'includes/class-sodabag-hubspot.php';

        $this->loader = new SodaBag_Loader();
        $this->plugin_public = new SodaBag_Public($this->get_plugin_name());
    }

    private function define_admin_hooks() {
        $plugin_admin = new SodaBag_Admin($this->get_plugin_name());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        $this->loader->add_action('admin_init', $plugin_admin, 'register_setting');
        $this->loader->add_action('wp_ajax_sodabag_delete_all_data', $plugin_admin, 'delete_all_data');
        $this->loader->add_action('wp_ajax_sodabag_populate_demo_data', $plugin_admin, 'populate_demo_data');
        $this->loader->add_action('admin_init', $plugin_admin, 'add_llm_integration_fields');
        $this->loader->add_action('wp_ajax_sodabag_check_llm_database_columns', $plugin_admin, 'check_llm_database_columns');
        $this->loader->add_action('wp_ajax_sodabag_create_rest_endpoint', $plugin_admin, 'create_rest_endpoint');
		$this->loader->add_action('wp_ajax_sodabag_populate_custom_url_data', $plugin_admin, 'populate_custom_url_data');
		$this->loader->add_action('wp_ajax_sodabag_populate_custom_story_url_data', $plugin_admin, 'populate_custom_story_url_data');
        $this->loader->add_action('wp_ajax_sodabag_populate_hubspot_integration', $plugin_admin, 'populate_hubspot_integration');
    }

    private function define_public_hooks() {
        $this->loader->add_action('wp_enqueue_scripts', $this->plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $this->plugin_public, 'enqueue_scripts');
        $this->loader->add_action('init', $this->plugin_public, 'register_shortcodes');
        $this->loader->add_action('wp_ajax_sodabag_submit_story', $this->plugin_public, 'handle_story_submission');
        $this->loader->add_action('wp_ajax_nopriv_sodabag_submit_story', $this->plugin_public, 'handle_story_submission');
        $this->loader->add_action('wp_ajax_sodabag_create_campaign', $this->plugin_public, 'create_campaign');
        $this->loader->add_action('wp_ajax_sodabag_get_campaign_details', $this->plugin_public, 'get_campaign_details');
        $this->loader->add_action('wp_ajax_sodabag_edit_campaign', $this->plugin_public, 'edit_campaign');
        $this->loader->add_action('wp_ajax_sodabag_delete_campaign', $this->plugin_public, 'delete_campaign');
        $this->loader->add_action('wp_ajax_sodabag_moderate_story', $this->plugin_public, 'moderate_story');
		$this->loader->add_action('wp_ajax_sodabag_delete_story', $this->plugin_public, 'delete_story');
        $this->loader->add_action('wp_ajax_sodabag_login', $this->plugin_public, 'handle_login');
        $this->loader->add_action('wp_ajax_nopriv_sodabag_login', $this->plugin_public, 'handle_login');
        $this->loader->add_action('wp_ajax_sodabag_register', $this->plugin_public, 'handle_registration');
        $this->loader->add_action('wp_ajax_nopriv_sodabag_register', $this->plugin_public, 'handle_registration');
        $this->loader->add_action('wp_ajax_sodabag_share_story', $this->plugin_public, 'share_story');
        $this->loader->add_action('wp_ajax_nopriv_sodabag_share_story', $this->plugin_public, 'share_story');
        $this->loader->add_action('wp_ajax_sodabag_track_share', $this->plugin_public, 'track_share');
        $this->loader->add_action('wp_ajax_nopriv_sodabag_track_share', $this->plugin_public, 'track_share');
        $this->loader->add_action('wp_ajax_sodabag_share_story_email', $this->plugin_public, 'share_story_email');
        $this->loader->add_action('wp_ajax_nopriv_sodabag_share_story_email', $this->plugin_public, 'share_story_email');
        $this->loader->add_action('wp_ajax_sodabag_filter_sort_stories', $this->plugin_public, 'filter_sort_stories');
        $this->loader->add_action('wp_ajax_nopriv_sodabag_filter_sort_stories', $this->plugin_public, 'filter_sort_stories');
        $this->loader->add_action('template_redirect', $this->plugin_public, 'handle_qr_scan');
    $this->loader->add_action('template_redirect', $this->plugin_public, 'handle_qr_redirect');
        $this->loader->add_action('wp_ajax_sodabag_like_story', $this->plugin_public, 'like_story');
        $this->loader->add_action('wp_ajax_sodabag_share_to_hubspot', $this->plugin_public, 'share_to_hubspot');
        $this->loader->add_action('wp_ajax_sodabag_save_llm_settings', $this->plugin_public, 'save_llm_settings');
        $this->loader->add_action('wp_ajax_nopriv_sodabag_like_story', $this->plugin_public, 'like_story');

        // Add this line to register REST routes
         add_action('rest_api_init', array($this->plugin_public, 'register_rest_routes'));

        $plugin_hubspot = new SodaBag_HubSpot($this->get_plugin_name());
        $plugin_hubspot->init();
    }

    public function run() {
        $this->loader->run();
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_loader() {
        return $this->loader;
    }

    public static function activate() {
        self::create_custom_roles();
        self::create_database_tables();
        self::create_pages();
        
        $admin = new SodaBag_Admin('sodabag');
        $admin->check_and_update_database();
    }

    public static function deactivate() {
        // Cleanup tasks if needed
    }

    public static function uninstall() {
        self::delete_custom_roles();
        self::delete_database_tables();
        self::delete_pages();
        delete_option('sodabag');
    }

    private function check_for_db_update() {
        $admin = new SodaBag_Admin($this->get_plugin_name());
        $admin->check_and_update_database();
    }

    private static function create_custom_roles() {
        add_role('business_owner', 'Business Owner', array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'publish_posts' => false,
            'upload_files' => true,
        ));
    }

    private static function delete_custom_roles() {
        remove_role('business_owner');
    }

    public static function create_database_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $campaigns_table = $wpdb->prefix . 'soda_campaigns';
    $stories_table = $wpdb->prefix . 'soda_stories';
    $analytics_table = $wpdb->prefix . 'soda_analytics';

    $sql = array();

    $sql[] = "CREATE TABLE IF NOT EXISTS $campaigns_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        name varchar(255) NOT NULL,
        description text NOT NULL,
        media_type varchar(50) NOT NULL,
        custom_fields text,
        logo_url varchar(255),
        custom_text text,
        sharing_text text,
        sharing_hashtags varchar(255),
        sharing_url varchar(255),
        email_subject varchar(255),
        email_body text,
		custom_story_url varchar(255),
        primary_color varchar(7),
        secondary_color varchar(7),
        require_terms_acceptance tinyint(1) DEFAULT 0,
        terms_acceptance_text text,
        ai_moderation_enabled tinyint(1) DEFAULT 0,
        selected_llm varchar(50),
        custom_submission_url varchar(255),
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        updated_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    $sql[] = "CREATE TABLE IF NOT EXISTS $stories_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        campaign_id mediumint(9) NOT NULL,
        user_id bigint(20) NOT NULL,
        content text NOT NULL,
        media_url varchar(255),
        custom_fields text,
        status varchar(20) NOT NULL,
        submitter_name varchar(255),
        submitter_email varchar(255),
        likes int(11) DEFAULT 0,
        shares int(11) DEFAULT 0,
        ai_moderation_status varchar(20),
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    $sql[] = "CREATE TABLE IF NOT EXISTS $analytics_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        campaign_id mediumint(9) NOT NULL,
        story_id mediumint(9),
        event_type varchar(50) NOT NULL,
        event_data text,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    foreach ($sql as $query) {
        dbDelta($query);
    }
}

    private static function delete_database_tables() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}soda_campaigns");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}soda_stories");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}soda_analytics");
    }

    private static function create_pages() {
        $pages = array(
            'business-owner-dashboard' => array(
                'title' => 'Business Owner Dashboard',
                'content' => '[sodabag_dashboard]'
            ),
            'sodabag-login-register' => array(
                'title' => 'SodaBag Login/Register',
                'content' => '[sodabag_login_register]'
            ),
            'sodabag-submission' => array(
                'title' => 'Submit Your Story',
                'content' => '[sodabag_submission]'
            ),
            'sodabag-qr-redirect' => array(
                'title' => 'QR Code Redirect',
                'content' => '<!-- SodaBag QR Code Redirect -->'
            ),
            'sodabag-campaign-details' => array(
                'title' => 'Campaign Details',
                'content' => '[sodabag_campaign_details]'
            ),
            'sodabag-campaign-stories' => array(
                'title' => 'Campaign Stories',
                'content' => '[sodabag_campaign_stories]'
            ),
            'sodabag-analytics' => array(
                'title' => 'Campaign Analytics',
                'content' => '[sodabag_analytics]'
            ),
            'sodabag-integrations' => array(
                'title' => 'SodaBag Integrations',
                'content' => '[sodabag_integrations]'
            )
        );

        foreach ($pages as $slug => $page) {
            $existing_page = get_page_by_path($slug);
            if (null === $existing_page) {
                wp_insert_post(array(
                    'post_title' => $page['title'],
                    'post_content' => $page['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_name' => $slug
                ));
            }
        }
    }

    private static function delete_pages() {
        $pages = array('business-owner-dashboard', 'sodabag-login-register', 'sodabag-submission', 'sodabag-qr-redirect', 'sodabag-campaign-details', 'sodabag-campaign-stories', 'sodabag-analytics', 'sodabag-integrations');
        foreach ($pages as $slug) {
            $page = get_page_by_path($slug);
            if ($page) {
                wp_delete_post($page->ID, true);
            }
        }
    }
}