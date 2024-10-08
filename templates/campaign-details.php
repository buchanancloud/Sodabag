<?php
// Ensure this file is being included by a parent file
if (!defined('ABSPATH')) exit;

// Ensure $campaign is set
if (!isset($campaign)) {
    wp_die('Invalid campaign');
}
?>

<div class="sodabag-container">
    <div class="sodabag-dashboard-menu">
        <a href="<?php echo home_url('/business-owner-dashboard/'); ?>" class="sodabag-menu-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="<?php echo wp_logout_url(home_url()); ?>" class="sodabag-menu-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="sodabag-campaign-details">
        <h2><?php echo esc_html($campaign->name); ?></h2>
        
        <div class="sodabag-campaign-details-grid">
            <div class="sodabag-campaign-details-item">
                <label>Description:</label>
                <p><?php echo esc_html($campaign->description); ?></p>
            </div>
            
            <div class="sodabag-campaign-details-item">
                <label>Media Type:</label>
                <p><?php echo esc_html($campaign->media_type); ?></p>
            </div>
            
            <div class="sodabag-campaign-details-item">
                <label>Created At:</label>
                <p><?php echo esc_html($campaign->created_at); ?></p>
            </div>
            
            <div class="sodabag-campaign-details-item">
                <label>Custom Text:</label>
                <p><?php echo esc_html($campaign->custom_text); ?></p>
            </div>
            
            <div class="sodabag-campaign-details-item">
                <label>Sharing Text:</label>
                <p><?php echo esc_html($campaign->sharing_text); ?></p>
            </div>
            
            <div class="sodabag-campaign-details-item">
                <label>Sharing Hashtags:</label>
                <p><?php echo esc_html($campaign->sharing_hashtags); ?></p>
            </div>
            
            <div class="sodabag-campaign-details-item">
                <label>Sharing URL:</label>
                <p><?php echo esc_url($campaign->sharing_url); ?></p>
            </div>
            
            <div class="sodabag-campaign-details-item">
                <label>Email Subject:</label>
                <p><?php echo esc_html($campaign->email_subject); ?></p>
            </div>
            
            <div class="sodabag-campaign-details-item">
                <label>Email Body:</label>
                <p><?php echo esc_html($campaign->email_body); ?></p>
            </div>

            <div class="sodabag-campaign-details-item">
                <label>Custom Submission URL:</label>
                <p><?php echo esc_url($campaign->custom_submission_url); ?></p>
            </div>
        </div>
        
        <div class="sodabag-campaign-custom-fields">
            <h3>Custom Fields</h3>
            <?php
            $custom_fields = maybe_unserialize($campaign->custom_fields);
            if (!empty($custom_fields) && is_array($custom_fields)) :
                foreach ($custom_fields as $field) :
            ?>
                <div class="sodabag-campaign-details-item">
                    <label><?php echo esc_html($field['name']); ?>:</label>
                    <p><?php echo esc_html($field['type']); ?></p>
                </div>
            <?php
                endforeach;
            else :
                echo '<p>No custom fields defined.</p>';
            endif;
            ?>
        </div>
        
        <div class="sodabag-campaign-actions">
            <a href="<?php echo esc_url(add_query_arg('campaign_id', $campaign->id, home_url('/sodabag-campaign-stories/'))); ?>" class="sodabag-button"><i class="fas fa-eye"></i> View Stories</a>
            <a href="<?php echo esc_url(add_query_arg('campaign_id', $campaign->id, home_url('/sodabag-analytics/'))); ?>" class="sodabag-button"><i class="fas fa-chart-bar"></i> View Analytics</a>
            <button id="sodabag-edit-campaign" class="sodabag-button"><i class="fas fa-edit"></i> Edit Campaign</button>
        </div>
    </div>
    
    <form id="sodabag-edit-campaign-form" class="sodabag-form" style="display:none;">
        <input type="hidden" name="campaign_id" value="<?php echo esc_attr($campaign->id); ?>">
        <input type="text" name="name" value="<?php echo esc_attr($campaign->name); ?>" required>
        <textarea name="description" required><?php echo esc_textarea($campaign->description); ?></textarea>
        <select name="media_type" required>
            <option value="photo" <?php selected($campaign->media_type, 'photo'); ?>>Photo</option>
            <option value="video" <?php selected($campaign->media_type, 'video'); ?>>Video</option>
            <option value="both" <?php selected($campaign->media_type, 'both'); ?>>Both</option>
        </select>
        <div class="sodabag-ai-moderation">
            <h3>AI Moderation</h3>
            <label for="ai_moderation_enabled">
                <input type="checkbox" id="ai_moderation_enabled" name="ai_moderation_enabled" value="1" <?php checked($campaign->ai_moderation_enabled, 1); ?>>
                Enable AI Moderation
            </label>
            <?php
            $anthropic_api_key = get_option('sodabag')['anthropic_api_key'];
            $openai_api_key = get_option('sodabag')['openai_api_key'];
            if ($anthropic_api_key || $openai_api_key) {
                echo '<select name="selected_llm">';
                if ($anthropic_api_key) {
                    echo '<option value="claude" ' . selected($campaign->selected_llm, 'claude', false) . '>Claude</option>';
                }
                if ($openai_api_key) {
                    echo '<option value="gpt-4" ' . selected($campaign->selected_llm, 'gpt-4', false) . '>GPT-4</option>';
                }
                echo '</select>';
            } else {
                echo '<p>No LLM API keys configured. <a href="' . admin_url('admin.php?page=sodabag-integrations') . '">Configure integrations</a></p>';
            }
            ?>
        </div>
        <div class="sodabag-logo-upload">
            <label for="logo">Campaign Logo:</label>
            <?php if (!empty($campaign->logo_url)) : ?>
                <img src="<?php echo esc_url($campaign->logo_url); ?>" alt="Current Logo" class="campaign-logo-preview" style="max-width: 200px;">
            <?php endif; ?>
            <input type="file" name="logo" accept="image/*" id="logo">
            <p class="description">Current logo will be kept if no new image is uploaded.</p>
        </div>
        <textarea name="custom_text" placeholder="Custom text to display below logo"><?php echo esc_textarea($campaign->custom_text); ?></textarea>
        <textarea name="sharing_text" placeholder="Default sharing text"><?php echo esc_textarea($campaign->sharing_text); ?></textarea>
        <input type="text" name="sharing_hashtags" placeholder="Default hashtags (comma-separated)" value="<?php echo esc_attr($campaign->sharing_hashtags); ?>">
        <input type="url" name="sharing_url" placeholder="Default sharing URL" value="<?php echo esc_url($campaign->sharing_url); ?>">
        <input type="text" name="email_subject" placeholder="Email sharing subject" value="<?php echo esc_attr($campaign->email_subject); ?>">
        <textarea name="email_body" placeholder="Email sharing body"><?php echo esc_textarea($campaign->email_body); ?></textarea>
        <input type="url" name="custom_submission_url" placeholder="Custom Submission URL" value="<?php echo esc_url($campaign->custom_submission_url); ?>">
        <input type="url" name="custom_story_url" placeholder="Custom Story URL" value="<?php echo esc_url($campaign->custom_story_url); ?>">
        <div class="sodabag-color-selectors">
            <label for="primary_color">Primary Color:</label>
            <input type="color" name="primary_color" id="primary_color" value="<?php echo esc_attr($campaign->primary_color); ?>">
            
            <label for="secondary_color">Secondary Color:</label>
            <input type="color" name="secondary_color" id="secondary_color" value="<?php echo esc_attr($campaign->secondary_color); ?>">
        </div>
        
        <div id="custom-fields-container">
            <h3>Custom Fields</h3>
            <?php
            $custom_fields = maybe_unserialize($campaign->custom_fields);
            if (!empty($custom_fields) && is_array($custom_fields)) :
                foreach ($custom_fields as $index => $field) :
            ?>
                <div class="sodabag-custom-field">
                    <input type="text" name="custom_fields[<?php echo $index; ?>][name]" value="<?php echo esc_attr($field['name']); ?>" placeholder="Field Name" required>
                    <select name="custom_fields[<?php echo $index; ?>][type]">
                        <option value="text" <?php selected($field['type'], 'text'); ?>>Text</option>
                        <option value="textarea" <?php selected($field['type'], 'textarea'); ?>>Textarea</option>
                        <option value="number" <?php selected($field['type'], 'number'); ?>>Number</option>
                        <option value="checkbox" <?php selected($field['type'], 'checkbox'); ?>>Checkbox</option>
                    </select>
                    <button type="button" class="remove-custom-field">Remove</button>
                </div>
            <?php
                endforeach;
            endif;
            ?>
        </div>
        <button type="button" id="add-custom-field" class="sodabag-button">Add Custom Field</button>
        
        <div class="sodabag-terms-acceptance">
            <input type="checkbox" name="require_terms_acceptance" id="require_terms_acceptance" <?php checked($campaign->require_terms_acceptance, 1); ?>>
            <label for="require_terms_acceptance">Require Terms Acceptance</label>
        </div>
        <textarea name="terms_acceptance_text" placeholder="Terms acceptance text"><?php echo esc_textarea($campaign->terms_acceptance_text); ?></textarea>
        <input type="submit" value="Update Campaign" class="sodabag-button" id="edit-campaign-submit">
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle edit form visibility
    $('#sodabag-edit-campaign').on('click', function() {
        $('#sodabag-edit-campaign-form').toggle();
    });

    // Add custom field
    function addCustomField(name = '', type = 'text') {
        var index = $('.sodabag-custom-field').length;
        var fieldHtml = `
            <div class="sodabag-custom-field">
                <input type="text" name="custom_fields[${index}][name]" value="${name}" placeholder="Field Name" required>
                <select name="custom_fields[${index}][type]">
                    <option value="text" ${type === 'text' ? 'selected' : ''}>Text</option>
                    <option value="textarea" ${type === 'textarea' ? 'selected' : ''}>Textarea</option>
                    <option value="number" ${type === 'number' ? 'selected' : ''}>Number</option>
                    <option value="checkbox" ${type === 'checkbox' ? 'selected' : ''}>Checkbox</option>
                </select>
                <button type="button" class="remove-custom-field">Remove</button>
            </div>
        `;
        $('#custom-fields-container').append(fieldHtml);
    }

    $('#add-custom-field').on('click', function() {
        addCustomField();
    });

    // Remove custom field
    $(document).on('click', '.remove-custom-field', function() {
        $(this).closest('.sodabag-custom-field').remove();
    });

    // Handle form submission
    $('#sodabag-edit-campaign-form').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        formData.append('action', 'sodabag_edit_campaign');
        formData.append('nonce', sodabag_ajax.nonce);

        $.ajax({
            url: sodabag_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#edit-campaign-submit').prop('disabled', true).text('Updating...');
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    if (response.data.logo_changed) {
                        $('.campaign-logo-preview').attr('src', response.data.logo_url);
                    }
                    // Update the custom fields display
                    updateCustomFieldsDisplay(response.data.custom_fields);
                    // Reload the page to show updated campaign details
                    location.reload();
                } else {
                    showNotification(response.data.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
                showNotification('An error occurred. Please try again.', 'error');
            },
            complete: function() {
                $('#edit-campaign-submit').prop('disabled', false).text('Update Campaign');
            }
        });
    });
	
	// Update custom fields display
    function updateCustomFieldsDisplay(customFields) {
        var container = $('#custom-fields-container');
        container.find('.sodabag-custom-field').remove();
        if (customFields && customFields.length > 0) {
            customFields.forEach(function(field, index) {
                addCustomField(field.name, field.type);
            });
        }
    }

    // Preview image before upload
    $('#logo').on('change', function() {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('.campaign-logo-preview').attr('src', e.target.result);
            }
            reader.readAsDataURL(file);
        }
    });

    // Show notification
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