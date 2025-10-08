<?php
/**
 * Bulk Action Modal Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div id="rtai-bulk-modal" class="rtai-modal" style="display: none;">
    <div class="rtai-modal-content">
        <div class="rtai-modal-header">
            <h3><?php esc_html_e('Bulk AI Content Generation', 'rapidtextai-woocommerce'); ?></h3>
            <button type="button" class="rtai-modal-close">&times;</button>
        </div>
        
        <div class="rtai-modal-body">
            <div class="rtai-bulk-step" id="rtai-bulk-setup">
                <h4><?php esc_html_e('Select Content Types', 'rapidtextai-woocommerce'); ?></h4>
                <p><?php esc_html_e('Choose what content to generate for the selected products:', 'rapidtextai-woocommerce'); ?></p>
                
                <div class="rtai-bulk-artifacts">
                    <label class="rtai-artifact-option">
                        <input type="checkbox" name="bulk_artifacts[]" value="title" checked>
                        <div class="rtai-artifact-info">
                            <strong><?php esc_html_e('Product Titles', 'rapidtextai-woocommerce'); ?></strong>
                            <span><?php esc_html_e('Generate compelling product titles', 'rapidtextai-woocommerce'); ?></span>
                        </div>
                    </label>
                    
                    <label class="rtai-artifact-option">
                        <input type="checkbox" name="bulk_artifacts[]" value="short_description" checked>
                        <div class="rtai-artifact-info">
                            <strong><?php esc_html_e('Short Descriptions', 'rapidtextai-woocommerce'); ?></strong>
                            <span><?php esc_html_e('Brief, engaging product summaries', 'rapidtextai-woocommerce'); ?></span>
                        </div>
                    </label>
                    
                    <label class="rtai-artifact-option">
                        <input type="checkbox" name="bulk_artifacts[]" value="long_description">
                        <div class="rtai-artifact-info">
                            <strong><?php esc_html_e('Long Descriptions', 'rapidtextai-woocommerce'); ?></strong>
                            <span><?php esc_html_e('Detailed product descriptions', 'rapidtextai-woocommerce'); ?></span>
                        </div>
                    </label>
                    
                    <label class="rtai-artifact-option">
                        <input type="checkbox" name="bulk_artifacts[]" value="seo_title" checked>
                        <div class="rtai-artifact-info">
                            <strong><?php esc_html_e('SEO Titles', 'rapidtextai-woocommerce'); ?></strong>
                            <span><?php esc_html_e('Search engine optimized titles', 'rapidtextai-woocommerce'); ?></span>
                        </div>
                    </label>
                    
                    <label class="rtai-artifact-option">
                        <input type="checkbox" name="bulk_artifacts[]" value="seo_description" checked>
                        <div class="rtai-artifact-info">
                            <strong><?php esc_html_e('SEO Meta Descriptions', 'rapidtextai-woocommerce'); ?></strong>
                            <span><?php esc_html_e('Meta descriptions for search results', 'rapidtextai-woocommerce'); ?></span>
                        </div>
                    </label>
                    
                    <label class="rtai-artifact-option">
                        <input type="checkbox" name="bulk_artifacts[]" value="bullets">
                        <div class="rtai-artifact-info">
                            <strong><?php esc_html_e('Feature Bullets', 'rapidtextai-woocommerce'); ?></strong>
                            <span><?php esc_html_e('Key feature bullet points', 'rapidtextai-woocommerce'); ?></span>
                        </div>
                    </label>
                    
                    <label class="rtai-artifact-option">
                        <input type="checkbox" name="bulk_artifacts[]" value="faq">
                        <div class="rtai-artifact-info">
                            <strong><?php esc_html_e('FAQ Content', 'rapidtextai-woocommerce'); ?></strong>
                            <span><?php esc_html_e('Frequently asked questions', 'rapidtextai-woocommerce'); ?></span>
                        </div>
                    </label>
                </div>
                
                <div class="rtai-bulk-options">
                    <h4><?php esc_html_e('Generation Options', 'rapidtextai-woocommerce'); ?></h4>
                    
                    <div class="rtai-option-row">
                        <label for="bulk-tone"><?php esc_html_e('Tone:', 'rapidtextai-woocommerce'); ?></label>
                        <select id="bulk-tone" name="bulk_tone">
                            <option value=""><?php esc_html_e('Use default', 'rapidtextai-woocommerce'); ?></option>
                            <option value="professional"><?php esc_html_e('Professional', 'rapidtextai-woocommerce'); ?></option>
                            <option value="friendly"><?php esc_html_e('Friendly', 'rapidtextai-woocommerce'); ?></option>
                            <option value="casual"><?php esc_html_e('Casual', 'rapidtextai-woocommerce'); ?></option>
                            <option value="luxury"><?php esc_html_e('Luxury', 'rapidtextai-woocommerce'); ?></option>
                            <option value="technical"><?php esc_html_e('Technical', 'rapidtextai-woocommerce'); ?></option>
                            <option value="playful"><?php esc_html_e('Playful', 'rapidtextai-woocommerce'); ?></option>
                        </select>
                    </div>
                    
                    <div class="rtai-option-row">
                        <label for="bulk-audience"><?php esc_html_e('Target Audience:', 'rapidtextai-woocommerce'); ?></label>
                        <input type="text" id="bulk-audience" name="bulk_audience" 
                               placeholder="<?php esc_attr_e('e.g., fitness enthusiasts, professionals', 'rapidtextai-woocommerce'); ?>">
                    </div>
                    
                    <div class="rtai-option-row">
                        <label for="bulk-keywords"><?php esc_html_e('Focus Keywords:', 'rapidtextai-woocommerce'); ?></label>
                        <input type="text" id="bulk-keywords" name="bulk_keywords" 
                               placeholder="<?php esc_attr_e('keyword1, keyword2, keyword3', 'rapidtextai-woocommerce'); ?>">
                    </div>
                    
                    <div class="rtai-option-row">
                        <label>
                            <input type="checkbox" name="bulk_overwrite" value="1">
                            <?php esc_html_e('Overwrite existing content', 'rapidtextai-woocommerce'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('If unchecked, only products with empty fields will be processed.', 'rapidtextai-woocommerce'); ?>
                        </p>
                    </div>
                </div>
                
                <div class="rtai-bulk-summary">
                    <h4><?php esc_html_e('Summary', 'rapidtextai-woocommerce'); ?></h4>
                    <div class="rtai-summary-content">
                        <p>
                            <strong><?php esc_html_e('Products:', 'rapidtextai-woocommerce'); ?></strong> 
                            <span id="rtai-selected-count">0</span>
                        </p>
                        <p>
                            <strong><?php esc_html_e('Content Types:', 'rapidtextai-woocommerce'); ?></strong> 
                            <span id="rtai-artifact-count">0</span>
                        </p>
                        <p>
                            <strong><?php esc_html_e('Estimated Jobs:', 'rapidtextai-woocommerce'); ?></strong> 
                            <span id="rtai-job-count">0</span>
                        </p>
                        <p class="rtai-cost-estimate">
                            <strong><?php esc_html_e('Estimated Cost:', 'rapidtextai-woocommerce'); ?></strong> 
                            <span id="rtai-cost-estimate">$0.00</span>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="rtai-bulk-step" id="rtai-bulk-progress" style="display: none;">
                <h4><?php esc_html_e('Generation in Progress', 'rapidtextai-woocommerce'); ?></h4>
                <div class="rtai-progress-container">
                    <div class="rtai-progress-bar">
                        <div class="rtai-progress-fill" style="width: 0%;"></div>
                    </div>
                    <div class="rtai-progress-text">
                        <span id="rtai-progress-current">0</span> / 
                        <span id="rtai-progress-total">0</span> 
                        <?php esc_html_e('jobs completed', 'rapidtextai-woocommerce'); ?>
                    </div>
                </div>
                
                <div class="rtai-progress-details">
                    <div class="rtai-status-counts">
                        <div class="rtai-status-item">
                            <span class="rtai-status-label"><?php esc_html_e('Queued:', 'rapidtextai-woocommerce'); ?></span>
                            <span class="rtai-status-count" id="rtai-queued-count">0</span>
                        </div>
                        <div class="rtai-status-item">
                            <span class="rtai-status-label"><?php esc_html_e('Running:', 'rapidtextai-woocommerce'); ?></span>
                            <span class="rtai-status-count" id="rtai-running-count">0</span>
                        </div>
                        <div class="rtai-status-item">
                            <span class="rtai-status-label"><?php esc_html_e('Completed:', 'rapidtextai-woocommerce'); ?></span>
                            <span class="rtai-status-count" id="rtai-success-count">0</span>
                        </div>
                        <div class="rtai-status-item">
                            <span class="rtai-status-label"><?php esc_html_e('Failed:', 'rapidtextai-woocommerce'); ?></span>
                            <span class="rtai-status-count" id="rtai-failed-count">0</span>
                        </div>
                    </div>
                    
                    <div class="rtai-current-job">
                        <p><?php esc_html_e('Current:', 'rapidtextai-woocommerce'); ?> <span id="rtai-current-job"></span></p>
                    </div>
                </div>
                
                <div class="rtai-progress-actions">
                    <button type="button" class="button" id="rtai-pause-bulk">
                        <?php esc_html_e('Pause', 'rapidtextai-woocommerce'); ?>
                    </button>
                    <button type="button" class="button" id="rtai-cancel-bulk">
                        <?php esc_html_e('Cancel All', 'rapidtextai-woocommerce'); ?>
                    </button>
                </div>
            </div>
            
            <div class="rtai-bulk-step" id="rtai-bulk-complete" style="display: none;">
                <h4><?php esc_html_e('Generation Complete', 'rapidtextai-woocommerce'); ?></h4>
                
                <div class="rtai-completion-summary">
                    <div class="rtai-summary-stats">
                        <div class="rtai-stat-item success">
                            <div class="rtai-stat-number" id="rtai-final-success">0</div>
                            <div class="rtai-stat-label"><?php esc_html_e('Successful', 'rapidtextai-woocommerce'); ?></div>
                        </div>
                        <div class="rtai-stat-item failed">
                            <div class="rtai-stat-number" id="rtai-final-failed">0</div>
                            <div class="rtai-stat-label"><?php esc_html_e('Failed', 'rapidtextai-woocommerce'); ?></div>
                        </div>
                        <div class="rtai-stat-item total">
                            <div class="rtai-stat-number" id="rtai-final-total">0</div>
                            <div class="rtai-stat-label"><?php esc_html_e('Total Jobs', 'rapidtextai-woocommerce'); ?></div>
                        </div>
                    </div>
                    
                    <div class="rtai-completion-actions">
                        <button type="button" class="button button-primary" id="rtai-view-results">
                            <?php esc_html_e('View Generated Products', 'rapidtextai-woocommerce'); ?>
                        </button>
                        <button type="button" class="button" id="rtai-download-report">
                            <?php esc_html_e('Download Report', 'rapidtextai-woocommerce'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="rtai-failed-jobs" id="rtai-failed-jobs" style="display: none;">
                    <h5><?php esc_html_e('Failed Jobs', 'rapidtextai-woocommerce'); ?></h5>
                    <div class="rtai-failed-list"></div>
                </div>
            </div>
        </div>
        
        <div class="rtai-modal-footer">
            <div class="rtai-footer-step" id="rtai-footer-setup">
                <button type="button" class="button button-primary" id="rtai-start-bulk">
                    <?php esc_html_e('Start Generation', 'rapidtextai-woocommerce'); ?>
                </button>
                <button type="button" class="button rtai-modal-close">
                    <?php esc_html_e('Cancel', 'rapidtextai-woocommerce'); ?>
                </button>
            </div>
            
            <div class="rtai-footer-step" id="rtai-footer-progress" style="display: none;">
                <p class="rtai-progress-note">
                    <?php esc_html_e('Generation is running in the background. You can close this window and continue working.', 'rapidtextai-woocommerce'); ?>
                </p>
            </div>
            
            <div class="rtai-footer-step" id="rtai-footer-complete" style="display: none;">
                <button type="button" class="button button-primary rtai-modal-close">
                    <?php esc_html_e('Close', 'rapidtextai-woocommerce'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    var bulkModal = {
        selectedProducts: [],
        batchId: null,
        pollInterval: null,
        
        init: function() {
            this.bindEvents();
            this.updateSummary();
        },
        
        bindEvents: function() {
            var self = this;
            
            // Artifact selection
            $('input[name="bulk_artifacts[]"]').on('change', function() {
                self.updateSummary();
            });
            
            // Start generation
            $('#rtai-start-bulk').on('click', function() {
                self.startGeneration();
            });
            
            // Cancel generation
            $('#rtai-cancel-bulk').on('click', function() {
                self.cancelGeneration();
            });
            
            // Modal close
            $('.rtai-modal-close').on('click', function() {
                self.closeModal();
            });
            
            // View results
            $('#rtai-view-results').on('click', function() {
                self.viewResults();
            });
        },
        
        updateSummary: function() {
            var selectedArtifacts = $('input[name="bulk_artifacts[]"]:checked').length;
            var productCount = this.selectedProducts.length;
            var totalJobs = productCount * selectedArtifacts;
            var estimatedCost = totalJobs * 0.02; // Rough estimate
            
            $('#rtai-selected-count').text(productCount);
            $('#rtai-artifact-count').text(selectedArtifacts);
            $('#rtai-job-count').text(totalJobs);
            $('#rtai-cost-estimate').text('$' + estimatedCost.toFixed(2));
        },
        
        startGeneration: function() {
            var self = this;
            var selectedArtifacts = [];
            
            $('input[name="bulk_artifacts[]"]:checked').each(function() {
                selectedArtifacts.push($(this).val());
            });
            
            if (selectedArtifacts.length === 0) {
                alert('<?php esc_html_e('Please select at least one content type.', 'rapidtextai-woocommerce'); ?>');
                return;
            }
            
            var requestData = {
                action: 'rtai_wc_bulk_generate',
                nonce: rtaiWC.nonce,
                post_ids: this.selectedProducts,
                artifacts: selectedArtifacts,
                context_overrides: {
                    tone: $('#bulk-tone').val(),
                    audience: $('#bulk-audience').val(),
                    keywords: $('#bulk-keywords').val(),
                    overwrite: $('input[name="bulk_overwrite"]').is(':checked')
                }
            };
            
            $.ajax({
                url: rtaiWC.ajaxUrl,
                type: 'POST',
                data: requestData,
                beforeSend: function() {
                    $('#rtai-start-bulk').prop('disabled', true).text('<?php esc_html_e('Starting...', 'rapidtextai-woocommerce'); ?>');
                },
                success: function(response) {
                    if (response.success) {
                        self.batchId = response.data.batch_id;
                        self.showProgress();
                        self.startPolling();
                    } else {
                        alert('<?php esc_html_e('Failed to start generation:', 'rapidtextai-woocommerce'); ?> ' + response.data);
                        $('#rtai-start-bulk').prop('disabled', false).text('<?php esc_html_e('Start Generation', 'rapidtextai-woocommerce'); ?>');
                    }
                },
                error: function() {
                    alert('<?php esc_html_e('Error starting generation. Please try again.', 'rapidtextai-woocommerce'); ?>');
                    $('#rtai-start-bulk').prop('disabled', false).text('<?php esc_html_e('Start Generation', 'rapidtextai-woocommerce'); ?>');
                }
            });
        },
        
        showProgress: function() {
            $('#rtai-bulk-setup').hide();
            $('#rtai-footer-setup').hide();
            $('#rtai-bulk-progress').show();
            $('#rtai-footer-progress').show();
        },
        
        startPolling: function() {
            var self = this;
            
            this.pollInterval = setInterval(function() {
                self.checkProgress();
            }, 2000);
        },
        
        checkProgress: function() {
            var self = this;
            
            $.ajax({
                url: rtaiWC.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rtai_wc_get_job_status',
                    nonce: rtaiWC.nonce,
                    batch_id: this.batchId
                },
                success: function(response) {
                    if (response.success) {
                        self.updateProgress(response.data);
                        
                        if (response.data.is_complete) {
                            self.showComplete(response.data);
                        }
                    }
                }
            });
        },
        
        updateProgress: function(data) {
            var percentage = data.progress;
            
            $('.rtai-progress-fill').css('width', percentage + '%');
            $('#rtai-progress-current').text(data.completed);
            $('#rtai-progress-total').text(data.total);
            
            $('#rtai-queued-count').text(data.status_counts.queued || 0);
            $('#rtai-running-count').text(data.status_counts.running || 0);
            $('#rtai-success-count').text(data.status_counts.success || 0);
            $('#rtai-failed-count').text(data.status_counts.failed || 0);
        },
        
        showComplete: function(data) {
            clearInterval(this.pollInterval);
            
            $('#rtai-bulk-progress').hide();
            $('#rtai-footer-progress').hide();
            $('#rtai-bulk-complete').show();
            $('#rtai-footer-complete').show();
            
            $('#rtai-final-success').text(data.status_counts.success || 0);
            $('#rtai-final-failed').text(data.status_counts.failed || 0);
            $('#rtai-final-total').text(data.total);
            
            if ((data.status_counts.failed || 0) > 0) {
                $('#rtai-failed-jobs').show();
                this.loadFailedJobs();
            }
        },
        
        cancelGeneration: function() {
            var self = this;
            
            if (!confirm('<?php esc_html_e('Are you sure you want to cancel the generation?', 'rapidtextai-woocommerce'); ?>')) {
                return;
            }
            
            $.ajax({
                url: rtaiWC.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'rtai_wc_cancel_batch',
                    nonce: rtaiWC.nonce,
                    batch_id: this.batchId
                },
                success: function(response) {
                    clearInterval(self.pollInterval);
                    self.closeModal();
                }
            });
        },
        
        closeModal: function() {
            $('#rtai-bulk-modal').hide();
            if (this.pollInterval) {
                clearInterval(this.pollInterval);
            }
        },
        
        viewResults: function() {
            // Redirect to products page with filter
            window.location.href = 'edit.php?post_type=product&rtai_batch=' + this.batchId;
        },
        
        setSelectedProducts: function(productIds) {
            this.selectedProducts = productIds;
            this.updateSummary();
        }
    };
    
    // Initialize bulk modal
    window.rtaiBulkModal = bulkModal;
    bulkModal.init();
});
</script>