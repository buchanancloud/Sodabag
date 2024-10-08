<?php

class SodaBag_HubSpot {
    private $plugin_name;

    public function __construct($plugin_name) {
        $this->plugin_name = $plugin_name;
    }

    public function init() {
        add_action('wp_ajax_sodabag_connect_hubspot', array($this, 'connect_hubspot'));
        add_action('wp_ajax_sodabag_refresh_hubspot_channels', array($this, 'refresh_hubspot_channels'));
        add_action('wp_ajax_sodabag_toggle_hubspot_channel', array($this, 'toggle_hubspot_channel'));
    }

    public function connect_hubspot() {
        check_ajax_referer('sodabag-public-nonce', 'nonce');

        if (!current_user_can('business_owner')) {
            wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
        }

        $api_token = sanitize_text_field($_POST['api_token']);
        $portal_id = intval($_POST['portal_id']);

        if (empty($api_token) || empty($portal_id)) {
            wp_send_json_error(array('message' => 'API Token and Portal ID are required.'));
        }

        $user_id = get_current_user_id();
        update_user_meta($user_id, 'hubspot_api_token', $api_token);
        update_user_meta($user_id, 'hubspot_portal_id', $portal_id);

        $channels = $this->fetch_hubspot_channels($api_token);

        if (is_wp_error($channels)) {
            wp_send_json_error(array('message' => $channels->get_error_message()));
        }

        update_user_meta($user_id, 'hubspot_channels', $channels);

        wp_send_json_success(array('message' => 'HubSpot connected successfully.', 'channels' => $channels));
    }

    public function refresh_hubspot_channels() {
        check_ajax_referer('sodabag-public-nonce', 'nonce');

        if (!current_user_can('business_owner')) {
            wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
        }

        $user_id = get_current_user_id();
        $api_token = get_user_meta($user_id, 'hubspot_api_token', true);

        if (empty($api_token)) {
            wp_send_json_error(array('message' => 'HubSpot is not connected.'));
        }

        $channels = $this->fetch_hubspot_channels($api_token);

        if (is_wp_error($channels)) {
            wp_send_json_error(array('message' => $channels->get_error_message()));
        }

        update_user_meta($user_id, 'hubspot_channels', $channels);

        wp_send_json_success(array('message' => 'HubSpot channels refreshed successfully.', 'channels' => $channels));
    }

    public function toggle_hubspot_channel() {
        check_ajax_referer('sodabag-public-nonce', 'nonce');

        if (!current_user_can('business_owner')) {
            wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
        }

        $user_id = get_current_user_id();
        $channel_id = sanitize_text_field($_POST['channel_id']);
        $enabled = boolval($_POST['enabled']);

        $enabled_channels = get_user_meta($user_id, 'hubspot_enabled_channels', true);
        if (!is_array($enabled_channels)) {
            $enabled_channels = array();
        }

        if ($enabled) {
            $enabled_channels[$channel_id] = true;
        } else {
            unset($enabled_channels[$channel_id]);
        }

        update_user_meta($user_id, 'hubspot_enabled_channels', $enabled_channels);

        wp_send_json_success(array('message' => 'Channel status updated successfully.'));
    }

    private function fetch_hubspot_channels($api_token) {
        $url = 'https://api.hubapi.com/broadcast/v1/channels/setting/publish/current';

        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_token,
                'Content-Type'  => 'application/json',
            ),
        );

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            return new WP_Error('hubspot_api_error', $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data)) {
            return new WP_Error('hubspot_api_error', 'No channels found or invalid response from HubSpot API.');
        }

        $filtered_channels = array();

        foreach ($data as $channel) {
            if ($channel['active'] == 1) {
                if ($channel['channelType'] === 'FacebookPage') {
                    $channel['channelType'] = 'Facebook';
                }

                if ($channel['channelType'] === 'Twitter' && $channel['profileUrl'] === 'https://twitter.com/null') {
                    continue;
                }

                if (in_array($channel['channelType'], ['Instagram', 'Facebook', 'Twitter'])) {
                    $filtered_channels[] = array(
                        'id' => $channel['channelId'],
                        'name' => $channel['name'],
                        'type' => $channel['channelType'],
                        'profileUrl' => $channel['profileUrl'],
                        'channelGuid' => $channel['channelGuid'],
                    );
                }
            }
        }

        return $filtered_channels;
    }
}