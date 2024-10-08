<?php
// Ensure this file is being included by a parent file
if (!defined('ABSPATH')) exit;

// Ensure $stories and $campaign_id are set
if (!isset($stories) || !isset($campaign_id)) {
    wp_die('Invalid stories or campaign ID');
}

// Get campaign colors
$campaign = $this->get_campaign($campaign_id);
$primary_color = $campaign->primary_color;
$secondary_color = $campaign->secondary_color;
?>

<style>
    .sodabag-story-display {
        --primary-color: <?php echo esc_html($primary_color); ?>;
        --secondary-color: <?php echo esc_html($secondary_color); ?>;
    }
</style>

<div class="sodabag-story-display">
    <?php foreach ($stories as $story) : ?>
        <div class="sodabag-story" data-story-id="<?php echo esc_attr($story->id); ?>">
            <div class="sodabag-story-media">
                <?php if (!empty($story->media_url)) : ?>
                    <?php
                    $file_type = wp_check_filetype($story->media_url);
                    if (strpos($file_type['type'], 'image') !== false) : ?>
                        <img src="<?php echo esc_url($story->media_url); ?>" alt="Story media" loading="lazy">
                    <?php elseif (strpos($file_type['type'], 'video') !== false) : ?>
                        <video src="<?php echo esc_url($story->media_url); ?>" controls preload="metadata">
                            Your browser does not support the video tag.
                        </video>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="sodabag-story-overlay sodabag-story-overlay-top">
                <div class="sodabag-story-avatar">
                    <?php echo esc_html(substr($story->submitter_name, 0, 1)); ?>
                </div>
                <div class="sodabag-story-name"><?php echo esc_html($this->format_user_name($story->submitter_name)); ?></div>
            </div>
            <div class="sodabag-story-content">
                <p><?php echo esc_html($this->get_story_excerpt($story->content)); ?></p>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div id="sodabag-lightbox" class="sodabag-lightbox">
    <div class="sodabag-lightbox-content">
        <span class="sodabag-lightbox-close">&times;</span>
        <div class="sodabag-lightbox-media"></div>
        <div class="sodabag-lightbox-info"></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Lightbox functionality
    $('.sodabag-story-media').on('click', function() {
        var $story = $(this).closest('.sodabag-story');
        var $media = $(this).clone();
        var $content = $story.find('.sodabag-story-content').clone();
        var $info = $story.find('.sodabag-story-overlay').clone();
        
        $('#sodabag-lightbox .sodabag-lightbox-media').empty().append($media);
        $('#sodabag-lightbox .sodabag-lightbox-info').empty().append($content).append($info);
        $('#sodabag-lightbox').show();
    });

    $('.sodabag-lightbox-close').on('click', function() {
        $('#sodabag-lightbox').hide();
    });

    // Close lightbox when clicking outside the content
    $('#sodabag-lightbox').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
});
</script>