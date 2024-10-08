<?php

class SodaBag_Public {
    private $plugin_name;

    public function __construct($plugin_name) {
        $this->plugin_name = $plugin_name;
    }

    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, SODABAG_PLUGIN_URL . 'assets/css/sodabag-styles.css', array(), null, 'all');
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css', array(), '5.15.3');
    }

    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, SODABAG_PLUGIN_URL . 'assets/js/sodabag-scripts.js', array('jquery'), null, false);
        wp_localize_script($this->plugin_name, 'sodabag_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sodabag-public-nonce'),
            'campaign_edit_url' => home_url('/sodabag-campaign-details/'),
            'campaign_stories_url' => home_url('/sodabag-campaign-stories/'),
            'dashboard_url' => home_url('/business-owner-dashboard/')
        ));

        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
    }

    public function register_shortcodes() {
        add_shortcode('sodabag_dashboard', array($this, 'render_dashboard'));
        add_shortcode('sodabag_login_register', array($this, 'render_login_register'));
        add_shortcode('sodabag_submission', array($this, 'render_submission_form'));
        add_shortcode('sodabag_embed', array($this, 'render_embed'));
        add_shortcode('sodabag_campaign_details', array($this, 'render_campaign_details'));
        add_shortcode('sodabag_campaign_stories', array($this, 'render_campaign_stories'));
        add_shortcode('sodabag_analytics', array($this, 'render_analytics'));
        add_shortcode('sodabag_integrations', array($this, 'render_integrations'));
    }

   public function register_rest_routes() {
    register_rest_route('sodabag/v1', '/stories', array(
        'methods' => 'GET',
        'callback' => array($this, 'get_stories_api'),
        'permission_callback' => '__return_true'
    ));

    register_rest_route('sodabag/v1', '/submit-story', array(
        'methods' => 'POST',
        'callback' => array($this, 'handle_story_submission_api'),
        'permission_callback' => '__return_true'
    ));
}

    public function get_stories_api($request) {
        $campaign_id = $request->get_param('campaign_id');
        $limit = $request->get_param('limit') ? intval($request->get_param('limit')) : 5;
        
        $stories = $this->get_stories($campaign_id, 'approved', $limit, 0, 'newest');
        
        $stories_data = array_map(function($story) {
            return array(
                'id' => $story->id,
                'content' => $story->content,
                'media_url' => $story->media_url,
                'created_at' => $story->created_at,
                'submitter_name' => $story->submitter_name,
                'likes' => $story->likes
            );
        }, $stories);
        
        return new WP_REST_Response($stories_data, 200);
    }

    public function submit_story_api($request) {
        $campaign_id = $request->get_param('campaign_id');
        $content = sanitize_textarea_field($request->get_param('content'));
        $submitter_name = sanitize_text_field($request->get_param('submitter_name'));
        $submitter_email = sanitize_email($request->get_param('submitter_email'));
        $custom_fields = $this->sanitize_custom_fields($request->get_param('custom_fields'));

        $options = get_option($this->plugin_name);
        $status = isset($options['default_story_status']) ? $options['default_story_status'] : 'pending';

        $story_id = $this->create_story($campaign_id, $content, '', $status, $custom_fields, $submitter_name, $submitter_email);

        if ($story_id) {
            return new WP_REST_Response(array('message' => 'Story submitted successfully.', 'story_id' => $story_id), 200);
        } else {
            return new WP_REST_Response(array('message' => 'Failed to submit story.'), 400);
        }
    }

    public function render_dashboard() {
        if (!is_user_logged_in() || !current_user_can('business_owner')) {
            return 'You must be logged in as a business owner to view this page.';
        }

        $user_id = get_current_user_id();
        $campaigns = $this->get_campaigns($user_id);
        $user_stats = $this->get_user_stats($user_id);

        ob_start();
        include SODABAG_PLUGIN_DIR . 'templates/dashboard.php';
        return ob_get_clean();
    }

    public function render_login_register() {
        if (is_user_logged_in()) {
            return 'You are already logged in.';
        }

        ob_start();
        include SODABAG_PLUGIN_DIR . 'templates/login-register.php';
        return ob_get_clean();
    }

    public function render_submission_form() {
        $campaign_id = isset($_GET['campaign']) ? intval($_GET['campaign']) : 0;
        $campaign = $this->get_campaign($campaign_id);

        if (!$campaign) {
            return '<p>Invalid campaign.</p>';
        }

        ob_start();
        include SODABAG_PLUGIN_DIR . 'templates/submission-form.php';
        return ob_get_clean();
    }

    public function render_embed($atts) {
        $atts = shortcode_atts(array(
            'campaign_id' => 0,
            'limit' => 5,
            'layout' => 'grid',
            'show_media' => true,
            'show_date' => true
        ), $atts, 'sodabag_embed');

        $campaign_id = intval($atts['campaign_id']);
        $stories = $this->get_stories($campaign_id, 'approved', $atts['limit'], 0, 'newest');
        
        ob_start();
        include SODABAG_PLUGIN_DIR . 'templates/story-display.php';
        return ob_get_clean();
    }

    public function render_campaign_details() {
        if (!is_user_logged_in() || !current_user_can('business_owner')) {
            return 'You must be logged in as a business owner to view this page.';
        }

        $campaign_id = isset($_GET['campaign_id']) ? intval($_GET['campaign_id']) : 0;
        $campaign = $this->get_campaign($campaign_id);

        if (!$campaign) {
            return '<p>Invalid campaign.</p>';
        }

        ob_start();
        include SODABAG_PLUGIN_DIR . 'templates/campaign-details.php';
        return ob_get_clean();
    }

    public function render_campaign_stories() {
        if (!is_user_logged_in() || !current_user_can('business_owner')) {
            return 'You must be logged in as a business owner to view this page.';
        }

        $campaign_id = isset($_GET['campaign_id']) ? intval($_GET['campaign_id']) : 0;
        $campaign = $this->get_campaign($campaign_id);

        if (!$campaign) {
            return '<p>Invalid campaign.</p>';
        }

        $stories = $this->get_stories($campaign_id);

        ob_start();
        include SODABAG_PLUGIN_DIR . 'templates/campaign-stories.php';
        return ob_get_clean();
    }

    public function render_analytics() {
        if (!is_user_logged_in() || !current_user_can('business_owner')) {
            return 'You must be logged in as a business owner to view this page.';
        }
		
		$user_id = get_current_user_id();
        $campaigns = $this->get_campaigns($user_id);

        $campaign_id = isset($_GET['campaign_id']) ? intval($_GET['campaign_id']) : 0;
        $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : null;
        $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : null;

        if ($campaign_id) {
            $analytics = $this->get_campaign_analytics($campaign_id, $start_date, $end_date);
        }

        ob_start();
        include SODABAG_PLUGIN_DIR . 'templates/analytics.php';
        return ob_get_clean();
    }

    public function render_integrations() {
        if (!is_user_logged_in() || !current_user_can('business_owner')) {
            return 'You must be logged in as a business owner to view this page.';
        }

        ob_start();
        include SODABAG_PLUGIN_DIR . 'templates/integrations.php';
        return ob_get_clean();
    }

    public function handle_story_submission() {
    // Check if this is an AJAX request or a direct API call
    $is_ajax = wp_doing_ajax();

    if ($is_ajax) {
        check_ajax_referer('sodabag_submit_story', 'sodabag_story_nonce');
    } else {
        // For API calls, we'll skip the nonce check but implement other security measures
        // Add your API security checks here (e.g., API key validation)
    }

    $campaign_id = intval($_POST['campaign_id']);
    $content = sanitize_textarea_field($_POST['content']);
    $submitter_name = sanitize_text_field($_POST['submitter_name']);
    $submitter_email = sanitize_email($_POST['submitter_email']);
    $custom_fields = isset($_POST['custom_fields']) ? $this->sanitize_custom_fields($_POST['custom_fields']) : array();

    // Generate a unique key for this submission
    $submission_key = md5($campaign_id . $content . $submitter_email . time());

    // Check if this submission has already been processed recently
    if (get_transient('sodabag_submission_' . $submission_key)) {
        $response = array(
            'success' => false,
            'data' => array('message' => 'This story has already been submitted. Please wait before submitting again.')
        );
        return $is_ajax ? wp_send_json_error($response) : $response;
    }

    // Set a transient to prevent duplicate submissions
    set_transient('sodabag_submission_' . $submission_key, true, 60); // 60 seconds cooldown

    $campaign = $this->get_campaign($campaign_id);
    if ($campaign->require_terms_acceptance && !isset($_POST['terms_acceptance'])) {
        $response = array(
            'success' => false,
            'data' => array('message' => 'You must accept the terms and conditions to submit your story.')
        );
        return $is_ajax ? wp_send_json_error($response) : $response;
    }

    $status = 'pending';
    $ai_moderation_status = null;

    if ($campaign->ai_moderation_enabled) {
        $ai_moderation_status = $this->moderate_story_with_llm($content, $campaign->selected_llm);
        if ($ai_moderation_status === 'pass') {
            $status = 'approved';
        }
    }

    $media_url = '';
    if (!empty($_FILES['media']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($_FILES['media'], $upload_overrides);

        if ($movefile && !isset($movefile['error'])) {
            $file_type = wp_check_filetype($movefile['file']);
            if (strpos($file_type['type'], 'video') !== false) {
                if (!$this->validate_video_length($movefile['file'])) {
                    $response = array(
                        'success' => false,
                        'data' => array('message' => 'Video duration must be 10 seconds or less.')
                    );
                    return $is_ajax ? wp_send_json_error($response) : $response;
                }
            }
            $media_url = $movefile['url'];
        } else {
            $error_message = isset($movefile['error']) ? $movefile['error'] : 'Unknown error during file upload.';
            $response = array(
                'success' => false,
                'data' => array('message' => 'Failed to upload file: ' . $error_message)
            );
            return $is_ajax ? wp_send_json_error($response) : $response;
        }
    }

    $story_id = $this->create_story($campaign_id, $content, $media_url, $status, $custom_fields, $submitter_name, $submitter_email, $ai_moderation_status);

    if ($story_id) {
        $response = array(
            'success' => true,
            'data' => array(
                'message' => 'Story submitted successfully.',
                'story_id' => $story_id,
                'show_share_modal' => true
            )
        );
    } else {
        global $wpdb;
        $error_message = $wpdb->last_error ? $wpdb->last_error : 'Unknown database error.';
        error_log('SodaBag Story Submission Error: ' . $error_message);
        $response = array(
            'success' => false,
            'data' => array('message' => 'Failed to submit story. Database error: ' . $error_message)
        );
    }

    // Remove the transient after successful submission
    delete_transient('sodabag_submission_' . $submission_key);

    return $is_ajax ? wp_send_json($response) : $response;
}

public function handle_story_submission_api($request) {
    // Handle CORS
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Access-Control-Allow-Headers: Content-Type");

    // Process the submission
    $result = $this->handle_story_submission();

    if ($result['success']) {
        return new WP_REST_Response($result, 200);
    } else {
        return new WP_REST_Response($result, 400);
    }
}
    public function create_campaign() {
        check_ajax_referer('sodabag-public-nonce', 'nonce');

        if (!current_user_can('business_owner')) {
            wp_send_json_error(array('message' => 'You do not have permission to create campaigns.'));
        }

        $user_id = get_current_user_id();
        $name = sanitize_text_field($_POST['name']);
        $description = sanitize_textarea_field($_POST['description']);
        $media_type = sanitize_text_field($_POST['media_type']);

        $existing_campaign = $this->get_campaign_by_name($user_id, $name);
        if ($existing_campaign) {
            wp_send_json_error(array('message' => 'A campaign with this name already exists.'));
            return;
        }

        $transient_key = 'sodabag_create_campaign_' . $user_id;
        if (get_transient($transient_key)) {
            wp_send_json_error(array('message' => 'Please wait a moment before creating another campaign.'));
            return;
        }
        set_transient($transient_key, true, 5);

        $campaign_id = $this->insert_campaign($user_id, $name, $description, $media_type);

        if ($campaign_id) {
            wp_send_json_success(array('message' => 'Campaign created successfully.', 'campaign_id' => $campaign_id));
        } else {
            wp_send_json_error(array('message' => 'Failed to create campaign.'));
        }
    }

    public function get_campaign_details() {
        check_ajax_referer('sodabag-public-nonce', 'nonce');

        $campaign_id = isset($_POST['campaign_id']) ? intval($_POST['campaign_id']) : 0;

        if (!$campaign_id) {
            wp_send_json_error(array('message' => 'Invalid campaign ID'));
            return;
        }

        $campaign = $this->get_campaign($campaign_id);

        if (!$campaign) {
            wp_send_json_error(array('message' => 'Campaign not found'));
            return;
        }

        wp_send_json_success(array('campaign' => $campaign));
    }

    public function edit_campaign() {
    check_ajax_referer('sodabag-public-nonce', 'nonce');

    if (!current_user_can('business_owner')) {
        wp_send_json_error(array('message' => 'You do not have permission to edit campaigns.'));
        return;
    }

    $campaign_id = intval($_POST['campaign_id']);
    $name = sanitize_text_field($_POST['name']);
    $description = sanitize_textarea_field($_POST['description']);
    $media_type = sanitize_text_field($_POST['media_type']);
    
    $custom_fields = array();
    if (isset($_POST['custom_fields']) && is_array($_POST['custom_fields'])) {
        foreach ($_POST['custom_fields'] as $field) {
            if (!empty($field['name']) && !empty($field['type'])) {
                $custom_fields[] = array(
                    'name' => sanitize_text_field($field['name']),
                    'type' => sanitize_text_field($field['type'])
                );
            }
        }
    }

    $primary_color = sanitize_hex_color($_POST['primary_color']);
    $secondary_color = sanitize_hex_color($_POST['secondary_color']);
    $require_terms_acceptance = isset($_POST['require_terms_acceptance']) ? 1 : 0;
    $terms_acceptance_text = sanitize_textarea_field($_POST['terms_acceptance_text']);

    $ai_moderation_enabled = isset($_POST['ai_moderation_enabled']) ? 1 : 0;
    $selected_llm = sanitize_text_field($_POST['selected_llm']);

    $existing_campaign = $this->get_campaign($campaign_id);
    $logo_url = $existing_campaign->logo_url;

    if (!empty($_FILES['logo']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($_FILES['logo'], $upload_overrides);

        if ($movefile && !isset($movefile['error'])) {
            $logo_url = $movefile['url'];
        } else {
            wp_send_json_error(array('message' => 'Error uploading logo: ' . $movefile['error']));
            return;
        }
    }

    $custom_text = sanitize_textarea_field($_POST['custom_text']);
    $sharing_text = sanitize_textarea_field($_POST['sharing_text']);
    $sharing_hashtags = sanitize_text_field($_POST['sharing_hashtags']);
    $sharing_url = esc_url_raw($_POST['sharing_url']);
    $email_subject = sanitize_text_field($_POST['email_subject']);
    $email_body = sanitize_textarea_field($_POST['email_body']);
    $custom_submission_url = esc_url_raw($_POST['custom_submission_url']);
    $custom_story_url = esc_url_raw($_POST['custom_story_url']);

    $result = $this->update_campaign($campaign_id, $name, $description, $media_type, $custom_fields, $logo_url, $custom_text, $sharing_text, $sharing_hashtags, $sharing_url, $email_subject, $email_body, $primary_color, $secondary_color, $require_terms_acceptance, $terms_acceptance_text, $ai_moderation_enabled, $selected_llm, $custom_submission_url, $custom_story_url);

    if ($result !== false) {
        wp_send_json_success(array(
            'message' => 'Campaign updated successfully.',
            'logo_url' => $logo_url,
            'logo_changed' => $logo_url !== $existing_campaign->logo_url,
            'custom_fields' => $custom_fields
        ));
    } else {
        wp_send_json_error(array('message' => 'Failed to update campaign. Please try again.'));
    }
}

    public function delete_campaign() {
        check_ajax_referer('sodabag-public-nonce', 'nonce');

        if (!current_user_can('business_owner')) {
            wp_send_json_error(array('message' => 'You do not have permission to delete campaigns.'));
        }

        $campaign_id = intval($_POST['campaign_id']);

        $result = $this->remove_campaign($campaign_id);

        if ($result) {
            wp_send_json_success(array('message' => 'Campaign deleted successfully.'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete campaign.'));
        }
    }

    public function moderate_story() {
        check_ajax_referer('sodabag-public-nonce', 'nonce');

        if (!current_user_can('business_owner')) {
            wp_send_json_error(array('message' => 'You do not have permission to moderate stories.'));
        }

        $story_id = intval($_POST['story_id']);
        $new_status = sanitize_text_field($_POST['new_status']);

        $result = $this->update_story_status($story_id, $new_status);

        if ($result !== false) {
            wp_send_json_success(array('message' => 'Story status updated successfully.'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update story status.'));
        }
    }

   public function delete_story() {
    check_ajax_referer('sodabag-public-nonce', 'nonce');

    if (!current_user_can('business_owner')) {
        wp_send_json_error(array('message' => 'You do not have permission to delete stories.'));
    }

    $story_id = intval($_POST['story_id']);

    $result = $this->remove_story($story_id);

    if ($result) {
        wp_send_json_success(array('message' => 'Story deleted successfully.'));
    } else {
        wp_send_json_error(array('message' => 'Failed to delete story.'));
    }
}

private function remove_story($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'soda_stories';
    return $wpdb->delete($table_name, array('id' => $id));
}
    public function handle_login() {
        check_ajax_referer('sodabag-public-nonce', 'nonce');

        $username = sanitize_user($_POST['username']);
        $password = $_POST['password'];

        $user = wp_signon(array(
            'user_login' => $username,
            'user_password' => $password,
            'remember' => true
        ));

        if (is_wp_error($user)) {
            wp_send_json_error(array('message' => 'Invalid username or password.'));
        } else {
            wp_send_json_success(array('message' => 'Login successful.', 'redirect' => home_url('/business-owner-dashboard/')));
        }
    }

    public function handle_registration() {
        check_ajax_referer('sodabag-public-nonce', 'nonce');

        $username = sanitize_user($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $business_name = sanitize_text_field($_POST['business_name']);

        if (username_exists($username)) {
            wp_send_json_error(array('message' => 'Username already exists.'));
            return;
        }

        if (email_exists($email)) {
            wp_send_json_error(array('message' => 'Email already exists.'));
            return;
        }

        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
        } else {
            $user = new WP_User($user_id);
            $user->set_role('business_owner');
            update_user_meta($user_id, 'business_name', $business_name);

            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);

            wp_send_json_success(array('message' => 'Registration successful. Redirecting...', 'redirect' => home_url('/business-owner-dashboard/')));
        }
    }

    public function share_story() {
        check_ajax_referer('sodabag-public-nonce', 'nonce');

        $story_id = intval($_POST['story_id']);
        $platform = sanitize_text_field($_POST['platform']);

        $story = $this->get_story($story_id);
        $campaign = $this->get_campaign($story->campaign_id);

        $share_data = array(
            'url' => !empty($campaign->sharing_url) ? $campaign->sharing_url : get_permalink($story_id),
            'text' => !empty($campaign->sharing_text) ? $campaign->sharing_text : wp_trim_words($story->content, 30),
            'hashtags' => !empty($campaign->sharing_hashtags) ? $campaign->sharing_hashtags : '',
            'image' => $story->media_url
        );

        $this->record_share($story_id, $platform);

        wp_send_json_success($share_data);
    }

    public function track_share() {
        check_ajax_referer('sodabag-public-nonce', 'nonce');

        $story_id = intval($_POST['story_id']);
        $platform = sanitize_text_field($_POST['platform']);

        $this->record_share($story_id, $platform);

        wp_send_json_success();
    }

    public function share_story_email() {
        check_ajax_referer('sodabag-public-nonce', 'nonce');

        $story_id = intval($_POST['story_id']);
        $recipient_email = sanitize_email($_POST['recipient_email']);

        $story = $this->get_story($story_id);
        $campaign = $this->get_campaign($story->campaign_id);

        $subject = !empty($campaign->email_subject) ? $campaign->email_subject : sprintf('Check out this story from %s', $campaign->name);
        $body = !empty($campaign->email_body) ? $campaign->email_body : $story->content;
        $body .= "\n\n" . get_permalink($story_id);

        $result = wp_mail($recipient_email, $subject, $body);

        if ($result) {
            $this->record_share($story_id, 'email');
            wp_send_json_success(array('message' => 'Email sent successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to send email'));
        }
    }

    public function filter_sort_stories() {
        check_ajax_referer('sodabag-public-nonce', 'nonce');

        $campaign_id = intval($_POST['campaign_id']);
        $sort_by = sanitize_text_field($_POST['sort_by']);
        $filter_by = sanitize_text_field($_POST['filter_by']);

        $stories = $this->get_filtered_sorted_stories($campaign_id, $sort_by, $filter_by);

        ob_start();
        include SODABAG_PLUGIN_DIR . 'templates/story-display.php';
        $html = ob_get_clean();

        wp_send_json_success(array('html' => $html));
    }

    public function handle_qr_scan() {
    if (isset($_GET['campaign']) && isset($_GET['qr'])) {
        $campaign_id = intval($_GET['campaign']);
        $campaign = $this->get_campaign($campaign_id);
        
        if (!$campaign) {
            wp_die('Invalid campaign.');
        }

        $this->record_analytics_event($campaign_id, 'qr_scan');
        
        // Always redirect to the QR redirect page first
        $redirect_url = add_query_arg(
            array(
                'campaign' => $campaign_id,
                'destination' => !empty($campaign->custom_submission_url) ? 'custom' : 'default'
            ),
            home_url('/sodabag-qr-redirect/')
        );
        
        wp_redirect($redirect_url);
        exit;
    }
}

	public function handle_qr_redirect() {
    if (is_page('sodabag-qr-redirect')) {
        $campaign_id = isset($_GET['campaign']) ? intval($_GET['campaign']) : 0;
        $destination = isset($_GET['destination']) ? $_GET['destination'] : 'default';

        if (!$campaign_id) {
            wp_die('Invalid campaign.');
        }

        $campaign = $this->get_campaign($campaign_id);

        if (!$campaign) {
            wp_die('Campaign not found.');
        }

        if ($destination === 'custom' && !empty($campaign->custom_submission_url)) {
            wp_redirect($campaign->custom_submission_url);
        } else {
            wp_redirect(add_query_arg('campaign', $campaign_id, home_url('/sodabag-submission/')));
        }
        exit;
    }
}
    
	public function share_to_hubspot() {
        check_ajax_referer('sodabag-public-nonce', 'nonce');

        if (!current_user_can('business_owner')) {
            wp_send_json_error(array('message' => 'You do not have permission to share stories.'));
        }

        $story_id = intval($_POST['story_id']);
        $channels = isset($_POST['channels']) ? $_POST['channels'] : array();

        $story = $this->get_story($story_id);
        if (!$story) {
            wp_send_json_error(array('message' => 'Story not found.'));
        }

        $user_id = get_current_user_id();
        $hubspot_api_token = get_user_meta($user_id, 'hubspot_api_token', true);

        if (empty($hubspot_api_token)) {
            wp_send_json_error(array('message' => 'HubSpot API token not found.'));
        }

        $success_count = 0;
        $error_messages = array();
        $debug_info = array();

        foreach ($channels as $channel_id) {
            $is_draft = isset($_POST['draft_' . $channel_id]) ? true : false;
            $schedule = isset($_POST['schedule_' . $channel_id]) ? sanitize_text_field($_POST['schedule_' . $channel_id]) : null;

            $result = $this->create_hubspot_broadcast($hubspot_api_token, $channel_id, $story, $is_draft, $schedule);

            $debug_info[] = array(
                'channel_id' => $channel_id,
                'is_draft' => $is_draft,
                'schedule' => $schedule,
                'data_sent' => $result['data_sent'],
                'api_response' => $result['api_response']
            );

            if ($result['success']) {
                $success_count++;
            } else {
                $error_messages[] = $result['message'];
            }
        }

        if ($success_count > 0) {
            $message = sprintf('%d channel(s) shared successfully.', $success_count);
            if (!empty($error_messages)) {
                $message .= ' Errors: ' . implode(', ', $error_messages);
            }
            wp_send_json_success(array('message' => $message, 'debug_info' => $debug_info));
        } else {
            wp_send_json_error(array('message' => 'Failed to share to any channels. ' . implode(', ', $error_messages), 'debug_info' => $debug_info));
        }
    }

    public function save_llm_settings() {
        check_ajax_referer('sodabag-public-nonce', 'nonce');

        if (!current_user_can('business_owner')) {
            wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
        }

        $options = get_option('sodabag');
        if (!is_array($options)) {
            $options = array();
        }

        $options['anthropic_api_key'] = sanitize_text_field($_POST['anthropic_api_key']);
        $options['openai_api_key'] = sanitize_text_field($_POST['openai_api_key']);

        $updated = update_option('sodabag', $options);

        if ($updated) {
            wp_send_json_success(array('message' => 'LLM settings saved successfully.'));
        } else {
            $current_options = get_option('sodabag');
            if ($current_options === $options) {
                wp_send_json_success(array('message' => 'LLM settings are up to date.'));
            } else {
                wp_send_json_error(array('message' => 'Failed to save LLM settings. Please try again.'));
            }
        }
    }

    public function like_story() {
        check_ajax_referer('sodabag-public-nonce', 'nonce');

        $story_id = intval($_POST['story_id']);
        $session_key = 'liked_story_' . $story_id;

        if (!isset($_SESSION[$session_key])) {
            $result = $this->increment_story_likes($story_id);
            if ($result) {
                $_SESSION[$session_key] = true;
                $new_likes = $this->get_story_likes($story_id);
                wp_send_json_success(array('message' => 'Story liked successfully.', 'new_likes' => $new_likes));
            } else {
                wp_send_json_error(array('message' => 'Failed to like story.'));
            }
        } else {
            wp_send_json_error(array('message' => 'You have already liked this story.'));
        }
    }
	
	private function get_story_likes($story_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'soda_stories';
        return $wpdb->get_var($wpdb->prepare("SELECT likes FROM $table_name WHERE id = %d", $story_id));
    }

    private function sanitize_custom_fields($custom_fields) {
        $sanitized = array();
        foreach ($custom_fields as $key => $value) {
            $sanitized[sanitize_key($key)] = sanitize_text_field($value);
        }
        return $sanitized;
    }

    private function get_campaigns($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'soda_campaigns';
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC", $user_id));
    }

    private function get_user_stats($user_id) {
        global $wpdb;
        $campaigns_table = $wpdb->prefix . 'soda_campaigns';
        $stories_table = $wpdb->prefix . 'soda_stories';

        $total_campaigns = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $campaigns_table WHERE user_id = %d", $user_id));
        $total_stories = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $stories_table WHERE campaign_id IN (SELECT id FROM $campaigns_table WHERE user_id = %d)", $user_id));
        $pending_stories = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $stories_table WHERE campaign_id IN (SELECT id FROM $campaigns_table WHERE user_id = %d) AND status = 'pending'", $user_id));

        return array(
            'total_campaigns' => $total_campaigns,
            'total_stories' => $total_stories,
            'pending_stories' => $pending_stories
        );
    }

    private function get_stories($campaign_id, $status = null, $limit = 10, $offset = 0, $sort_by = 'newest') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'soda_stories';
        $query = "SELECT * FROM $table_name WHERE campaign_id = %d";
        $params = array($campaign_id);

        if ($status) {
            $query .= " AND status = %s";
            $params[] = $status;
        }

        switch ($sort_by) {
            case 'oldest':
                $query .= " ORDER BY created_at ASC";
                break;
            case 'most_liked':
                $query .= " ORDER BY likes DESC";
                break;
            case 'most_shared':
                $query .= " ORDER BY shares DESC";
                break;
            case 'newest':
            default:
                $query .= " ORDER BY created_at DESC";
                break;
        }

        $query .= " LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;

        return $wpdb->get_results($wpdb->prepare($query, $params));
    }

    private function create_story($campaign_id, $content, $media_url, $status, $custom_fields = array(), $submitter_name = '', $submitter_email = '', $ai_moderation_status = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'soda_stories';

        $result = $wpdb->insert(
            $table_name,
            array(
                'campaign_id' => $campaign_id,
                'user_id' => get_current_user_id(),
                'content' => $content,
                'media_url' => $media_url,
                'custom_fields' => maybe_serialize($custom_fields),
                'status' => $status,
                'submitter_name' => $submitter_name,
                'submitter_email' => $submitter_email,
                'ai_moderation_status' => $ai_moderation_status,
                'created_at' => current_time('mysql'),
                'likes' => 0,
                'shares' => 0
            )
        );

        if ($result === false) {
            error_log('Database insertion error: ' . $wpdb->last_error);
        }

        return $result ? $wpdb->insert_id : false;
    }

    private function insert_campaign($user_id, $name, $description, $media_type) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'soda_campaigns';
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'name' => $name,
                'description' => $description,
                'media_type' => $media_type,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            )
        );

        return $result ? $wpdb->insert_id : false;
    }

    private function update_campaign($id, $name, $description, $media_type, $custom_fields, $logo_url, $custom_text, $sharing_text, $sharing_hashtags, $sharing_url, $email_subject, $email_body, $primary_color, $secondary_color, $require_terms_acceptance, $terms_acceptance_text, $ai_moderation_enabled, $selected_llm, $custom_submission_url, $custom_story_url) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'soda_campaigns';
    return $wpdb->update(
        $table_name,
        array(
            'name' => $name,
            'description' => $description,
            'media_type' => $media_type,
            'custom_fields' => maybe_serialize($custom_fields),
            'logo_url' => $logo_url,
            'custom_text' => $custom_text,
            'sharing_text' => $sharing_text,
            'sharing_hashtags' => $sharing_hashtags,
            'sharing_url' => $sharing_url,
            'email_subject' => $email_subject,
            'email_body' => $email_body,
            'primary_color' => $primary_color,
            'secondary_color' => $secondary_color,
            'require_terms_acceptance' => $require_terms_acceptance,
            'terms_acceptance_text' => $terms_acceptance_text,
            'ai_moderation_enabled' => $ai_moderation_enabled,
            'selected_llm' => $selected_llm,
            'custom_submission_url' => $custom_submission_url,
            'custom_story_url' => $custom_story_url,
            'updated_at' => current_time('mysql')
        ),
        array('id' => $id)
    );
}

    private function remove_campaign($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'soda_campaigns';
        return $wpdb->delete($table_name, array('id' => $id));
    }

    private function update_story_status($id, $status) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'soda_stories';
        return $wpdb->update(
            $table_name,
            array('status' => $status),
            array('id' => $id)
        );
    }

    private function get_campaign($campaign_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'soda_campaigns';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $campaign_id));
    }

    private function get_campaign_by_name($user_id, $name) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'soda_campaigns';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d AND name = %s",
            $user_id,
            $name
        ));
    }

    private function get_story($story_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'soda_stories';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $story_id));
    }

    private function record_share($story_id, $platform) {
        global $wpdb;
        $stories_table = $wpdb->prefix . 'soda_stories';
        $analytics_table = $wpdb->prefix . 'soda_analytics';

        $wpdb->query($wpdb->prepare("UPDATE $stories_table SET shares = shares + 1 WHERE id = %d", $story_id));

        $story = $this->get_story($story_id);
        $this->record_analytics_event($story->campaign_id, 'share', $platform, $story_id);
    }

    private function record_analytics_event($campaign_id, $event_type, $event_data = null, $story_id = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'soda_analytics';
        
        $wpdb->insert(
            $table_name,
            array(
                'campaign_id' => $campaign_id,
                'story_id' => $story_id,
                'event_type' => $event_type,
                'event_data' => $event_data,
                'created_at' => current_time('mysql')
            )
        );
    }

    private function create_hubspot_broadcast($api_token, $channel_id, $story, $is_draft, $schedule) {
        $url = 'https://api.hubapi.com/broadcast/v1/broadcasts';

        $body = array(
            'channelGuid' => $channel_id,
            'content' => array(
                'body' => $story->content
            )
        );

        if ($is_draft) {
            $body['status'] = 'DRAFT';
        } elseif ($schedule) {
            $body['triggerAt'] = strtotime($schedule) * 1000; // Convert to milliseconds
        }

        if (!empty($story->media_url)) {
            $file_type = wp_check_filetype($story->media_url);
            if (strpos($file_type['type'], 'image') !== false) {
                $body['content']['photoUrl'] = $story->media_url;
            }
        }

        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_token,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($body)
        );

        $response = wp_remote_post($url, $args);

        $result = array(
            'success' => false,
            'message' => '',
            'data_sent' => $body,
            'api_response' => ''
        );

        if (is_wp_error($response)) {
            $result['message'] = $response->get_error_message();
        } else {
            $result['api_response'] = wp_remote_retrieve_body($response);
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = json_decode($result['api_response'], true);

            if ($response_code === 201) {
                $result['success'] = true;
                $result['message'] = 'Broadcast created successfully';
            } else {
                $result['message'] = isset($response_body['message']) ? $response_body['message'] : 'Unknown error occurred.';
            }
        }

        return $result;
    }

    private function moderate_story_with_llm($story_content, $llm_type) {
        $api_key = '';
        $prompt = "You are a moderator on a popular social website. Please review the following social post content for any vulgar language, profanity, hate speech, sexual content, and any other possible content that would not be suitable for someone under 18. Here is the content:\n\n\"$story_content\"\n\nIf you determine there is any prohibited content within the post above, please respond with the text: <fail>\n\nIf the above post content is appropriate and does NOT contain any prohibited content, then respond with the text: <pass>\n\nImportant!: only include either the text <fail> or <pass> based on your analysis. Do not include any other explanations or other text!";

        if ($llm_type === 'claude') {
            $api_key = get_option($this->plugin_name)['anthropic_api_key'];
            $response = $this->call_anthropic_api($api_key, $prompt);
        } elseif ($llm_type === 'gpt-4') {
            $api_key = get_option($this->plugin_name)['openai_api_key'];
            $response = $this->call_openai_api($api_key, $prompt);
        } else {
            return 'error';
        }

        if (strpos($response, '<pass>') !== false) {
            return 'pass';
        } elseif (strpos($response, '<fail>') !== false) {
            return 'fail';
        } else {
            return 'error';
        }
    }

    private function call_anthropic_api($api_key, $prompt) {
        $url = 'https://api.anthropic.com/v1/completions';
        $headers = array(
            'Content-Type' => 'application/json',
            'X-API-Key' => $api_key
        );
        $body = json_encode(array(
            'model' => 'claude-3-5-sonnet',
            'prompt' => $prompt,
            'max_tokens_to_sample' => 1000,
            'temperature' => 0
        ));

        $response = wp_remote_post($url, array(
            'headers' => $headers,
            'body' => $body
        ));

        if (is_wp_error($response)) {
            return 'error';
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['completion'];
    }

    private function call_openai_api($api_key, $prompt) {
        $url = 'https://api.openai.com/v1/chat/completions';
        $headers = array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        );
        $body = json_encode(array(
            'model' => 'gpt-4',
            'messages' => array(
                array('role' => 'user', 'content' => $prompt)
            )
        ));

        $response = wp_remote_post($url, array(
            'headers' => $headers,
            'body' => $body
        ));

        if (is_wp_error($response)) {
            return 'error';
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['choices'][0]['message']['content'];
    }

    private function get_filtered_sorted_stories($campaign_id, $sort_by, $filter_by) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'soda_stories';

        $query = "SELECT * FROM $table_name WHERE campaign_id = %d";
        $params = array($campaign_id);

        if ($filter_by === 'with_media') {
            $query .= " AND media_url != ''";
        } elseif ($filter_by === 'text_only') {
            $query .= " AND media_url = ''";
        } elseif ($filter_by !== 'all') {
            $query .= " AND status = %s";
            $params[] = $filter_by;
        }

        switch ($sort_by) {
            case 'oldest':
                $query .= " ORDER BY created_at ASC";
                break;
            case 'most_liked':
                $query .= " ORDER BY likes DESC";
                break;
            case 'most_shared':
                $query .= " ORDER BY shares DESC";
                break;
            case 'newest':
            default:
                $query .= " ORDER BY created_at DESC";
                break;
        }

        return $wpdb->get_results($wpdb->prepare($query, $params));
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

        $submissions_over_time = $wpdb->get_results($wpdb->prepare("
            SELECT DATE(created_at) as date, COUNT(*) as count
            FROM $stories_table
            WHERE campaign_id = %d" . $date_condition . "
            GROUP BY DATE(created_at)
            ORDER BY DATE(created_at)
        ", $campaign_id), OBJECT_K);

        $total_views = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $analytics_table WHERE campaign_id = %d AND event_type = 'view'" . $date_condition, $campaign_id));
        $engagement_rate = $total_views > 0 ? ($total_submissions / $total_views) * 100 : 0;

        return array(
            'total_submissions' => $total_submissions,
            'total_shares' => $total_shares,
            'qr_scans' => $qr_scans,
            'share_by_platform' => $share_by_platform,
            'submissions_over_time' => $submissions_over_time,
            'engagement_rate' => round($engagement_rate, 2)
        );
    }

    private function increment_story_likes($story_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'soda_stories';
        return $wpdb->query($wpdb->prepare("UPDATE $table_name SET likes = likes + 1 WHERE id = %d", $story_id));
    }

    private function validate_video_length($file_path, $max_duration = 10) {
        if (!function_exists('getid3_init')) {
            require_once(ABSPATH . 'wp-includes/ID3/getid3.php');
        }
        $getID3 = new getID3;
        $file_info = $getID3->analyze($file_path);
        if (isset($file_info['playtime_seconds']) && $file_info['playtime_seconds'] > $max_duration) {
            return false;
        }
        return true;
    }

    private function format_user_name($full_name) {
        $names = explode(' ', $full_name);
        $first_name = strtoupper($names[0]);
        $last_initial = isset($names[1]) ? strtoupper(substr($names[1], 0, 1)) . '.' : '';
        return $first_name . ' ' . $last_initial;
    }

    private function get_story_excerpt($content, $length = 60) {
        $content = strip_tags($content);
        if (strlen($content) > $length) {
            return substr($content, 0, $length) . '...';
        } else {
            return $content;
        }
    }

    private function get_avatar_color($name) {
        $colors = ['#FFD700', '#32CD32', '#808080', '#FF69B4', '#FF8C00', '#1E90FF'];
        $index = crc32(strtolower($name)) % count($colors);
        return $colors[$index];
    }
}