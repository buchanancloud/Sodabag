<?php

class SodaBag_Admin {
    private $plugin_name;

    public function __construct($plugin_name) {
        $this->plugin_name = $plugin_name;
    }

    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, SODABAG_PLUGIN_URL . 'assets/css/sodabag-styles.css', array(), null, 'all');
    }

    public function enqueue_scripts() {
    wp_enqueue_script($this->plugin_name, SODABAG_PLUGIN_URL . 'assets/js/sodabag-scripts.js', array('jquery'), null, false);
    wp_localize_script($this->plugin_name, 'sodabag_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('sodabag-admin-nonce')
    ));
    wp_add_inline_script($this->plugin_name, '
        jQuery(document).ready(function($) {
            $("#sodabag-delete-data").on("click", function() {
                if (confirm("Are you sure you want to delete all plugin data? This action cannot be undone.")) {
                    $.post(ajaxurl, {
                        action: "sodabag_delete_all_data",
                        nonce: sodabag_ajax.nonce
                    }, function(response) {
                        alert(response.data.message);
                        location.reload();
                    });
                }
            });

            $("#sodabag-populate-demo").on("click", function() {
                if (confirm("Are you sure you want to populate demo data? This will overwrite any existing data.")) {
                    $.post(ajaxurl, {
                        action: "sodabag_populate_demo_data",
                        nonce: sodabag_ajax.nonce
                    }, function(response) {
                        alert(response.data.message);
                        location.reload();
                    });
                }
            });

            $("#sodabag-check-llm-columns").on("click", function() {
                $.post(ajaxurl, {
                    action: "sodabag_check_llm_database_columns",
                    nonce: sodabag_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        alert(response.data.message);
                    } else {
                        alert("Error: " + response.data.message);
                    }
                });
            });

            $("#sodabag-create-rest-endpoint").on("click", function() {
                $.post(ajaxurl, {
                    action: "sodabag_create_rest_endpoint",
                    nonce: sodabag_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        alert(response.data.message);
                    } else {
                        alert("Error: " + response.data.message);
                    }
                });
            });

            $("#sodabag-populate-custom-url").on("click", function() {
                $.post(ajaxurl, {
                    action: "sodabag_populate_custom_url_data",
                    nonce: sodabag_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        alert(response.data.message);
                    } else {
                        alert("Error: " + response.data.message);
                    }
                });
            });
        });
    ');
}

    public function add_plugin_admin_menu() {
        add_menu_page(
            'SodaBag Settings',
            'SodaBag',
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_setup_page'),
            'dashicons-testimonial',
            30
        );

        add_submenu_page(
            $this->plugin_name,
            'Campaign Analytics',
            'Analytics',
            'manage_options',
            $this->plugin_name . '-analytics',
            array($this, 'display_analytics_page')
        );
    }

    public function display_plugin_setup_page() {
        include_once SODABAG_PLUGIN_DIR . 'templates/admin-settings.php';
        $this->add_debug_info();
        $this->add_debug_log_viewer();
    }

    public function display_analytics_page() {
        $campaign_id = isset($_GET['campaign_id']) ? intval($_GET['campaign_id']) : 0;
        $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : null;
        $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : null;
        
        $campaigns = $this->get_campaigns();

        if ($campaign_id) {
            $analytics = $this->get_campaign_analytics($campaign_id, $start_date, $end_date);
        }

        include SODABAG_PLUGIN_DIR . 'templates/admin-analytics.php';
    }

    public function register_setting() {
        register_setting($this->plugin_name, $this->plugin_name, array($this, 'validate'));

        add_settings_section(
            'sodabag_general_settings',
            'General Settings',
            array($this, 'general_settings_callback'),
            $this->plugin_name
        );

        // Add LLM integration fields
        $this->add_llm_integration_fields();
    }

    public function general_settings_callback() {
        echo '<p>Configure general settings for SodaBag.</p>';
    }

    public function add_llm_integration_fields() {
        add_settings_section(
            'sodabag_llm_settings',
            'LLM Integration Settings',
            array($this, 'llm_settings_callback'),
            $this->plugin_name
        );

        add_settings_field(
            'anthropic_api_key',
            'Anthropic API Key',
            array($this, 'text_field_callback'),
            $this->plugin_name,
            'sodabag_llm_settings',
            array('label_for' => 'anthropic_api_key')
        );

        add_settings_field(
            'openai_api_key',
            'OpenAI API Key',
            array($this, 'text_field_callback'),
            $this->plugin_name,
            'sodabag_llm_settings',
            array('label_for' => 'openai_api_key')
        );
    }

    public function llm_settings_callback() {
        echo '<p>Enter your API keys for LLM integrations.</p>';
    }

    public function text_field_callback($args) {
        $options = get_option($this->plugin_name);
        $value = isset($options[$args['label_for']]) ? $options[$args['label_for']] : '';
        echo '<input type="text" id="' . $args['label_for'] . '" name="' . $this->plugin_name . '[' . $args['label_for'] . ']" value="' . esc_attr($value) . '">';
    }

    public function validate($input) {
        $valid = array();
        
        // Validate LLM API keys
        $valid['anthropic_api_key'] = isset($input['anthropic_api_key']) ? sanitize_text_field($input['anthropic_api_key']) : '';
        $valid['openai_api_key'] = isset($input['openai_api_key']) ? sanitize_text_field($input['openai_api_key']) : '';

        return $valid;
    }

    public function delete_all_data() {
        check_ajax_referer('sodabag-admin-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
            return;
        }

        try {
            SodaBag_Core::uninstall();
            wp_send_json_success(array('message' => 'All plugin data has been deleted.'));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error deleting data: ' . $e->getMessage()));
        }
    }

    public function populate_demo_data() {
        check_ajax_referer('sodabag-admin-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
            return;
        }

        try {
            // Check and update database structure
            $this->check_and_update_database();

            // Ensure tables and pages exist
            SodaBag_Core::create_database_tables();
            SodaBag_Core::create_pages();
            
            global $wpdb;
            
            // Create demo user
            $demo_user_id = wp_insert_user(array(
                'user_login' => 'demo_business_owner',
                'user_pass' => wp_generate_password(),
                'user_email' => 'demo@example.com',
                'role' => 'business_owner'
            ));
            
            if (is_wp_error($demo_user_id)) {
                throw new Exception('Failed to create demo user: ' . $demo_user_id->get_error_message());
            }
            
            // Create demo campaign
            $campaigns_table = $wpdb->prefix . 'soda_campaigns';
            $result = $wpdb->insert(
                $campaigns_table,
                array(
                    'user_id' => $demo_user_id,
                    'name' => 'Demo Campaign',
                    'description' => 'This is a demo campaign for testing purposes.',
                    'media_type' => 'both',
                    'custom_fields' => serialize(array(
                        array('name' => 'Rating', 'type' => 'number'),
                        array('name' => 'Feedback', 'type' => 'textarea')
                    )),
                    'primary_color' => '#d13469',
                    'secondary_color' => '#b02d59',
                    'require_terms_acceptance' => 1,
                    'terms_acceptance_text' => 'I agree to share my story.',
                    'ai_moderation_enabled' => 1,
                    'selected_llm' => 'gpt-4',
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                )
            );
            
            if ($result === false) {
                throw new Exception('Failed to create demo campaign: ' . $wpdb->last_error);
            }
            
            $campaign_id = $wpdb->insert_id;
            
            // Create demo story
            $stories_table = $wpdb->prefix . 'soda_stories';
            $result = $wpdb->insert(
                $stories_table,
                array(
                    'campaign_id' => $campaign_id,
                    'user_id' => $demo_user_id,
                    'content' => 'This is a demo story submission.',
                    'media_url' => '',
                    'custom_fields' => serialize(array(
                        'Rating' => 5,
                        'Feedback' => 'Great experience!'
                    )),
                    'status' => 'approved',
                    'submitter_name' => 'Demo User',
                    'submitter_email' => 'demo@example.com',
                    'likes' => 10,
                    'shares' => 5,
                    'ai_moderation_status' => 'pass',
                    'created_at' => current_time('mysql')
                )
            );
            
            if ($result === false) {
                throw new Exception('Failed to create demo story: ' . $wpdb->last_error);
            }
            
            wp_send_json_success(array('message' => 'Demo data has been populated successfully.'));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error populating demo data: ' . $e->getMessage()));
        }
    }

	public function create_rest_endpoint() {
    check_ajax_referer('sodabag-admin-nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
        return;
    }

    $message = "Debugging REST API Registration:\n\n";

    // Check if rest_get_server() is available
    if (!function_exists('rest_get_server')) {
        $message .= "rest_get_server() function is not available. REST API might not be initialized.\n";
    } else {
        $server = rest_get_server();
        $message .= "REST API server is available.\n";
    }

    // Attempt to register routes
    $plugin_public = new SodaBag_Public($this->plugin_name);
    $registration_result = $plugin_public->register_rest_routes();
    $message .= "Route registration attempt completed.\n";

    // Check registered routes
    $routes = $server ? $server->get_routes() : array();
    $message .= "Total registered routes: " . count($routes) . "\n";
    $message .= "Is 'sodabag/v1/stories' registered: " . (isset($routes['sodabag/v1/stories']) ? 'Yes' : 'No') . "\n";
    $message .= "Is 'sodabag/v1/submit-story' registered: " . (isset($routes['sodabag/v1/submit-story']) ? 'Yes' : 'No') . "\n";

    // Check for similarly named routes
    $similar_routes = array_filter(array_keys($routes), function($route) {
        return strpos($route, 'sodabag') !== false;
    });
    if (!empty($similar_routes)) {
        $message .= "Similar routes found:\n" . implode("\n", $similar_routes) . "\n";
    }

    // Test endpoints
    $stories_endpoint = rest_url('sodabag/v1/stories');
    $submit_endpoint = rest_url('sodabag/v1/submit-story');

    $stories_response = wp_remote_get($stories_endpoint);
    $submit_response = wp_remote_get($submit_endpoint);

    $message .= "\nEndpoint Tests:\n";
    $message .= "Stories Endpoint: " . $stories_endpoint . "\n";
    $message .= "Stories Response Code: " . wp_remote_retrieve_response_code($stories_response) . "\n";
    $message .= "Stories Response Body: " . wp_remote_retrieve_body($stories_response) . "\n";
    $message .= "Submit Endpoint: " . $submit_endpoint . "\n";
    $message .= "Submit Response Code: " . wp_remote_retrieve_response_code($submit_response) . "\n";

    // Check WordPress options
    $permalink_structure = get_option('permalink_structure');
    $message .= "\nWordPress Configuration:\n";
    $message .= "Permalink Structure: " . ($permalink_structure ? $permalink_structure : 'Default') . "\n";

    wp_send_json_success(array('message' => $message));
}
	
    public function check_llm_database_columns() {
        global $wpdb;
        $campaigns_table = $wpdb->prefix . 'soda_campaigns';
        $stories_table = $wpdb->prefix . 'soda_stories';

        $wpdb->query("ALTER TABLE $campaigns_table ADD COLUMN IF NOT EXISTS ai_moderation_enabled TINYINT(1) DEFAULT 0");
        $wpdb->query("ALTER TABLE $campaigns_table ADD COLUMN IF NOT EXISTS selected_llm VARCHAR(50) DEFAULT NULL");
        $wpdb->query("ALTER TABLE $stories_table ADD COLUMN IF NOT EXISTS ai_moderation_status VARCHAR(20) DEFAULT NULL");

        wp_send_json_success(array('message' => 'LLM database columns checked and updated successfully.'));
    }

    public function check_and_update_database() {
        global $wpdb;
        $campaigns_table = $wpdb->prefix . 'soda_campaigns';
        $stories_table = $wpdb->prefix . 'soda_stories';

        // Check if ai_moderation_enabled column exists in campaigns table
        $ai_moderation_enabled_exists = $wpdb->get_results("SHOW COLUMNS FROM {$campaigns_table} LIKE 'ai_moderation_enabled'");
        if (empty($ai_moderation_enabled_exists)) {
            $wpdb->query("ALTER TABLE {$campaigns_table} ADD COLUMN ai_moderation_enabled TINYINT(1) DEFAULT 0 AFTER terms_acceptance_text");
        }

        // Check if selected_llm column exists in campaigns table
        $selected_llm_exists = $wpdb->get_results("SHOW COLUMNS FROM {$campaigns_table} LIKE 'selected_llm'");
        if (empty($selected_llm_exists)) {
            $wpdb->query("ALTER TABLE {$campaigns_table} ADD COLUMN selected_llm VARCHAR(50) DEFAULT NULL AFTER ai_moderation_enabled");
        }

        // Check if ai_moderation_status column exists in stories table
        $ai_moderation_status_exists = $wpdb->get_results("SHOW COLUMNS FROM {$stories_table} LIKE 'ai_moderation_status'");
        if (empty($ai_moderation_status_exists)) {
            $wpdb->query("ALTER TABLE {$stories_table} ADD COLUMN ai_moderation_status VARCHAR(20) DEFAULT NULL AFTER shares");
        }
    }

    public function add_debug_info() {
        echo '<div class="debug-info" style="background-color: #f0f0f0; padding: 15px; margin-top: 20px; border: 1px solid #ccc;">';
        echo '<h3>Debug Information</h3>';
        
        // Check if tables exist
        global $wpdb;
        $campaigns_table = $wpdb->prefix . 'soda_campaigns';
        $stories_table = $wpdb->prefix . 'soda_stories';
        $analytics_table = $wpdb->prefix . 'soda_analytics';
        
        echo '<p>Campaigns table exists: ' . ($wpdb->get_var("SHOW TABLES LIKE '$campaigns_table'") == $campaigns_table ? 'Yes' : 'No') . '</p>';
        echo '<p>Stories table exists: ' . ($wpdb->get_var("SHOW TABLES LIKE '$stories_table'") == $stories_table ? 'Yes' : 'No') . '</p>';
        echo '<p>Analytics table exists: ' . ($wpdb->get_var("SHOW TABLES LIKE '$analytics_table'") == $analytics_table ? 'Yes' : 'No') . '</p>';
        
        // Check upload directory permissions
        $upload_dir = wp_upload_dir();
        echo '<p>Upload directory writable: ' . (wp_is_writable($upload_dir['path']) ? 'Yes' : 'No') . '</p>';
        echo '<p>Upload directory path: ' . $upload_dir['path'] . '</p>';
        
        // Display PHP memory limit
        echo '<p>PHP Memory Limit: ' . ini_get('memory_limit') . '</p>';
        
        // Display PHP max upload size
        echo '<p>PHP Max Upload Size: ' . ini_get('upload_max_filesize') . '</p>';
        
        // Display PHP post max size
        echo '<p>PHP Post Max Size: ' . ini_get('post_max_size') . '</p>';
        
        // Display last 5 entries in the stories table
        $last_stories = $wpdb->get_results("SELECT * FROM $stories_table ORDER BY id DESC LIMIT 5");
        echo '<h4>Last 5 Story Submissions:</h4>';
        foreach ($last_stories as $story) {
            echo '<p>ID: ' . $story->id . ', Campaign ID: ' . $story->campaign_id . ', Media URL: ' . (empty($story->media_url) ? 'None' : $story->media_url) . ', AI Moderation Status: ' . $story->ai_moderation_status . '</p>';
        }
        
        echo '</div>';
    }

    public function add_debug_log_viewer() {
        echo '<div class="debug-log-viewer" style="background-color: #f0f0f0; padding: 15px; margin-top: 20px; border: 1px solid #ccc;">';
        echo '<h3>Debug Log Viewer</h3>';
        
        $log_file = WP_CONTENT_DIR . '/debug.log';
        if (file_exists($log_file)) {
            $log_content = file_get_contents($log_file);
            echo '<textarea style="width: 100%; height: 300px;">' . esc_textarea($log_content) . '</textarea>';
        } else {
            echo '<p>No debug log file found.</p>';
        }
        
        echo '</div>';
    }
	
	public function populate_custom_url_data() {
    check_ajax_referer('sodabag-admin-nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'soda_campaigns';

    // Check if the column exists
    $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'custom_submission_url'");

    if (empty($column_exists)) {
        // Add the column if it doesn't exist
        $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN custom_submission_url VARCHAR(255)");
    }

    // Add a demo record
    $demo_campaign = $wpdb->get_row("SELECT id FROM {$table_name} WHERE name = 'Demo Campaign'");

    if ($demo_campaign) {
        $wpdb->update(
            $table_name,
            array('custom_submission_url' => 'https://example.com/custom-submission'),
            array('id' => $demo_campaign->id)
        );
    } else {
        $wpdb->insert(
            $table_name,
            array(
                'name' => 'Demo Campaign',
                'description' => 'This is a demo campaign with a custom submission URL.',
                'media_type' => 'both',
                'custom_submission_url' => 'https://example.com/custom-submission',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            )
        );
    }

    wp_send_json_success(array('message' => 'Custom URL column added and demo data populated successfully.'));
}

    private function get_campaigns() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'soda_campaigns';
        return $wpdb->get_results("SELECT id, name FROM $table_name");
    }

	
	public function populate_custom_story_url_data() {
    check_ajax_referer('sodabag-admin-nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'soda_campaigns';

    // Check if the column exists
    $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'custom_story_url'");

    if (empty($column_exists)) {
        // Add the column if it doesn't exist
        $result = $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN custom_story_url VARCHAR(255)");
        
        if ($result === false) {
            wp_send_json_error(array('message' => 'Failed to add custom_story_url column.'));
            return;
        }
        
        // Insert a demo record
        $result = $wpdb->insert(
            $table_name,
            array(
                'name' => 'Demo Campaign',
                'description' => 'This is a demo campaign with a custom story URL.',
                'media_type' => 'both',
                'custom_story_url' => 'https://example.com/view-story',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            )
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => 'Failed to insert demo record.'));
            return;
        }
        
        wp_send_json_success(array('message' => 'Custom Story URL column added and demo data inserted successfully.'));
    } else {
        wp_send_json_success(array('message' => 'Custom Story URL column already exists. No changes were made.'));
    }
}
	
    private function get_campaign_analytics($campaign_id, $start_date = null, $end_date = null) {
        global $wpdb;
        $analytics_table = $wpdb->prefix . 'soda_analytics';
        $stories_table = $wpdb->prefix . 'soda_stories';

        $date_condition = '';
        if ($start_date && $end_date) {
            $date_condition = $wpdb->prepare(" AND created_at BETWEEN %s AND %s", $start_date, $end_date);
        }

        $total_submissions = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $stories_table WHERE campaign_id = %d" . $date_condition, $campaign_id));
        $total_shares = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $analytics_table WHERE campaign_id = %d AND event_type = 'share'" . $date_condition, $campaign_id));
        $qr_scans = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $analytics_table WHERE campaign_id = %d AND event_type = 'qr_scan'" . $date_condition, $campaign_id));

        $share_by_platform = $wpdb->get_results($wpdb->prepare("
            SELECT event_data, COUNT(*) as count
            FROM $analytics_table
            WHERE campaign_id = %d AND event_type = 'share'" . $date_condition . "
            GROUP BY event_data
        ", $campaign_id), OBJECT_K);

        return array(
            'total_submissions' => $total_submissions,
            'total_shares' => $total_shares,
            'qr_scans' => $qr_scans,
            'share_by_platform' => $share_by_platform
        );
    }
}