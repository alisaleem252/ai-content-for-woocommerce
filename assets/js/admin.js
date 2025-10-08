/**
 * RapidTextAI for WooCommerce Admin JavaScript
 */

(function($) {
    'use strict';
    
    var RTAI_WC_Admin = {
        
        init: function() {
            this.bindEvents();
            this.initProductComposer();
            this.initBulkActions();
        },
        
        bindEvents: function() {
            // Tab switching in product meta box
            $(document).on('click', '.rtai-tab-button', this.switchTab);
            
            // Generate content
            $(document).on('click', '.rtai-generate-btn', this.generateContent);
            $(document).on('click', '.rtai-generate-seo-btn', this.generateSEOContent);
            $(document).on('click', '.rtai-translate-btn', this.translateContent);
            
            // Apply/rollback content
            $(document).on('click', '.rtai-apply-content', this.applyContent);
            $(document).on('click', '.rtai-edit-content', this.editContent);
            $(document).on('click', '.rtai-rollback-history', this.rollbackContent);
            $(document).on('click', '.rtai-view-history', this.viewHistory);
            
            // Streaming modal controls
            $(document).on('click', '.rtai-apply-stream', this.applyStreamContent);
            $(document).on('click', '.rtai-apply-all-streams', this.applyAllStreamContent);
            $(document).on('click', '.rtai-close-streams', this.closeStreamingModal);
            
            // Modal controls
            $(document).on('click', '.rtai-modal-close', this.closeModal);
            $(document).on('click', '.rtai-modal', function(e) {
                if (e.target === this) {
                    RTAI_WC_Admin.closeModal();
                }
            });
            
            // Bulk actions
            $(document).on('click', '#doaction, #doaction2', this.handleBulkAction);
            
            // Settings page
            $(document).on('click', '#rtai-test-connection', this.testConnection);
        },
        
        initProductComposer: function() {
            // Initialize any product-specific functionality
            if ($('.rtai-wc-composer').length) {
                this.loadProductHistory();
            }
        },
        
        initBulkActions: function() {
            // Add bulk action modal to products page
            if ($('body').hasClass('edit-php') && $('body').hasClass('post-type-product')) {
                $('body').append($('#rtai-bulk-modal'));
            }
        },
        
        switchTab: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var tabId = $button.data('tab');
            var $container = $button.closest('.rtai-tabs');
            
            // Update buttons
            $container.find('.rtai-tab-button').removeClass('active');
            $button.addClass('active');
            
            // Update content
            $container.find('.rtai-tab-content').removeClass('active');
            $container.find('#rtai-tab-' + tabId).addClass('active');
        },
        
        generateContent: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $container = $button.closest('.rtai-wc-composer');
            var postId = $container.data('post-id');
            
            // Get selected artifacts
            var artifacts = [];
            $container.find('input[name="artifacts[]"]:checked').each(function() {
                artifacts.push($(this).val());
            });
            
            if (artifacts.length === 0) {
                alert(rtaiWC.strings.error + ' Please select at least one content type.');
                return;
            }
            
            // Get context overrides
            var contextOverrides = {
                audience: $container.find('input[name="audience"]').val(),
                tone: $container.find('select[name="tone"]').val(),
                keywords: $container.find('input[name="keywords"]').val(),
                features: $container.find('textarea[name="features"]').val()
            };
            
            // Show streaming modal immediately
            RTAI_WC_Admin.showStreamingModal(artifacts);
            
            // Start streaming generation for each artifact
            artifacts.forEach(function(artifact) {
                RTAI_WC_Admin.generateStreamingContent(postId, artifact, contextOverrides);
            });
        },
        
        generateStreamingContent: function(postId, artifact, contextOverrides) {
            // First get streaming configuration from server
            $.ajax({
                url: rtaiWC.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rtai_wc_get_stream_config',
                    nonce: rtaiWC.nonce,
                    post_id: postId,
                    artifact: artifact,
                    context_overrides: contextOverrides
                },
                success: function(response) {
                    if (response.success && response.data.stream_url) {
                        RTAI_WC_Admin.startEventStream(artifact, response.data);
                    } else {
                        RTAI_WC_Admin.handleStreamError(artifact, response.data || 'Failed to get stream configuration');
                    }
                },
                error: function() {
                    RTAI_WC_Admin.handleStreamError(artifact, 'Network error');
                }
            });
        },
        
        startEventStream: function(artifact, streamConfig) {
            var $modal = $('#rtai-streaming-modal');
            var $content = $modal.find('.rtai-stream-content[data-artifact="' + artifact + '"]');
            
            // Mark as started
            $content.addClass('streaming').find('.rtai-stream-status').text('Generating...');
            
            var fullContent = '';
            
            // Use fetch with streaming instead of EventSource for better control
            fetch(streamConfig.stream_url, {
                method: streamConfig.method || 'POST',
                headers: {
                    'Authorization': streamConfig.headers.Authorization,
                    'Content-Type': 'application/json',
                    'Accept': 'text/event-stream',
                    'Cache-Control': 'no-cache'
                },
                body: streamConfig.body
            }).then(function(response) {
                if (!response.ok) {
                    throw new Error('Stream request failed');
                }
                
                var reader = response.body.getReader();
                var decoder = new TextDecoder();
                
                function readStream() {
                    return reader.read().then(function(result) {
                        if (result.done) {
                            RTAI_WC_Admin.handleStreamComplete(artifact, fullContent, {});
                            return;
                        }
                        
                        var chunk = decoder.decode(result.value, { stream: true });
                        var lines = chunk.split('\n');
                        
                        lines.forEach(function(line) {
                            if (line.startsWith('data: ')) {
                                try {
                                    var data = JSON.parse(line.slice(6));
                                    
                                    if (data.choices && data.choices[0] && data.choices[0].delta && data.choices[0].delta.content) {
                                        var content = data.choices[0].delta.content;
                                        
                                        fullContent += content;
                                        // Update the preview in real-time
                                        var $preview = $content.find('.rtai-stream-preview');
                                        $preview.text(fullContent);
                                        
                                        // Auto-scroll to bottom
                                        $preview.scrollTop($preview[0].scrollHeight);
                                    }
                                    
                                    // Check if stream is done
                                    if (data.choices && data.choices[0] && data.choices[0].finish_reason) {
                                        RTAI_WC_Admin.handleStreamComplete(artifact, fullContent, data);
                                        return;
                                    }
                                } catch (error) {
                                    console.error('Error parsing stream data:', error);
                                }
                            }
                        });
                        
                        return readStream();
                    });
                }
                
                return readStream();
            }).catch(function(error) {
                console.error('Stream error:', error);
                RTAI_WC_Admin.handleStreamError(artifact, 'Stream connection error: ' + error.message);
            });
            
            // Set timeout for safety
            setTimeout(function() {
                if ($content.hasClass('streaming')) {
                    RTAI_WC_Admin.handleStreamError(artifact, 'Stream timeout');
                }
            }, 120000); // 2 minute timeout
        },
        
        handleStreamComplete: function(artifact, content, streamData) {
            var $modal = $('#rtai-streaming-modal');
            var $content = $modal.find('.rtai-stream-content[data-artifact="' + artifact + '"]');
            
            $content.removeClass('streaming').addClass('complete');
            $content.find('.rtai-stream-status').text('Complete');
            
            // Store the final content
            $content.data('finalContent', content);
            $content.data('streamData', streamData);
            
            // Enable apply button
            $content.find('.rtai-apply-stream').prop('disabled', false);
            
            // Check if all streams are complete
            var $allContents = $modal.find('.rtai-stream-content');
            var allComplete = true;
            $allContents.each(function() {
                if (!$(this).hasClass('complete') && !$(this).hasClass('error')) {
                    allComplete = false;
                    return false;
                }
            });
            
            if (allComplete) {
                $modal.find('.rtai-apply-all-streams').prop('disabled', false);
            }
        },
        
        handleStreamError: function(artifact, error) {
            var $modal = $('#rtai-streaming-modal');
            var $content = $modal.find('.rtai-stream-content[data-artifact="' + artifact + '"]');
            
            $content.removeClass('streaming').addClass('error');
            $content.find('.rtai-stream-status').text('Error: ' + error);
            $content.find('.rtai-stream-preview').text('Generation failed. Please try again.');
        },
        
        showStreamingModal: function(artifacts) {
            var $modal = $('#rtai-streaming-modal');
            if (!$modal.length) {
                // Create streaming modal if it doesn't exist
                $modal = RTAI_WC_Admin.createStreamingModal();
                $('body').append($modal);
            }
            
            var $container = $modal.find('.rtai-streaming-container');
            $container.empty();
            
            // Create a content area for each artifact
            artifacts.forEach(function(artifact) {
                var $content = $('<div class="rtai-stream-content" data-artifact="' + artifact + '">');
                
                var $header = $('<div class="rtai-stream-header">');
                $header.append('<h4>' + RTAI_WC_Admin.formatArtifactName(artifact) + '</h4>');
                $header.append('<span class="rtai-stream-status">Initializing...</span>');
                
                var $preview = $('<div class="rtai-stream-preview">Waiting for content...</div>');
                
                var $actions = $('<div class="rtai-stream-actions">');
                $actions.append('<button type="button" class="button rtai-apply-stream" data-artifact="' + artifact + '" disabled>Apply</button>');
                
                $content.append($header).append($preview).append($actions);
                $container.append($content);
            });
            
            // Add global actions
            var $globalActions = $('<div class="rtai-global-actions">');
            $globalActions.append('<button type="button" class="button button-primary rtai-apply-all-streams" disabled>Apply All</button>');
            $globalActions.append('<button type="button" class="button rtai-close-streams">Close</button>');
            $container.append($globalActions);
            
            $modal.show();
        },
        
        createStreamingModal: function() {
            return $('<div id="rtai-streaming-modal" class="rtai-modal" style="display: none;">' +
                '<div class="rtai-modal-content rtai-streaming-modal-content">' +
                    '<div class="rtai-modal-header">' +
                        '<h3>Generating Content</h3>' +
                        '<button type="button" class="rtai-modal-close">&times;</button>' +
                    '</div>' +
                    '<div class="rtai-streaming-container"></div>' +
                '</div>' +
            '</div>');
        },
        
        generateSEOContent: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $container = $button.closest('.rtai-wc-composer');
            var postId = $container.data('post-id');
            
            // Get selected SEO artifacts
            var artifacts = [];
            $container.find('input[name="seo_artifacts[]"]:checked').each(function() {
                artifacts.push($(this).val());
            });
            
            if (artifacts.length === 0) {
                alert('Please select at least one SEO content type.');
                return;
            }
            
            // Get SEO-specific context
            var contextOverrides = {
                focus_keywords: $container.find('input[name="focus_keywords"]').val(),
                competitor_analysis: $container.find('textarea[name="competitor_analysis"]').val()
            };
            
            RTAI_WC_Admin.performGeneration($button, $container, postId, artifacts, contextOverrides);
        },
        
        translateContent: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $container = $button.closest('.rtai-wc-composer');
            var postId = $container.data('post-id');
            
            // Get translation settings
            var contextOverrides = {
                target_language: $container.find('select[name="target_language"]').val(),
                formality: $container.find('select[name="formality"]').val(),
                translation_mode: true
            };
            
            var artifacts = [];
            $container.find('input[name="translation_artifacts[]"]:checked').each(function() {
                artifacts.push($(this).val());
            });
            
            if (artifacts.length === 0) {
                alert('Please select content to translate.');
                return;
            }
            
            RTAI_WC_Admin.performGeneration($button, $container, postId, ['translations'], contextOverrides);
        },
        
        performGeneration: function($button, $container, postId, artifacts, contextOverrides) {
            // Show loading
            $button.prop('disabled', true).text('Generating...');
            
            $.ajax({
                url: rtaiWC.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rtai_wc_generate_content',
                    nonce: rtaiWC.nonce,
                    post_id: postId,
                    artifacts: artifacts,
                    context_overrides: contextOverrides
                },
                success: function(response) {
                    if (response.success) {
                        RTAI_WC_Admin.showResults(response.data, $container);
                    } else {
                        alert('Generation failed: ' + response.data);
                    }
                },
                error: function() {
                    alert('Error occurred during generation.');
                },
                complete: function() {
                    $button.prop('disabled', false).text($button.data('original-text') || 'Generate');
                }
            });
        },
        
        showResults: function(results, $container) {
            var $modal = $('#rtai-results-modal');
            var $preview = $modal.find('.rtai-content-preview');
            
            // Clear previous content
            $preview.empty();
            
            // Build results HTML
            var hasResults = false;
            $.each(results, function(artifact, result) {
                if (result.success) {
                    hasResults = true;
                    var $item = $('<div class="rtai-result-item">');
                    
                    var $header = $('<div class="rtai-result-header">');
                    $header.append('<div class="rtai-result-title">' + RTAI_WC_Admin.formatArtifactName(artifact) + '</div>');
                    $header.append('<div class="rtai-result-meta">' + result.tokens + ' tokens, ' + result.model + '</div>');
                    
                    var $content = $('<div class="rtai-result-content">').text(result.content);
                    
                    var $actions = $('<div class="rtai-result-actions">');
                    $actions.append('<button type="button" class="button button-primary rtai-apply-single" data-artifact="' + artifact + '" data-content="' + RTAI_WC_Admin.escapeHtml(result.content) + '">Apply</button>');
                    $actions.append('<button type="button" class="button rtai-edit-single" data-artifact="' + artifact + '" data-content="' + RTAI_WC_Admin.escapeHtml(result.content) + '">Edit</button>');
                    
                    $item.append($header).append($content).append($actions);
                    $preview.append($item);
                }
            });
            
            if (hasResults) {
                $modal.show();
                
                // Store results for batch apply
                $modal.data('results', results);
                $modal.data('container', $container);
            } else {
                alert('No content was generated successfully.');
            }
        },
        
        applyContent: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $modal = $button.closest('.rtai-modal');
            var results = $modal.data('results');
            var $container = $modal.data('container');
            var postId = $container.data('post-id');
            
            if (!confirm(rtaiWC.strings.confirm_apply)) {
                return;
            }
            
            $button.prop('disabled', true).text('Applying...');
            
            var promises = [];
            
            // Apply all successful results
            $.each(results, function(artifact, result) {
                if (result.success) {
                    var promise = RTAI_WC_Admin.applySingleContent(postId, artifact, result.content);
                    promises.push(promise);
                }
            });
            
            // Wait for all applications to complete
            Promise.all(promises).then(function() {
                alert(rtaiWC.strings.success);
                RTAI_WC_Admin.closeModal();
                RTAI_WC_Admin.refreshProductEditor();
                RTAI_WC_Admin.loadProductHistory();
            }).catch(function(error) {
                alert('Error applying content: ' + error);
            }).finally(function() {
                $button.prop('disabled', false).text('Apply to Product');
            });
        },
        
        applySingleContent: function(postId, artifact, content) {
            
            // Apply content to the actual product editor fields
            switch(artifact) {
                case 'title':
                    // Update title in both classic and Gutenberg editors
                    if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch && wp.data.dispatch('core/editor') !== null) {
                        // Gutenberg editor
                        wp.data.dispatch('core/editor').editPost({ title: content });
                    }
                    // Classic editor and general title field
                    $('#title, input[name="post_title"]').val(content);
                    break;
                    
                case 'description':
                case 'long_description':

                                        
                                        
                    // Convert markdown content to HTML if marked library is available
                    if (typeof marked !== 'undefined') {
                        content = marked.parse(content);
                    }
                    // Update main content editor
                    if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch && wp.data.dispatch('core/editor') !== null) {
                        // Gutenberg editor
                        wp.data.dispatch('core/editor').editPost({ content: content });
                    } else if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor) {
                        // TinyMCE classic editor
                        tinyMCE.activeEditor.setContent(content);
                    }
                    // Fallback to textarea
                    $('#content, textarea[name="content"]').val(content);
                    break;
                    
                case 'short_description':

                    // Convert markdown content to HTML if marked library is available
                    if (typeof marked !== 'undefined') {
                        content = marked.parse(content);
                    }
                    // Update excerpt/short description
                    if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch && wp.data.dispatch('core/editor') !== null) {
                        // Gutenberg editor
                       wp.data.dispatch('core/editor').editPost({ excerpt: content });
                    }
                    // WooCommerce short description field
                    var $shortDesc = $('#woocommerce-product-data textarea[name="_product_short_description"]');
                    if ($shortDesc.length && typeof tinyMCE !== 'undefined') {
                        var shortDescEditor = tinyMCE.get('_product_short_description');
                        if (shortDescEditor) {
                            shortDescEditor.setContent(content);
                        } else {
                            $shortDesc.val(content);
                        }
                    }
                    // General excerpt field
                    $('#excerpt, textarea[name="excerpt"]').val(content);
                    break;
                    
                case 'meta_title':
                case 'seo_title':
                    // Update SEO title fields (Yoast, RankMath, etc.)
                    $('input[name="_yoast_wpseo_title"], input[name="rank_math_title"]').val(content).trigger('input');
                    break;
                    
                case 'meta_description':
                case 'seo_description':
                    // Update SEO meta description
                    $('textarea[name="_yoast_wpseo_metadesc"], textarea[name="rank_math_description"]').val(content).trigger('input');
                    break;
                    
                case 'tags':
                    // Update product tags
                    if (content) {
                        var tags = content.split(',').map(function(tag) { return tag.trim(); });
                        $('#new-tag-product_tag').val(tags.join(', '));
                        // Trigger tag addition if possible
                        if ($('#product_tag .button-add-tag').length) {
                            tags.forEach(function(tag) {
                                $('#new-tag-product_tag').val(tag);
                                $('#product_tag .button-add-tag').click();
                            });
                        }
                    }
                    break;
                    
                default:
                    // For custom artifacts, try to find matching fields
                    var fieldSelectors = [
                        'input[name="' + artifact + '"]',
                        'textarea[name="' + artifact + '"]',
                        'input[name="_' + artifact + '"]',
                        'textarea[name="_' + artifact + '"]',
                        '#' + artifact
                    ];
                    
                    fieldSelectors.forEach(function(selector) {
                        var $field = $(selector);
                        if ($field.length) {
                            $field.val(content).trigger('change');
                        }
                    });
                    break;
            }

            // Trigger change events to ensure other plugins notice the updates
            $(document).trigger('rtai_content_applied', {
                artifact: artifact,
                content: content,
                postId: postId
            });
            return new Promise(function(resolve, reject) {
                $.ajax({
                    url: rtaiWC.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'rtai_wc_apply_content',
                        nonce: rtaiWC.nonce,
                        post_id: postId,
                        artifact: artifact,
                        content: content
                    },
                    success: function(response) {
                        if (response.success) {
                            resolve();
                        } else {
                            reject(response.data);
                        }
                    },
                    error: function() {
                        reject('AJAX error');
                    }
                });
            });
        },
        
        editContent: function(e) {
            e.preventDefault();
            
            // Switch to edit mode - could open editor or inline editing
            // For now, just show alert
            alert('Edit functionality would open an editor here.');
        },
        
        rollbackContent: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var historyId = $button.data('history-id');
            var $container = $button.closest('.rtai-wc-composer');
            var postId = $container.data('post-id');
            
            if (!confirm(rtaiWC.strings.confirm_rollback)) {
                return;
            }
            
            $button.prop('disabled', true);
            
            $.ajax({
                url: rtaiWC.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rtai_wc_rollback_content',
                    nonce: rtaiWC.nonce,
                    post_id: postId,
                    history_id: historyId
                },
                success: function(response) {
                    if (response.success) {
                        alert('Content rolled back successfully.');
                        RTAI_WC_Admin.refreshProductEditor();
                        RTAI_WC_Admin.loadProductHistory();
                    } else {
                        alert('Rollback failed: ' + response.data);
                    }
                },
                error: function() {
                    alert('Error during rollback.');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        },
        
        viewHistory: function(e) {
            e.preventDefault();
            
            var historyId = $(this).data('history-id');
            // Implementation for viewing full history item
            alert('View history item: ' + historyId);
        },
        
        loadProductHistory: function() {
            var $container = $('.rtai-wc-composer');
            if (!$container.length) return;
            
            var postId = $container.data('post-id');
            if (!postId) return;
            
            $.ajax({
                url: rtaiWC.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rtai_wc_get_history',
                    nonce: rtaiWC.nonce,
                    post_id: postId
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        RTAI_WC_Admin.renderHistory(response.data);
                    }
                }
            });
        },
        
        renderHistory: function(history) {
            var $historyTab = $('#rtai-tab-history');
            var $list = $historyTab.find('.rtai-history-list');
            
            if (!$list.length) {
                $list = $('<div class="rtai-history-list">');
                $historyTab.find('.rtai-no-history').replaceWith($list);
            }
            
            $list.empty();
            
            $.each(history.slice(0, 10), function(index, entry) {
                var $item = $('<div class="rtai-history-item">');
                
                var $header = $('<div class="rtai-history-header">');
                $header.append('<strong>' + RTAI_WC_Admin.formatArtifactName(entry.artifact) + '</strong>');
                $header.append('<span class="rtai-history-date">' + RTAI_WC_Admin.formatDate(entry.timestamp) + '</span>');
                
                var $preview = $('<div class="rtai-history-preview">').text(RTAI_WC_Admin.truncateText(entry.output, 100));
                
                var $meta = $('<div class="rtai-history-meta">');
                $meta.append('<span class="rtai-model">' + entry.model + '</span>');
                $meta.append('<span class="rtai-tokens">' + entry.tokens + ' tokens</span>');
                if (entry.cost_estimate) {
                    $meta.append('<span class="rtai-cost">$' + parseFloat(entry.cost_estimate).toFixed(4) + '</span>');
                }
                
                var $actions = $('<div class="rtai-history-actions">');
                $actions.append('<button type="button" class="button rtai-view-history" data-history-id="' + entry.id + '">View</button>');
                $actions.append('<button type="button" class="button rtai-rollback-history" data-history-id="' + entry.id + '">Rollback</button>');
                
                $item.append($header).append($preview).append($meta).append($actions);
                $list.append($item);
            });
        },
        
        handleBulkAction: function(e) {
            var action = $(this).closest('.tablenav').find('select[name="action"]').val();
            if (action === 'rtai_generate_content') {
                e.preventDefault();
                alert('Bulk content generation would open a modal here.');
                var selectedIds = [];
                $('input[name="post[]"]:checked').each(function() {
                    selectedIds.push(parseInt($(this).val()));
                });
                
                if (selectedIds.length === 0) {
                    alert('Please select at least one product.');
                    return;
                }
                
                // Show bulk modal
                if (window.rtaiBulkModal) {
                    window.rtaiBulkModal.setSelectedProducts(selectedIds);
                    $('#rtai-bulk-modal').show();
                }
            }
        },
        
        testConnection: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            $button.prop('disabled', true).text('Testing...');
            
            $.ajax({
                url: rtaiWC.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rtai_wc_test_connection',
                    nonce: rtaiWC.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Connection successful! Connected to RapidTextAI.');
                    } else {
                        alert('Connection failed: ' + response.data);
                    }
                },
                error: function() {
                    alert('Error testing connection.');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Test Connection');
                }
            });
        },
        
        closeModal: function() {
            $('.rtai-modal').hide();
            RTAI_WC_Admin.cleanupStreams();
        },
        
        closeStreamingModal: function() {
            $('#rtai-streaming-modal').hide();
            RTAI_WC_Admin.cleanupStreams();
        },
        
        cleanupStreams: function() {
            // Close any active streams by marking them as complete
            $('#rtai-streaming-modal .rtai-stream-content.streaming').each(function() {
                $(this).removeClass('streaming').addClass('error');
                $(this).find('.rtai-stream-status').text('Cancelled');
            });
        },
        
        applyStreamContent: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var artifact = $button.data('artifact');
            var $content = $button.closest('.rtai-stream-content');
            var content = $content.data('finalContent');
            var postId = $('.rtai-wc-composer').data('post-id');
            
            if (!content) {
                alert('No content to apply');
                return;
            }
            
            $button.prop('disabled', true).text('Applying...');
            
            RTAI_WC_Admin.applySingleContent(postId, artifact, content).then(function() {
                $button.text('Applied').addClass('applied');
                alert('Content applied successfully');
            }).catch(function(error) {
                alert('Error applying content: ' + error);
                $button.prop('disabled', false).text('Apply');
            });
        },
        
        applyAllStreamContent: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $modal = $('#rtai-streaming-modal');
            var postId = $('.rtai-wc-composer').data('post-id');
            
            if (!confirm('Apply all generated content to the product?')) {
                return;
            }
            
            $button.prop('disabled', true).text('Applying All...');
            
            var promises = [];
            
            $modal.find('.rtai-stream-content.complete').each(function() {
                var $content = $(this);
                var artifact = $content.data('artifact');
                var content = $content.data('finalContent');
                
                if (content) {
                    var promise = RTAI_WC_Admin.applySingleContent(postId, artifact, content);
                    promises.push(promise);
                }
            });
            
            Promise.all(promises).then(function() {
                alert('All content applied successfully');
                RTAI_WC_Admin.closeStreamingModal();
                RTAI_WC_Admin.refreshProductEditor();
                RTAI_WC_Admin.loadProductHistory();
            }).catch(function(error) {
                alert('Error applying content: ' + error);
            }).finally(function() {
                $button.prop('disabled', false).text('Apply All');
            });
        },
        
        refreshProductEditor: function() {
            // Refresh content in product editor if possible
            if (typeof wp !== 'undefined' && wp.data && wp.data.select) {
                // Gutenberg editor
                var postId = wp.data.select('core/editor').getCurrentPostId();
                if (postId) {
                    wp.data.dispatch('core').invalidateResolution('getEditedEntityRecord', ['postType', 'product', postId]);
                }
            } else {
                // Classic editor - would need to refresh manually
                location.reload();
            }
        },
        
        // Utility functions
        formatArtifactName: function(artifact) {
            return artifact.split('_').map(function(word) {
                return word.charAt(0).toUpperCase() + word.slice(1);
            }).join(' ');
        },
        
        formatDate: function(dateString) {
            var date = new Date(dateString);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
        },
        
        truncateText: function(text, maxLength) {
            if (text.length <= maxLength) return text;
            return text.substr(0, maxLength) + '...';
        },
        
        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        RTAI_WC_Admin.init();
    });
    
    // Expose to global scope for external access
    window.RTAI_WC_Admin = RTAI_WC_Admin;
    
})(jQuery);