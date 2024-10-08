<?php
// Ensure this file is being included by a parent file
if (!defined('ABSPATH')) exit;

// Ensure $campaign and $stories are set
if (!isset($campaign) || !isset($stories)) {
    wp_die('Invalid campaign or stories');
}

$user_id = get_current_user_id();
$hubspot_channels = get_user_meta($user_id, 'hubspot_channels', true);
$hubspot_enabled_channels = get_user_meta($user_id, 'hubspot_enabled_channels', true);
$hubspot_portal_id = get_user_meta($user_id, 'hubspot_portal_id', true);
?>

<div class="sodabag-container">
    <h2>Stories for <?php echo esc_html($campaign->name); ?></h2>

    <div class="sodabag-story-controls">
        <select class="sodabag-story-sort">
            <option value="date_desc">Newest First</option>
            <option value="date_asc">Oldest First</option>
            <option value="rating_desc">Highest Rated</option>
            <option value="shares_desc">Most Shared</option>
        </select>
        <select class="sodabag-story-filter">
            <option value="all">All Stories</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
        </select>
    </div>

    <div class="sodabag-stories-list">
        <?php if (empty($stories)) : ?>
            <p>No stories found for this campaign.</p>
        <?php else : ?>
            <?php foreach ($stories as $story) : ?>
                <div class="sodabag-story" data-story-id="<?php echo esc_attr($story->id); ?>">
    <div class="sodabag-story-header">
        <div class="sodabag-story-avatar">
            <i class="fas fa-user"></i>
        </div>
        <div>
            <div class="sodabag-story-name"><?php echo esc_html($story->submitter_name); ?></div>
            <div class="sodabag-story-date"><?php echo date('m/d/y', strtotime($story->created_at)); ?></div>
        </div>
    </div>
    <p><?php echo esc_html($story->content); ?></p>
    <?php if (!empty($story->media_url)) : ?>
        <div class="sodabag-story-media">
            <?php
            $file_type = wp_check_filetype($story->media_url);
            if (strpos($file_type['type'], 'image') !== false) : ?>
                <img src="<?php echo esc_url($story->media_url); ?>" alt="Story media">
            <?php elseif (strpos($file_type['type'], 'video') !== false) : ?>
                <video src="<?php echo esc_url($story->media_url); ?>" controls></video>
            <?php else : ?>
                <p>Unsupported media type</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <p class="sodabag-story-status">Status: <?php echo esc_html($story->status); ?></p>
    <?php if ($story->ai_moderation_status): ?>
        <div class="sodabag-ai-moderation-status">
            <i class="fas fa-robot"></i>
            <?php if ($story->ai_moderation_status === 'pass'): ?>
                <i class="fas fa-check-circle" style="color: green;"></i>
            <?php elseif ($story->ai_moderation_status === 'fail'): ?>
                <i class="fas fa-times-circle" style="color: red;"></i>
            <?php endif; ?>
            AI Moderation: <?php echo esc_html($story->ai_moderation_status); ?>
        </div>
    <?php endif; ?>
    <?php if ($story->status === 'pending') : ?>
        <button class="sodabag-approve-story sodabag-button">Approve</button>
        <button class="sodabag-reject-story sodabag-button">Reject</button>
    <?php endif; ?>
    <button class="sodabag-delete-story sodabag-button" data-story-id="<?php echo esc_attr($story->id); ?>">Delete</button>
                    
                    <?php if (!empty($hubspot_enabled_channels)) : ?>
                        <button class="sodabag-hubspot-share sodabag-button" data-story-id="<?php echo esc_attr($story->id); ?>">
                            <i class="fab fa-hubspot"></i> Share
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- HubSpot Share Modal -->
<div id="sodabag-hubspot-share-modal" class="sodabag-modal">
    <div class="sodabag-modal-content">
        <span class="sodabag-modal-close">&times;</span>
        <h3>Share to HubSpot Channels</h3>
        <form id="sodabag-hubspot-share-form">
            <input type="hidden" id="hubspot-share-story-id" name="story_id" value="">
            <div class="sodabag-hubspot-channels">
                <?php foreach ($hubspot_channels as $channel) : ?>
                    <?php if (isset($hubspot_enabled_channels[$channel['id']])) : ?>
                        <div class="sodabag-hubspot-channel">
                            <label>
                                <input type="checkbox" name="channels[]" value="<?php echo esc_attr($channel['id']); ?>">
                                <i class="fab fa-<?php echo esc_attr(strtolower($channel['type'])); ?>"></i>
                                <?php echo esc_html($channel['name']); ?>
                            </label>
                            <div class="sodabag-hubspot-channel-options">
                                <label>
                                    <input type="checkbox" name="draft_<?php echo esc_attr($channel['id']); ?>" value="1">
                                    Draft
                                </label>
                                <input type="text" name="schedule_<?php echo esc_attr($channel['id']); ?>" class="sodabag-datepicker" placeholder="Schedule (optional)">
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="sodabag-button">Share</button>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('.sodabag-approve-story, .sodabag-reject-story').on('click', function() {
        var $story = $(this).closest('.sodabag-story');
        var storyId = $story.data('story-id');
        var newStatus = $(this).hasClass('sodabag-approve-story') ? 'approved' : 'rejected';

        $.ajax({
            url: sodabag_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sodabag_moderate_story',
                nonce: sodabag_ajax.nonce,
                story_id: storyId,
                new_status: newStatus
            },
            success: function(response) {
                if (response.success) {
                    $story.find('.sodabag-story-status').text('Status: ' + newStatus);
                    $story.find('.sodabag-approve-story, .sodabag-reject-story').remove();
                    showNotification('Story ' + newStatus + ' successfully', 'success');
                } else {
                    showNotification(response.data.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
                showNotification('An error occurred. Please try again.', 'error');
            }
        });
    });

    $(document).on('click', '.sodabag-delete-story', function() {
    if (confirm('Are you sure you want to delete this story?')) {
        var $story = $(this).closest('.sodabag-story');
        var storyId = $story.data('story-id');

        $.ajax({
            url: sodabag_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sodabag_delete_story',
                nonce: sodabag_ajax.nonce,
                story_id: storyId
            },
            success: function(response) {
                if (response.success) {
                    $story.remove();
                    showNotification('Story deleted successfully', 'success');
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

    $('.sodabag-story-sort, .sodabag-story-filter').on('change', function() {
        var sortBy = $('.sodabag-story-sort').val();
        var filterBy = $('.sodabag-story-filter').val();

        $.ajax({
            url: sodabag_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sodabag_filter_sort_stories',
                nonce: sodabag_ajax.nonce,
                campaign_id: <?php echo esc_js($campaign->id); ?>,
                sort_by: sortBy,
                filter_by: filterBy
            },
            success: function(response) {
                if (response.success) {
                    $('.sodabag-stories-list').html(response.data.html);
                } else {
                    showNotification(response.data.message, 'error');
                }
            },
            error: function() {
                showNotification('An error occurred. Please try again.', 'error');
            }
        });
    });

    $('.sodabag-hubspot-share').on('click', function() {
        var storyId = $(this).data('story-id');
        $('#hubspot-share-story-id').val(storyId);
        $('#sodabag-hubspot-share-modal').show();
    });

    $('.sodabag-modal-close').on('click', function() {
        $('#sodabag-hubspot-share-modal').hide();
    });

    $(window).on('click', function(event) {
        if ($(event.target).is('#sodabag-hubspot-share-modal')) {
            $('#sodabag-hubspot-share-modal').hide();
        }
    });

    $('.sodabag-datepicker').datetimepicker({
        format: 'Y-m-d H:i',
        step: 15
    });

    $('#sodabag-hubspot-share-form').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        formData += '&action=sodabag_share_to_hubspot&nonce=' + sodabag_ajax.nonce;

        $.ajax({
            url: sodabag_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showNotification('Story shared successfully to HubSpot', 'success');
                    $('#sodabag-hubspot-share-modal').hide();
                    var hubspotUrl = 'https://app.hubspot.com/social/<?php echo esc_js($hubspot_portal_id); ?>/manage';
                    var successMessage = 'Story shared successfully. ' +
                        '<a href="#" class="sodabag-button">Back to Stories</a> ' +
                        '<a href="' + hubspotUrl + '" target="_blank" class="sodabag-button">View on HubSpot</a>';
                    showNotification(successMessage, 'success', 10000);
                } else {
                    showNotification(response.data.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
                showNotification('An error occurred. Please try again.', 'error');
            }
        });
    });

    function showNotification(message, type, duration = 3000) {
        var notificationHtml = '<div class="sodabag-notification ' + type + '">' + message + '</div>';
        $('body').append(notificationHtml);
        setTimeout(function() {
            $('.sodabag-notification').fadeOut('slow', function() {
                $(this).remove();
            });
        }, duration);
    }
});
</script>