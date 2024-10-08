<div class="sodabag-stories-list">
    <?php if (empty($stories)) : ?>
        <p><?php _e('No stories found for this campaign.', 'sodabag'); ?></p>
    <?php else : ?>
        <?php foreach ($stories as $story) : ?>
            <div class="sodabag-story" data-story-id="<?php echo esc_attr($story->id); ?>">
                <p><?php echo esc_html($story->content); ?></p>
                <?php if ($story->media_url) : ?>
                    <?php if (strpos($story->media_url, '.mp4') !== false) : ?>
                        <video src="<?php echo esc_url($story->media_url); ?>" controls></video>
                    <?php else : ?>
                        <img src="<?php echo esc_url($story->media_url); ?>" alt="Story media">
                    <?php endif; ?>
                <?php endif; ?>
                <p>Status: <?php echo esc_html($story->status); ?></p>
                <?php if ($story->status === 'pending') : ?>
                    <button class="sodabag-approve-story"><?php _e('Approve', 'sodabag'); ?></button>
                    <button class="sodabag-reject-story"><?php _e('Reject', 'sodabag'); ?></button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>