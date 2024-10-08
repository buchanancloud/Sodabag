<?php
// Ensure this file is being included by a parent file
if (!defined('ABSPATH')) exit;

// Ensure user is logged in and has the correct role
if (!is_user_logged_in() || !current_user_can('business_owner')) {
    wp_die('You do not have permission to access this page.');
}

$user_id = get_current_user_id();
$hubspot_api_token = get_user_meta($user_id, 'hubspot_api_token', true);
$hubspot_portal_id = get_user_meta($user_id, 'hubspot_portal_id', true);
$hubspot_channels = get_user_meta($user_id, 'hubspot_channels', true);
$hubspot_enabled_channels = get_user_meta($user_id, 'hubspot_enabled_channels', true);

$options = get_option('sodabag');
$anthropic_api_key = isset($options['anthropic_api_key']) ? $options['anthropic_api_key'] : '';
$openai_api_key = isset($options['openai_api_key']) ? $options['openai_api_key'] : '';
?>

<div class="sodabag-container">
    <div class="sodabag-dashboard-menu">
        <a href="<?php echo home_url('/business-owner-dashboard/'); ?>" class="sodabag-menu-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="<?php echo home_url('/sodabag-integrations/'); ?>" class="sodabag-menu-item"><i class="fas fa-plug"></i> Integrations</a>
        <a href="<?php echo wp_logout_url(home_url()); ?>" class="sodabag-menu-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <h2>Integrations</h2>

    <div class="sodabag-integration-section">
        <h3>HubSpot Integration</h3>
        <?php if (empty($hubspot_api_token) || empty($hubspot_portal_id)) : ?>
            <form id="sodabag-hubspot-connect-form" class="sodabag-form">
                <input type="text" id="hubspot-api-token" name="api_token" placeholder="HubSpot API Token" required>
                <input type="text" id="hubspot-portal-id" name="portal_id" placeholder="HubSpot Portal ID" required>
                <button type="submit" class="sodabag-button">Connect HubSpot</button>
            </form>
        <?php else : ?>
            <p>HubSpot is connected.</p>
            <button id="sodabag-hubspot-refresh" class="sodabag-button">Refresh Channels</button>
            <div id="sodabag-hubspot-channels" class="sodabag-channels-grid">
                <?php
                if (!empty($hubspot_channels)) {
                    foreach ($hubspot_channels as $channel) {
                        $icon_class = '';
                        switch ($channel['type']) {
                            case 'Instagram':
                                $icon_class = 'fa-instagram';
                                break;
                            case 'Facebook':
                                $icon_class = 'fa-facebook';
                                break;
                            case 'Twitter':
                                $icon_class = 'fa-twitter';
                                break;
                        }
                        $is_enabled = isset($hubspot_enabled_channels[$channel['id']]);
                        ?>
                        <div class="sodabag-channel-card">
                            <i class="fab <?php echo esc_attr($icon_class); ?>"></i>
                            <h4><?php echo esc_html($channel['name']); ?></h4>
                            <p><a href="<?php echo esc_url($channel['profileUrl']); ?>" target="_blank">View Profile</a></p>
                            <label class="sodabag-toggle">
                                <input type="checkbox" class="sodabag-channel-toggle" data-channel-id="<?php echo esc_attr($channel['id']); ?>" <?php checked($is_enabled); ?>>
                                <span class="sodabag-toggle-slider"></span>
                            </label>
                        </div>
                        <?php
                    }
                } else {
                    echo '<p>No channels found. Click "Refresh Channels" to fetch the latest data.</p>';
                }
                ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="sodabag-integration-section">
        <h3>LLM Integrations</h3>
        <form id="sodabag-llm-integration-form" class="sodabag-form">
            <div class="sodabag-integration-item">
                <i class="fas fa-robot sodabag-llm-icon"></i>
                <label for="anthropic-api-key">Anthropic API Key:</label>
                <input type="text" id="anthropic-api-key" name="anthropic_api_key" value="<?php echo esc_attr($anthropic_api_key); ?>" placeholder="Enter Anthropic API Key">
            </div>
            <div class="sodabag-integration-item">
                <i class="fas fa-robot sodabag-llm-icon"></i>
                <label for="openai-api-key">OpenAI API Key:</label>
                <input type="text" id="openai-api-key" name="openai_api_key" value="<?php echo esc_attr($openai_api_key); ?>" placeholder="Enter OpenAI API Key">
            </div>
            <button type="submit" class="sodabag-button">Save LLM Settings</button>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // HubSpot connection form submission
    $('#sodabag-hubspot-connect-form').on('submit', function(e) {
        e.preventDefault();
        var apiToken = $('#hubspot-api-token').val();
        var portalId = $('#hubspot-portal-id').val();

        $.ajax({
            url: sodabag_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sodabag_connect_hubspot',
                nonce: sodabag_ajax.nonce,
                api_token: apiToken,
                portal_id: portalId
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    });

    // Refresh HubSpot channels
    $('#sodabag-hubspot-refresh').on('click', function() {
        $.ajax({
            url: sodabag_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sodabag_refresh_hubspot_channels',
                nonce: sodabag_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    });

    // Toggle HubSpot channel
    $('.sodabag-channel-toggle').on('change', function() {
        var channelId = $(this).data('channel-id');
        var enabled = $(this).is(':checked');

        $.ajax({
            url: sodabag_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sodabag_toggle_hubspot_channel',
                nonce: sodabag_ajax.nonce,
                channel_id: channelId,
                enabled: enabled
            },
            success: function(response) {
                if (response.success) {
                    console.log(response.data.message);
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        });
    });

    // LLM Integration form submission
    $('#sodabag-llm-integration-form').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        formData += '&action=sodabag_save_llm_settings&nonce=' + sodabag_ajax.nonce;

        $.ajax({
            url: sodabag_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    // Update the input fields with the saved values
                    $('#anthropic-api-key').val($('#anthropic-api-key').val());
                    $('#openai-api-key').val($('#openai-api-key').val());
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
                alert('An error occurred. Please try again.');
            }
        });
    });
});
</script>