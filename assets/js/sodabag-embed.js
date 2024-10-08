var SodaBagEmbed = {
    config: {},

    load: function(config) {
        var container = document.getElementById(config.container);
        if (!container) return;

        // Load CSS
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = config.cssUrl;
        document.head.appendChild(link);

        // Load Font Awesome
        var faLink = document.createElement('link');
        faLink.rel = 'stylesheet';
        faLink.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css';
        document.head.appendChild(faLink);

        this.fetchStories(config, container);
    },

    fetchStories: function(config, container) {
        fetch(config.apiUrl + '?campaign_id=' + config.campaignId + '&limit=' + config.limit)
            .then(response => response.json())
            .then(stories => {
                this.render(container, stories, config);
                this.initEventListeners(container);
            })
            .catch(error => console.error('Error fetching stories:', error));
    },

    render: function(container, stories, config) {
        var html = '<div class="sodabag-story-display">';
        
        stories.forEach(function(story) {
            html += '<div class="sodabag-story" data-story-id="' + story.id + '">';
            html += '<div class="sodabag-story-media">';
            if (story.media_url) {
                if (story.media_url.endsWith('.mp4')) {
                    html += '<video src="' + story.media_url + '" controls></video>';
                } else {
                    html += '<img src="' + story.media_url + '" alt="Story media">';
                }
            }
            html += '</div>';
            html += '<div class="sodabag-story-overlay sodabag-story-overlay-top">';
            html += '<div class="sodabag-story-avatar">' + story.submitter_name.charAt(0).toUpperCase() + '</div>';
            html += '<div class="sodabag-story-name">' + this.formatUserName(story.submitter_name) + '</div>';
            html += '</div>';
            html += '<div class="sodabag-story-content">';
            html += '<p>' + this.getStoryExcerpt(story.content) + '</p>';
            html += '</div>';
            html += '</div>';
        }, this);
        
        html += '</div>';

        container.innerHTML = html;

        // Add lightbox HTML
        var lightboxHtml = '<div id="sodabag-lightbox" class="sodabag-lightbox">' +
            '<div class="sodabag-lightbox-content">' +
            '<span class="sodabag-lightbox-close">&times;</span>' +
            '<div class="sodabag-lightbox-media"></div>' +
            '<div class="sodabag-lightbox-info"></div>' +
            '</div></div>';
        document.body.insertAdjacentHTML('beforeend', lightboxHtml);
    },

    initEventListeners: function(container) {
        // Lightbox functionality
        container.querySelectorAll('.sodabag-story-media').forEach(function(media) {
            media.addEventListener('click', function() {
                var story = this.closest('.sodabag-story');
                var lightbox = document.getElementById('sodabag-lightbox');
                var lightboxMedia = lightbox.querySelector('.sodabag-lightbox-media');
                var lightboxInfo = lightbox.querySelector('.sodabag-lightbox-info');

                lightboxMedia.innerHTML = this.innerHTML;
                lightboxInfo.innerHTML = story.querySelector('.sodabag-story-overlay').innerHTML;
                lightbox.style.display = 'block';
            });
        });

        // Close lightbox
        var lightbox = document.getElementById('sodabag-lightbox');
        lightbox.querySelector('.sodabag-lightbox-close').addEventListener('click', function() {
            lightbox.style.display = 'none';
        });

        // Close lightbox when clicking outside the content
        lightbox.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    },

    loadSubmissionForm: function(config) {
        this.config = config;
        var container = document.getElementById(config.container);
        if (!container) return;

        // Load CSS
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = config.cssUrl;
        document.head.appendChild(link);

        // Load Font Awesome
        var faLink = document.createElement('link');
        faLink.rel = 'stylesheet';
        faLink.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css';
        document.head.appendChild(faLink);

        // Fetch and render the submission form
        this.fetchSubmissionForm(config, container);
    },

    fetchSubmissionForm: function(config, container) {
        var formHtml = `
            <form id="sodabag-story-form" class="sodabag-form" enctype="multipart/form-data">
                <input type="hidden" name="campaign_id" value="${config.campaignId}">
                <input type="hidden" name="action" value="sodabag_submit_story">
                <input type="text" name="submitter_name" placeholder="Your Name" required>
                <input type="email" name="submitter_email" placeholder="Your Email" required>
                <textarea name="content" placeholder="Your Story" required></textarea>
                <input type="file" name="media" accept="image/*,video/*">
        `;

        if (config.customFields && config.customFields.length > 0) {
            config.customFields.forEach(function(field) {
                var fieldHtml = '';
                switch (field.type) {
                    case 'text':
                        fieldHtml = `<input type="text" name="custom_fields[${field.name}]" placeholder="${field.name}" required>`;
                        break;
                    case 'textarea':
                        fieldHtml = `<textarea name="custom_fields[${field.name}]" placeholder="${field.name}" required></textarea>`;
                        break;
                    case 'number':
                        fieldHtml = `<input type="number" name="custom_fields[${field.name}]" placeholder="${field.name}" required>`;
                        break;
                    case 'checkbox':
                        fieldHtml = `
                            <div class="sodabag-custom-field-checkbox">
                                <input type="checkbox" id="custom_${field.name}" name="custom_fields[${field.name}]" value="1">
                                <label for="custom_${field.name}">${field.name}</label>
                            </div>
                        `;
                        break;
                }
                formHtml += fieldHtml;
            });
        }

        formHtml += `
                <div class="sodabag-terms-acceptance">
                    <input type="checkbox" id="terms_acceptance" name="terms_acceptance" required checked>
                    <label for="terms_acceptance">
                        I agree to the terms and conditions. 
                        <a href="#" class="sodabag-read-terms">Read Terms</a>
                    </label>
                </div>
                <button type="submit">Submit Story</button>
            </form>
            <div id="sodabag-terms-modal" class="sodabag-modal">
                <div class="sodabag-modal-content">
                    <span class="sodabag-modal-close">&times;</span>
                    <h3>Terms and Conditions</h3>
                    <div id="sodabag-terms-text"></div>
                </div>
            </div>
            <div id="sodabag-share-modal" class="sodabag-modal">
                <div class="sodabag-modal-content">
                    <span class="sodabag-modal-close">&times;</span>
                    <h3>Share Your Story</h3>
                    <p>Thank you for submitting your story! Would you like to share it?</p>
                    <div class="sodabag-share-buttons">
                        <button class="sodabag-share-button facebook" data-platform="facebook">
                            <i class="fab fa-facebook-f"></i> Facebook
                        </button>
                        <button class="sodabag-share-button twitter" data-platform="twitter">
                            <i class="fab fa-twitter"></i> Twitter
                        </button>
                        <button class="sodabag-share-button linkedin" data-platform="linkedin">
                            <i class="fab fa-linkedin-in"></i> LinkedIn
                        </button>
                        <button class="sodabag-share-button email" data-platform="email">
                            <i class="fas fa-envelope"></i> Email
                        </button>
                    </div>
                </div>
            </div>
        `;

        container.innerHTML = formHtml;

        // Set terms text
        var termsText = config.termsAcceptanceText || 'Please accept the terms and conditions.';
        container.querySelector('#sodabag-terms-text').innerHTML = termsText;

        this.initializeFormEventListeners(container);
    },

    initializeFormEventListeners: function(container) {
        var self = this;

        // Terms modal functionality
        var termsModal = container.querySelector('#sodabag-terms-modal');
        container.querySelector('.sodabag-read-terms').addEventListener('click', function(e) {
            e.preventDefault();
            termsModal.style.display = 'block';
        });

        container.querySelector('.sodabag-modal-close').addEventListener('click', function() {
            termsModal.style.display = 'none';
        });

        // Share modal functionality
        var shareModal = container.querySelector('#sodabag-share-modal');
        shareModal.querySelector('.sodabag-modal-close').addEventListener('click', function() {
            shareModal.style.display = 'none';
        });

        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target == termsModal) {
                termsModal.style.display = 'none';
            }
            if (event.target == shareModal) {
                shareModal.style.display = 'none';
            }
        });

       // Add event listeners for share buttons
var shareButtons = container.querySelectorAll('.sodabag-share-button');
shareButtons.forEach(function(button) {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var platform = this.dataset.platform;
        var storyId = shareModal.dataset.storyId;
        console.log('Share button clicked:', platform, storyId);
        self.handleShare(platform, storyId);
    });
});

        // Add event listener for form submission
var form = container.querySelector('#sodabag-story-form');
var shareModal = container.querySelector('#sodabag-share-modal');
form.addEventListener('submit', function(e) {
    e.preventDefault();
    var formData = new FormData(form);

    fetch(self.config.apiUrl, {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.data.message);
            if (data.data.show_share_modal) {
                shareModal.dataset.storyId = data.data.story_id;
                console.log('Setting storyId:', data.data.story_id);
                shareModal.style.display = 'block';
            }
            form.reset();
        } else {
            alert('Error: ' + data.data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
});

        // Add progress indicator
        var progressIndicator = document.createElement('div');
        progressIndicator.id = 'sodabag-progress-indicator';
        progressIndicator.style.display = 'none';
        progressIndicator.innerHTML = 'Submitting...';
        container.appendChild(progressIndicator);

        // Show/hide progress indicator
        form.addEventListener('submit', function() {
            progressIndicator.style.display = 'block';
        });

        // Hide progress indicator after submission (success or error)
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    if (mutation.addedNodes[0].nodeType === Node.TEXT_NODE && 
                        (mutation.addedNodes[0].textContent.includes('successfully') || 
                         mutation.addedNodes[0].textContent.includes('Error'))) {
                        progressIndicator.style.display = 'none';
                    }
                }
            });
        });

        observer.observe(container, { childList: true, subtree: true });
    },

    handleShare: function(platform, storyId) {
        var shareUrl = this.config.apiUrl + '?action=sodabag_share_story&story_id=' + storyId + '&platform=' + platform;
        
        fetch(shareUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                var shareData = data.data;
                var shareWindow;
                switch (platform) {
                    case 'facebook':
                        shareWindow = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(shareData.url);
                        break;
                    case 'twitter':
                        shareWindow = 'https://twitter.com/intent/tweet?text=' + encodeURIComponent(shareData.text) + '&url=' + encodeURIComponent(shareData.url);
                        break;
                    case 'linkedin':
                        shareWindow = 'https://www.linkedin.com/shareArticle?mini=true&url=' + encodeURIComponent(shareData.url) + '&title=' + encodeURIComponent(shareData.text);
                        break;
                    case 'email':
                        window.location.href = 'mailto:?subject=Check out this story&body=' + encodeURIComponent(shareData.text + '\n\n' + shareData.url);
                        return;
                }
                if (shareWindow) {
                    window.open(shareWindow, platform + '-share-dialog', 'width=626,height=436');
                }
            } else {
                console.error('Error sharing story:', data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    },

    formatDate: function(dateString) {
        var date = new Date(dateString);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    },

    formatUserName: function(fullName) {
        var names = fullName.split(' ');
        return names[0].toUpperCase() + (names[1] ? ' ' + names[1].charAt(0).toUpperCase() + '.' : '');
    },

    getStoryExcerpt: function(content, length = 100) {
        if (content.length > length) {
            return content.substring(0, length) + '...';
        }
        return content;
    }
};