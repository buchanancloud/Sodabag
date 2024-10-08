jQuery(document).ready(function($) {
    let isSubmitting = false;

    // Login form submission
    $('#sodabag-login-form').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            type: 'POST',
            url: sodabag_ajax.ajax_url,
            data: {
                action: 'sodabag_login',
                nonce: sodabag_ajax.nonce,
                username: $('#sodabag-username').val(),
                password: $('#sodabag-password').val()
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.redirect;
                } else {
                    showNotification(response.data.message, 'error');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("AJAX error: " + textStatus + ' : ' + errorThrown);
                showNotification('An error occurred. Please try again.', 'error');
            }
        });
    });

    // Registration form submission
    $('#sodabag-register-form').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            type: 'POST',
            url: sodabag_ajax.ajax_url,
            data: {
                action: 'sodabag_register',
                nonce: sodabag_ajax.nonce,
                username: $('#sodabag-reg-username').val(),
                email: $('#sodabag-reg-email').val(),
                password: $('#sodabag-reg-password').val(),
                business_name: $('#sodabag-reg-business-name').val()
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.redirect;
                } else {
                    showNotification(response.data.message, 'error');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("AJAX error: " + textStatus + ' : ' + errorThrown);
                showNotification('An error occurred. Please try again.', 'error');
            }
        });
    });

    jQuery(document).ready(function($) {
    // Story submission form
    $('#sodabag-story-form').on('submit', function(e) {
        e.preventDefault();
        if (isSubmitting) return;
        isSubmitting = true;

        var formData = new FormData(this);

        $('#submission-progress').show();
        $('#submission-result').hide();

        $.ajax({
            url: sodabag_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#submission-progress').hide();
                $('#submission-result').show();
                if (response.success) {
                    $('#submission-result').html('<p class="success">' + response.data.message + '</p>');
                    $('#sodabag-story-form')[0].reset();
                    
                    // Show share modal
                    if (response.data.show_share_modal) {
                        $('#sodabag-share-modal').fadeIn(300);
                        // Store the story ID in the modal for sharing
                        $('#sodabag-share-modal').data('story-id', response.data.story_id);
                    }
                } else {
                    $('#submission-result').html('<p class="error">Error: ' + response.data.message + '</p>');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                $('#submission-progress').hide();
                $('#submission-result').show();
                $('#submission-result').html('<p class="error">An error occurred. Please try again.</p>');
            },
            complete: function() {
                isSubmitting = false;
            }
        });
    });
// Handle share button clicks
$('.sodabag-share-button').on('click', function() {
    var platform = $(this).data('platform');
    var storyId = $('#sodabag-share-modal').data('story-id');
    
    $.ajax({
        url: sodabag_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'sodabag_share_story',
            nonce: sodabag_ajax.nonce,
            story_id: storyId,
            platform: platform
        },
        success: function(response) {
            if (response.success) {
                var shareData = response.data;
                var shareUrl = shareData.url;
                var shareText = shareData.text;
                var shareHashtags = shareData.hashtags;
                var shareImage = shareData.image;
                
                var shareWindow;
                switch (platform) {
                    case 'facebook':
                        shareWindow = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(shareUrl);
                        if (shareImage) {
                            shareWindow += '&picture=' + encodeURIComponent(shareImage);
                        }
                        break;
                    case 'twitter':
                        shareWindow = 'https://twitter.com/intent/tweet?text=' + encodeURIComponent(shareText);
                        if (shareUrl) {
                            shareWindow += '&url=' + encodeURIComponent(shareUrl);
                        }
                        if (shareHashtags) {
                            shareWindow += '&hashtags=' + encodeURIComponent(shareHashtags.replace('#', ''));
                        }
                        break;
                    case 'linkedin':
                        shareWindow = 'https://www.linkedin.com/shareArticle?mini=true&url=' + encodeURIComponent(shareUrl);
                        if (shareText) {
                            shareWindow += '&title=' + encodeURIComponent(shareText);
                        }
                        break;
                    case 'email':
                        var subject = 'Check out this story';
                        var body = shareText + '\n\n' + shareUrl;
                        if (shareHashtags) {
                            body += '\n\nHashtags: ' + shareHashtags;
                        }
                        window.location.href = 'mailto:?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(body);
                        return; // Exit the function as we don't need to open a new window for email
                }
                
                // Open share window
                if (shareWindow) {
                    window.open(shareWindow, platform + '-share-dialog', 'width=626,height=436');
                }
            }
        }
    });
});

    // Close share modal
    $('.sodabag-modal-close').on('click', function() {
        $('#sodabag-share-modal').fadeOut(300);
    });

    // Close modal when clicking outside
    $(window).on('click', function(event) {
        if ($(event.target).is('#sodabag-share-modal')) {
            $('#sodabag-share-modal').fadeOut(300);
        }
    });
});
    // Campaign management
    $('#sodabag-create-campaign-form').on('submit', function(e) {
        e.preventDefault();
        if (isSubmitting) return;
        isSubmitting = true;

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
                isSubmitting = false;
            }
        });
    });

    $('.sodabag-edit-campaign').on('click', function() {
        var campaignId = $(this).closest('.sodabag-campaign').data('campaign-id');
        window.location.href = sodabag_ajax.campaign_edit_url + '?campaign_id=' + campaignId;
    });

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

    $('.sodabag-delete-campaign').on('click', function() {
        var campaignId = $(this).closest('.sodabag-campaign').data('campaign-id');
        if (confirm('Are you sure you want to delete this campaign? This action cannot be undone.')) {
            $.ajax({
                type: 'POST',
                url: sodabag_ajax.ajax_url,
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

    // Story moderation
    $(document).on('click', '.sodabag-approve-story, .sodabag-reject-story', function() {
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
                    showNotification(response.data.message, 'success');
                    $story.find('.sodabag-story-status').text('Status: ' + newStatus);
                    $story.find('.sodabag-approve-story, .sodabag-reject-story').remove();
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

    // Delete story
    $(document).on('click', '.sodabag-delete-story', function() {
        if (confirm('Are you sure you want to delete this story?')) {
            var storyId = $(this).data('story-id');
            var $story = $(this).closest('.sodabag-story');
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
                        showNotification('Story deleted successfully.', 'success');
                    } else {
                        showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    showNotification('An error occurred. Please try again.', 'error');
                }
            });
        }
    });

    // Add custom field
    $('#add-custom-field, #edit-add-custom-field').on('click', function() {
        var container = $(this).prev().attr('id');
        addCustomField('', 'text', '#' + container);
    });

    // Remove custom field
    $(document).on('click', '.remove-custom-field', function() {
        $(this).closest('.sodabag-custom-field').remove();
    });

    function addCustomField(name = '', type = 'text', container = '#custom-fields-container') {
        var fieldHtml = `
            <div class="sodabag-custom-field">
                <input type="text" name="field_name[]" value="${name}" placeholder="Field Name" required>
                <select name="field_type[]">
                    <option value="text" ${type === 'text' ? 'selected' : ''}>Text</option>
                    <option value="textarea" ${type === 'textarea' ? 'selected' : ''}>Textarea</option>
                    <option value="number" ${type === 'number' ? 'selected' : ''}>Number</option>
                    <option value="checkbox" ${type === 'checkbox' ? 'selected' : ''}>Checkbox</option>
                </select>
                <button type="button" class="remove-custom-field">Remove</button>
            </div>
        `;
        $(container).append(fieldHtml);
    }

    // Real-time updates for embedded stories
    function updateEmbeddedStories() {
        $('.sodabag-story-display').each(function() {
            var $container = $(this);
            var campaignId = $container.data('campaign-id');
            $.ajax({
                url: sodabag_ajax.ajax_url,
                data: {
                    action: 'sodabag_get_stories',
                    nonce: sodabag_ajax.nonce,
                    campaign_id: campaignId
                },
                success: function(response) {
                    if (response.success) {
                        $container.html(response.data.html);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("AJAX error: " + textStatus + ' : ' + errorThrown);
                }
            });
        });
    }

    // Update embedded stories every 60 seconds
    setInterval(updateEmbeddedStories, 60000);

    // Story filtering and sorting
    $('.sodabag-story-sort, .sodabag-story-filter').on('change', function() {
        var campaignId = $('.sodabag-story-display').data('campaign-id');
        var sortBy = $('.sodabag-story-sort').val();
        var filterBy = $('.sodabag-story-filter').val();

        $.ajax({
            url: sodabag_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'sodabag_filter_sort_stories',
                nonce: sodabag_ajax.nonce,
                campaign_id: campaignId,
                sort_by: sortBy,
                filter_by: filterBy
            },
            success: function(response) {
                if (response.success) {
                    $('.sodabag-story-display').html(response.data.html);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("AJAX error: " + textStatus + ' : ' + errorThrown);
                showNotification('An error occurred. Please try again.', 'error');
            }
        });
    });

    // Lightbox functionality
    $(document).on('click', '.sodabag-story', function() {
        var $story = $(this);
        var $media = $story.find('.sodabag-story-media').clone();
        var $info = $story.find('.sodabag-story-overlay').clone();
        
        $('#sodabag-lightbox .sodabag-lightbox-media').empty().append($media);
        $('#sodabag-lightbox .sodabag-lightbox-info').empty().append($info);
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

    // View Stories functionality
    $('.sodabag-view-stories').on('click', function() {
        var campaignId = $(this).closest('.sodabag-campaign').data('campaign-id');
        window.location.href = sodabag_ajax.campaign_stories_url + '?campaign_id=' + campaignId;
    });

    // Lazy loading
    function lazyLoad() {
        var lazyImages = [].slice.call(document.querySelectorAll("img.lazy"));
        var lazyVideos = [].slice.call(document.querySelectorAll("video.lazy"));

        if ("IntersectionObserver" in window) {
            let lazyImageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        let lazyImage = entry.target;
                        lazyImage.src = lazyImage.dataset.src;
                        lazyImage.classList.remove("lazy");
                        lazyImageObserver.unobserve(lazyImage);
                    }
                });
            });

            let lazyVideoObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        let lazyVideo = entry.target;
                        lazyVideo.load();
                        lazyVideo.classList.remove("lazy");
                        lazyVideoObserver.unobserve(lazyVideo);
                    }
                });
            });

            lazyImages.forEach(function(lazyImage) {
                lazyImageObserver.observe(lazyImage);
            });

            lazyVideos.forEach(function(lazyVideo) {
                lazyVideoObserver.observe(lazyVideo);
            });
        }
    }
$("#sodabag-populate-custom-story-url").on("click", function() {
    $.post(ajaxurl, {
        action: "sodabag_populate_custom_story_url_data",
        nonce: sodabag_ajax.nonce
    }, function(response) {
        if (response.success) {
            alert(response.data.message);
        } else {
            alert("Error: " + response.data.message);
        }
    });
});
    // Call lazy loading on document ready and after AJAX content updates
    $(document).ready(lazyLoad);
    $(document).ajaxComplete(lazyLoad);

    // Notification function
    function showNotification(message, type) {
        var notificationHtml = '<div class="sodabag-notification ' + type + '">' + message + '</div>';
        $('body').append(notificationHtml);
        setTimeout(function() {
            $('.sodabag-notification').fadeOut('slow', function() {
                $(this).remove();
            });
        }, 3000);
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
});

// HubSpot integration
jQuery(document).ready(function($) {
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
	
	$('#sodabag-check-llm-columns').on('click', function() {
    $.ajax({
        url: sodabag_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'sodabag_check_llm_database_columns',
            nonce: sodabag_ajax.nonce
        },
        success: function(response) {
            if (response.success) {
                alert(response.data.message);
            } else {
                alert('Error: ' + response.data.message);
            }
        }
    });
});

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
});