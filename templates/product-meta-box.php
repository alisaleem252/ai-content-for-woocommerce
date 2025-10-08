<?php
/**
 * Product Meta Box Template
 */

if (!defined('ABSPATH')) {
    exit;
}

wp_nonce_field('rtai_wc_product_meta', 'rtai_wc_product_nonce');
?>

<div class="rtai-wc-composer" data-post-id="<?php echo esc_attr($post->ID); ?>">
    
    <?php if (!$is_connected): ?>
        <div class="rtai-connect-notice">
            <p>
                <strong><?php esc_html_e('Connect to RapidTextAI', 'ai-content-for-woocommerce'); ?></strong><br>
                <?php esc_html_e('Connect your RapidTextAI account to start generating content.', 'ai-content-for-woocommerce'); ?>
            </p>
            <a href="<?php echo esc_url(admin_url('options-general.php?page=rtai-wc-settings')); ?>" class="button button-primary">
                <?php esc_html_e('Setup Connection', 'ai-content-for-woocommerce'); ?>
            </a>
        </div>
    <?php else: ?>
        
        <div class="rtai-tabs">
            <div class="rtai-tab-nav">
                <button type="button" class="rtai-tab-button active" data-tab="write">
                    <?php esc_html_e('Write', 'ai-content-for-woocommerce'); ?>
                </button>
                <button type="button" class="rtai-tab-button" data-tab="seo">
                    <?php esc_html_e('SEO', 'ai-content-for-woocommerce'); ?>
                </button>
                <button type="button" class="rtai-tab-button" data-tab="translate">
                    <?php esc_html_e('Translate', 'ai-content-for-woocommerce'); ?>
                </button>
                <button type="button" class="rtai-tab-button" data-tab="history">
                    <?php esc_html_e('History', 'ai-content-for-woocommerce'); ?>
                </button>
            </div>
            
            <!-- Write Tab -->
            <div class="rtai-tab-content active" id="rtai-tab-write">
                <div class="rtai-generation-options">
                    <h4><?php esc_html_e('Generate Content', 'ai-content-for-woocommerce'); ?></h4>
                    
                    <div class="rtai-artifacts">
                        <label>
                            <input type="checkbox" name="artifacts[]" value="title" checked>
                            <?php esc_html_e('Product Title', 'ai-content-for-woocommerce'); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="artifacts[]" value="short_description" checked>
                            <?php esc_html_e('Short Description', 'ai-content-for-woocommerce'); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="artifacts[]" value="long_description" checked>
                            <?php esc_html_e('Long Description', 'ai-content-for-woocommerce'); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="artifacts[]" value="bullets">
                            <?php esc_html_e('Feature Bullets', 'ai-content-for-woocommerce'); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="artifacts[]" value="faq">
                            <?php esc_html_e('FAQ Content', 'ai-content-for-woocommerce'); ?>
                        </label>
                    </div>
                    
                    <div class="rtai-context-controls">
                        <div class="rtai-field">
                            <label for="rtai-audience"><?php esc_html_e('Target Audience', 'ai-content-for-woocommerce'); ?></label>
                            <input type="text" id="rtai-audience" name="audience" 
                                   placeholder="<?php esc_attr_e('e.g., fitness enthusiasts, professionals, parents', 'ai-content-for-woocommerce'); ?>">
                        </div>
                        
                        <div class="rtai-field">
                            <label for="rtai-tone"><?php esc_html_e('Tone', 'ai-content-for-woocommerce'); ?></label>
                            <select id="rtai-tone" name="tone">
                                <option value=""><?php esc_html_e('Use default', 'ai-content-for-woocommerce'); ?></option>
                                <option value="professional"><?php esc_html_e('Professional', 'ai-content-for-woocommerce'); ?></option>
                                <option value="friendly"><?php esc_html_e('Friendly', 'ai-content-for-woocommerce'); ?></option>
                                <option value="casual"><?php esc_html_e('Casual', 'ai-content-for-woocommerce'); ?></option>
                                <option value="luxury"><?php esc_html_e('Luxury', 'ai-content-for-woocommerce'); ?></option>
                                <option value="technical"><?php esc_html_e('Technical', 'ai-content-for-woocommerce'); ?></option>
                                <option value="playful"><?php esc_html_e('Playful', 'ai-content-for-woocommerce'); ?></option>
                            </select>
                        </div>
                        
                        <div class="rtai-field">
                            <label for="rtai-keywords"><?php esc_html_e('Keywords', 'ai-content-for-woocommerce'); ?></label>
                            <input type="text" id="rtai-keywords" name="keywords" 
                                   placeholder="<?php esc_attr_e('keyword1, keyword2, keyword3', 'ai-content-for-woocommerce'); ?>">
                        </div>
                        
                        <div class="rtai-field">
                            <label for="rtai-features"><?php esc_html_e('Key Features', 'ai-content-for-woocommerce'); ?></label>
                            <textarea id="rtai-features" name="features" rows="2" 
                                      placeholder="<?php esc_attr_e('Highlight specific features or benefits to emphasize', 'ai-content-for-woocommerce'); ?>"></textarea>
                        </div>
                    </div>
                    
                    <div class="rtai-actions">
                        <button type="button" class="button button-primary rtai-generate-btn">
                            <span class="dashicons dashicons-admin-generic"></span>
                            <?php esc_html_e('Generate Content', 'ai-content-for-woocommerce'); ?>
                        </button>
                        <span class="rtai-loading" style="display: none;">
                            <span class="spinner is-active"></span>
                            <?php esc_html_e('Generating...', 'ai-content-for-woocommerce'); ?>
                        </span>
                    </div>
                </div>
                
                <div class="rtai-results" style="display: none;">
                    <h4><?php esc_html_e('Generated Content', 'ai-content-for-woocommerce'); ?></h4>
                    <div class="rtai-results-content"></div>
                </div>
            </div>
            
            <!-- SEO Tab -->
            <div class="rtai-tab-content" id="rtai-tab-seo">
                <div class="rtai-seo-generation">
                    <h4><?php esc_html_e('SEO Content Generation', 'ai-content-for-woocommerce'); ?></h4>
                    
                    <div class="rtai-artifacts">
                        <label>
                            <input type="checkbox" name="seo_artifacts[]" value="seo_title" checked>
                            <?php esc_html_e('SEO Title', 'ai-content-for-woocommerce'); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="seo_artifacts[]" value="seo_description" checked>
                            <?php esc_html_e('Meta Description', 'ai-content-for-woocommerce'); ?>
                        </label>
                    </div>
                    
                    <div class="rtai-seo-controls">
                        <div class="rtai-field">
                            <label for="rtai-focus-keywords"><?php esc_html_e('Focus Keywords', 'ai-content-for-woocommerce'); ?></label>
                            <input type="text" id="rtai-focus-keywords" name="focus_keywords" 
                                   placeholder="<?php esc_attr_e('primary keyword, secondary keyword', 'ai-content-for-woocommerce'); ?>">
                        </div>
                        
                        <div class="rtai-field">
                            <label for="rtai-competitor-analysis"><?php esc_html_e('Competitor Products', 'ai-content-for-woocommerce'); ?></label>
                            <textarea id="rtai-competitor-analysis" name="competitor_analysis" rows="2" 
                                      placeholder="<?php esc_attr_e('URLs or names of competing products for differentiation', 'ai-content-for-woocommerce'); ?>"></textarea>
                        </div>
                    </div>
                    
                    <div class="rtai-actions">
                        <button type="button" class="button button-primary rtai-generate-seo-btn">
                            <span class="dashicons dashicons-search"></span>
                            <?php esc_html_e('Generate SEO Content', 'ai-content-for-woocommerce'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="rtai-seo-results" style="display: none;">
                    <h4><?php esc_html_e('Generated SEO Content', 'ai-content-for-woocommerce'); ?></h4>
                    <div class="rtai-seo-results-content"></div>
                </div>
            </div>
            
            <!-- Translate Tab -->
            <div class="rtai-tab-content" id="rtai-tab-translate">
                <div class="rtai-translation">
                    <h4><?php esc_html_e('Content Translation', 'ai-content-for-woocommerce'); ?></h4>
                    
                    <div class="rtai-field">
                        <label for="rtai-target-language"><?php esc_html_e('Target Language', 'ai-content-for-woocommerce'); ?></label>
                        <select id="rtai-target-language" name="target_language">
                            <option value="es"><?php esc_html_e('Spanish', 'ai-content-for-woocommerce'); ?></option>
                            <option value="fr"><?php esc_html_e('French', 'ai-content-for-woocommerce'); ?></option>
                            <option value="de"><?php esc_html_e('German', 'ai-content-for-woocommerce'); ?></option>
                            <option value="it"><?php esc_html_e('Italian', 'ai-content-for-woocommerce'); ?></option>
                            <option value="pt"><?php esc_html_e('Portuguese', 'ai-content-for-woocommerce'); ?></option>
                            <option value="ru"><?php esc_html_e('Russian', 'ai-content-for-woocommerce'); ?></option>
                            <option value="zh"><?php esc_html_e('Chinese', 'ai-content-for-woocommerce'); ?></option>
                            <option value="ja"><?php esc_html_e('Japanese', 'ai-content-for-woocommerce'); ?></option>
                            <option value="ko"><?php esc_html_e('Korean', 'ai-content-for-woocommerce'); ?></option>
                            <option value="ar"><?php esc_html_e('Arabic', 'ai-content-for-woocommerce'); ?></option>
                        </select>
                    </div>
                    
                    <div class="rtai-field">
                        <label for="rtai-formality"><?php esc_html_e('Formality Level', 'ai-content-for-woocommerce'); ?></label>
                        <select id="rtai-formality" name="formality">
                            <option value="formal"><?php esc_html_e('Formal', 'ai-content-for-woocommerce'); ?></option>
                            <option value="informal"><?php esc_html_e('Informal', 'ai-content-for-woocommerce'); ?></option>
                        </select>
                    </div>
                    
                    <div class="rtai-translation-artifacts">
                        <label>
                            <input type="checkbox" name="translation_artifacts[]" value="title" checked>
                            <?php esc_html_e('Product Title', 'ai-content-for-woocommerce'); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="translation_artifacts[]" value="short_description" checked>
                            <?php esc_html_e('Short Description', 'ai-content-for-woocommerce'); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="translation_artifacts[]" value="long_description" checked>
                            <?php esc_html_e('Long Description', 'ai-content-for-woocommerce'); ?>
                        </label>
                    </div>
                    
                    <div class="rtai-actions">
                        <button type="button" class="button button-primary rtai-translate-btn">
                            <span class="dashicons dashicons-translation"></span>
                            <?php esc_html_e('Translate Content', 'ai-content-for-woocommerce'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="rtai-translation-results" style="display: none;">
                    <h4><?php esc_html_e('Translated Content', 'ai-content-for-woocommerce'); ?></h4>
                    <div class="rtai-translation-results-content"></div>
                </div>
            </div>
            
            <!-- History Tab -->
            <div class="rtai-tab-content" id="rtai-tab-history">
                <h4><?php esc_html_e('Generation History', 'ai-content-for-woocommerce'); ?></h4>
                
                <?php if (!empty($history)): ?>
                    <div class="rtai-history-list">
                        <?php foreach (array_slice($history, 0, 10) as $entry): ?>
                            <div class="rtai-history-item" data-history-id="<?php echo esc_attr($entry['id']); ?>">
                                <div class="rtai-history-header">
                                    <strong><?php echo esc_html(ucwords(str_replace('_', ' ', $entry['artifact']))); ?></strong>
                                    <span class="rtai-history-date"><?php echo esc_html(mysql2date('M j, Y g:i A', $entry['timestamp'])); ?></span>
                                </div>
                                <div class="rtai-history-preview">
                                    <?php echo esc_html(wp_trim_words($entry['output'], 20)); ?>
                                </div>
                                <div class="rtai-history-meta">
                                    <span class="rtai-model"><?php echo esc_html($entry['model']); ?></span>
                                    <span class="rtai-tokens"><?php echo number_format($entry['tokens']); ?> tokens</span>
                                    <?php if (!empty($entry['cost_estimate'])): ?>
                                        <span class="rtai-cost">$<?php echo number_format($entry['cost_estimate'], 4); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="rtai-history-actions">
                                    <button type="button" class="button rtai-view-history" data-history-id="<?php echo esc_attr($entry['id']); ?>">
                                        <?php esc_html_e('View', 'ai-content-for-woocommerce'); ?>
                                    </button>
                                    <button type="button" class="button rtai-rollback-history" data-history-id="<?php echo esc_attr($entry['id']); ?>">
                                        <?php esc_html_e('Rollback', 'ai-content-for-woocommerce'); ?>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="rtai-no-history">
                        <?php esc_html_e('No generation history found. Generate some content to see it here.', 'ai-content-for-woocommerce'); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Results Modal -->
        <div id="rtai-results-modal" class="rtai-modal" style="display: none;">
            <div class="rtai-modal-content">
                <div class="rtai-modal-header">
                    <h3><?php esc_html_e('Generated Content', 'ai-content-for-woocommerce'); ?></h3>
                    <button type="button" class="rtai-modal-close">&times;</button>
                </div>
                <div class="rtai-modal-body">
                    <div class="rtai-content-preview"></div>
                </div>
                <div class="rtai-modal-footer">
                    <button type="button" class="button button-primary rtai-apply-content">
                        <?php esc_html_e('Apply to Product', 'ai-content-for-woocommerce'); ?>
                    </button>
                    <button type="button" class="button rtai-edit-content">
                        <?php esc_html_e('Edit Before Applying', 'ai-content-for-woocommerce'); ?>
                    </button>
                    <button type="button" class="button rtai-modal-close">
                        <?php esc_html_e('Cancel', 'ai-content-for-woocommerce'); ?>
                    </button>
                </div>
            </div>
        </div>
        
    <?php endif; ?>
</div>