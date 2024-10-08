<?php
// Ensure this file is being included by a parent file
if (!defined('ABSPATH')) exit;

// Ensure $user_stats and $campaigns are set
if (!isset($user_stats) || !isset($campaigns)) {
    wp_die('Invalid data');
}
?>

<div class="sodabag-container">
    <div class="sodabag-dashboard-menu">
        <a href="<?php echo home_url('/business-owner-dashboard/'); ?>" class="sodabag-menu-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="<?php echo home_url('/sodabag-integrations/'); ?>" class="sodabag-menu-item"><i class="fas fa-plug"></i> Integrations</a>
        <a href="<?php echo wp_logout_url(home_url()); ?>" class="sodabag-menu-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="sodabag-dashboard-summary">
        <h2>Summary</h2>
        <div class="sodabag-summary-stats">
            <div class="sodabag-stat">
                <i class="fas fa-bullhorn"></i>
                <span class="sodabag-stat-label">Total Campaigns</span>
                <span class="sodabag-stat-number"><?php echo esc_html($user_stats['total_campaigns']); ?></span>
            </div>
            <div class="sodabag-stat">
                <i class="fas fa-comment-alt"></i>
                <span class="sodabag-stat-label">Total Stories</span>
                <span class="sodabag-stat-number"><?php echo esc_html($user_stats['total_stories']); ?></span>
            </div>
            <div class="sodabag-stat">
                <i class="fas fa-clock"></i>
                <span class="sodabag-stat-label">Pending Stories</span>
                <span class="sodabag-stat-number"><?php echo esc_html($user_stats['pending_stories']); ?></span>
            </div>
        </div>
    </div>

    <h2>Create New Campaign</h2>
	<form id="sodabag-create-campaign-form" class="sodabag-form" enctype="multipart/form-data">
        <?php wp_nonce_field('sodabag_create_campaign', 'sodabag_campaign_nonce'); ?>
        <input type="text" id="campaign-name" name="name" placeholder="Campaign Name" required>
        <textarea id="campaign-description" name="description" placeholder="Campaign Description" required></textarea>
        <select id="campaign-media-type" name="media_type" required>
            <option value="">Select Media Type</option>
            <option value="photo">Photo</option>
            <option value="video">Video</option>
            <option value="both">Both</option>
        </select>
        <input type="submit" value="Create Campaign" class="sodabag-button">
    </form>

    <h2>Your Campaigns</h2>
    <div class="sodabag-dashboard">
        <?php if (empty($campaigns)) : ?>
            <p>You haven't created any campaigns yet.</p>
        <?php else : ?>
            <?php foreach ($campaigns as $campaign) : ?>
                <div class="sodabag-campaign" data-campaign-id="<?php echo esc_attr($campaign->id); ?>">
                    <h3><?php echo esc_html($campaign->name); ?></h3>
                    <p><?php echo esc_html($campaign->description); ?></p>
                    <p>Media Type: <?php echo esc_html($campaign->media_type); ?></p>
                    <p>Created: <?php echo esc_html($campaign->created_at); ?></p>
                    <a href="<?php echo esc_url(add_query_arg('campaign_id', $campaign->id, home_url('/sodabag-campaign-details/'))); ?>" class="sodabag-button"><i class="fas fa-edit"></i> Edit Campaign</a>
                    <a href="<?php echo esc_url(add_query_arg('campaign_id', $campaign->id, home_url('/sodabag-campaign-stories/'))); ?>" class="sodabag-button"><i class="fas fa-eye"></i> View Stories</a>
                    <a href="<?php echo esc_url(add_query_arg('campaign_id', $campaign->id, home_url('/sodabag-analytics/'))); ?>" class="sodabag-button"><i class="fas fa-chart-bar"></i> View Analytics</a>
                    <button class="sodabag-button sodabag-delete-campaign"><i class="fas fa-trash-alt"></i> Delete Campaign</button>
                    
                    <div class="sodabag-campaign-resources">
                        <h4>Campaign Resources</h4>
                        <p>Campaign URL: <input type="text" readonly value="<?php echo esc_url(home_url('/sodabag-submission/?campaign=' . $campaign->id)); ?>"></p>
						<p>Custom URL: 
        <input type="text" readonly value="<?php echo esc_url(!empty($campaign->custom_submission_url) ? $campaign->custom_submission_url : home_url('/sodabag-submission/?campaign=' . $campaign->id)); ?>">
    </p>
                        <p>Story Display Embed Code:</p>
                        <textarea readonly rows="10" style="width: 100%;">
<div id="sodabag-stories-<?php echo esc_attr($campaign->id); ?>"></div>
<script src="<?php echo esc_url(SODABAG_PLUGIN_URL . 'assets/js/sodabag-embed.js'); ?>"></script>
<script>
SodaBagEmbed.load({
    container: 'sodabag-stories-<?php echo esc_attr($campaign->id); ?>',
    campaignId: <?php echo esc_js($campaign->id); ?>,
    limit: 5,
    showDate: true,
    apiUrl: '<?php echo esc_url(home_url('/wp-json/sodabag/v1/stories')); ?>',
    cssUrl: '<?php echo esc_url(SODABAG_PLUGIN_URL . 'assets/css/sodabag-styles.css'); ?>'
});
</script>
</textarea>
                        <p>Story Submission Form Embed Code:</p>
                        <textarea readonly rows="10" style="width: 100%;">
<div id="sodabag-submission-<?php echo esc_attr($campaign->id); ?>"></div>
<script src="<?php echo esc_url(SODABAG_PLUGIN_URL . 'assets/js/sodabag-embed.js'); ?>"></script>
<script>
SodaBagEmbed.loadSubmissionForm({
    container: 'sodabag-submission-<?php echo esc_attr($campaign->id); ?>',
    campaignId: <?php echo esc_js($campaign->id); ?>,
    apiUrl: '<?php echo esc_url(home_url('/wp-json/sodabag/v1/submit-story')); ?>',
    cssUrl: '<?php echo esc_url(SODABAG_PLUGIN_URL . 'assets/css/sodabag-styles.css'); ?>'
});
</script>
</textarea>
                        <div class="sodabag-qr-code">
    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode(home_url('/sodabag-qr-redirect/?campaign=' . $campaign->id . '&qr=1')); ?>" alt="QR Code">
    <br>
    <a href="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=<?php echo urlencode(home_url('/sodabag-qr-redirect/?campaign=' . $campaign->id . '&qr=1')); ?>" download="qr_code_campaign_<?php echo esc_attr($campaign->id); ?>.png" class="sodabag-button">Download QR Code</a>
</div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#sodabag-create-campaign-form').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        formData.append('action', 'sodabag_create_campaign');
        formData.append('nonce', sodabag_ajax.nonce);

        $.ajax({
            type: 'POST',
            url: sodabag_ajax.ajax_url,
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#sodabag-create-campaign-form input[type="submit"]').prop('disabled', true).val('Creating...');
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    location.reload();
                } else {
                    showNotification(response.data.message, 'error');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("AJAX error: " + textStatus + ' : ' + errorThrown);
                showNotification('An error occurred. Please try again.', 'error');
            },
            complete: function() {
                $('#sodabag-create-campaign-form input[type="submit"]').prop('disabled', false).val('Create Campaign');
            }
        });
    });

    $('.sodabag-delete-campaign').on('click', function() {
        var campaignId = $(this).closest('.sodabag-campaign').data('campaign-id');
        if (confirm('Are you sure you want to delete this campaign? This action cannot be undone.')) {
            $.ajax({
                url: sodabag_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'sodabag_delete_campaign',
                    nonce: sodabag_ajax.nonce,
                    campaign_id: campaignId
                },
                success: function(response) {
                    if (response.success) {
                        showNotification(response.data.message, 'success');
                        location.reload();
                    } else {
                        showNotification(response.data.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                    showNotification('An error occurred. Please try again.', 'error');
                }
            });
        }
    });

    function showNotification(message, type) {
        var notificationHtml = '<div class="sodabag-notification ' + type + '">' + message + '</div>';
        $('body').append(notificationHtml);
        setTimeout(function() {
            $('.sodabag-notification').fadeOut('slow', function() {
                $(this).remove();
            });
        }, 3000);
    }
});
</script>