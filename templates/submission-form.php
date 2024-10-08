<?php
// Ensure this file is being included by a parent file
if (!defined('ABSPATH')) exit;

// Ensure $campaign and $campaign_id are set
if (!isset($campaign) || !isset($campaign_id)) {
    wp_die('Invalid campaign');
}
?>

<div class="sodabag-container" style="--primary-color: <?php echo esc_attr($campaign->primary_color); ?>; --secondary-color: <?php echo esc_attr($campaign->secondary_color); ?>;">
    <?php if (!empty($campaign->logo_url)) : ?>
        <div class="sodabag-campaign-logo-container">
            <img src="<?php echo esc_url($campaign->logo_url); ?>" alt="<?php echo esc_attr($campaign->name); ?> logo" class="sodabag-campaign-logo">
        </div>
    <?php endif; ?>

    <?php if (!empty($campaign->custom_text)) : ?>
        <p class="sodabag-campaign-custom-text"><?php echo wp_kses_post($campaign->custom_text); ?></p>
    <?php endif; ?>

    <form id="sodabag-story-form" class="sodabag-form" enctype="multipart/form-data" method="post">
        <?php wp_nonce_field('sodabag_submit_story', 'sodabag_story_nonce'); ?>
        <input type="hidden" name="campaign_id" value="<?php echo esc_attr($campaign_id); ?>">
        <input type="hidden" name="action" value="sodabag_submit_story">

        <div class="sodabag-media-upload">
            <label for="media-upload" class="sodabag-upload-label">
                <i class="fas fa-image"></i>
                <span>Add Media</span>
            </label>
            <input type="file" id="media-upload" name="media" accept="image/*,video/*" style="display: none;">
            <div id="media-preview"></div>
        </div>

        <label for="story-content">Post Caption:</label>
        <textarea id="story-content" name="content" placeholder="Enter some text about your post!" required></textarea>

        <button type="button" id="next-step" class="sodabag-button">Post</button>

        <div id="user-info" style="display: none;">
            <label for="submitter-name">Display Name</label>
            <input type="text" id="submitter-name" name="submitter_name" required>

            <label for="submitter-email">Email</label>
            <input type="email" id="submitter-email" name="submitter_email" required>

            <button type="submit" class="sodabag-button">Submit</button>
        </div>
    </form>
</div>

<div id="sodabag-share-modal" class="sodabag-modal">
    <div class="sodabag-modal-content">
        <?php if (!empty($campaign->custom_story_url)) : ?>
            <a href="<?php echo esc_url($campaign->custom_story_url); ?>" target="_blank" class="sodabag-button view-post-button" style="display: block; text-align: center; margin-bottom: 20px;">View Post</a>
        <?php endif; ?>
        <span class="sodabag-modal-close">&times;</span>
        <p style="font-size: 14px; text-align: center; margin-bottom: 15px;">Share your post:</p>
        <div class="sodabag-share-buttons">
            <button class="sodabag-share-button facebook" data-platform="facebook">
                <i class="fab fa-facebook-f"></i>
                <span>Facebook</span>
            </button>
            <button class="sodabag-share-button twitter" data-platform="twitter">
                <i class="fab fa-twitter"></i>
                <span>Twitter</span>
            </button>
            <button class="sodabag-share-button linkedin" data-platform="linkedin">
                <i class="fab fa-linkedin-in"></i>
                <span>LinkedIn</span>
            </button>
            <button class="sodabag-share-button email" data-platform="email">
                <i class="fas fa-envelope"></i>
                <span>Email</span>
            </button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Media preview functionality
    $('#media-upload').on('change', function(e) {
        var file = e.target.files[0];
        var reader = new FileReader();
        reader.onload = function(e) {
            var preview = $('#media-preview');
            preview.empty();
            if (file.type.startsWith('image/')) {
                preview.html('<img src="' + e.target.result + '" alt="Preview">');
            } else if (file.type.startsWith('video/')) {
                preview.html('<video src="' + e.target.result + '" controls></video>');
            }
        }
        reader.readAsDataURL(file);
    });

    // Next step button functionality
    $('#next-step').on('click', function() {
        $('#user-info').show();
        $(this).hide();
    });

    // Form submission
   $('#sodabag-story-form').on('submit', function(e) {
    e.preventDefault();
    var formData = new FormData(this);

    $.ajax({
        url: sodabag_ajax.ajax_url,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                $('#sodabag-story-form')[0].reset();
                $('#media-preview').empty();
                
                // Show share modal
                if (response.data.show_share_modal) {
                    $('#sodabag-share-modal').fadeIn(300);
                    $('#sodabag-share-modal').data('story-id', response.data.story_id);
                }
            } else {
                console.error('Error:', response.data.message);
            }
        },
        error: function() {
            console.error('An error occurred. Please try again.');
        }
    });
});

    // Share modal functionality
    $('.sodabag-share-button').on('click', function() {
        var platform = $(this).data('platform');
        var storyId = $('#sodabag-share-modal').data('story-id');
        

    });

    $('.sodabag-modal-close').on('click', function() {
        $('#sodabag-share-modal').fadeOut(300);
    });
});
</script>