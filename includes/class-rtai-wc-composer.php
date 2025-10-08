<?php
/**
 * Content Composer Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class RTAI_WC_Composer {
    
    private static $instance = null;
    
    const SUPPORTED_ARTIFACTS = array(
        'title',
        'short_description', 
        'long_description',
        'seo_title',
        'seo_description',
        'bullets',
        'faq',
        'attributes',
        'translations'
    );
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Hook filters for extensibility
        add_filter('rtai_wc_prompt_context', array($this, 'apply_context_filters'), 10, 2);
    }
    
    /**
     * Generate content for a product
     */
    public function generate_content($post_id, $artifacts, $context_overrides = array()) {
        $product = wc_get_product($post_id);
        if (!$product) {
            throw new Exception(__('Product not found.', 'ai-content-for-woocommerce'));
        }
        
        // Validate artifacts
        $artifacts = array_intersect($artifacts, self::SUPPORTED_ARTIFACTS);
        if (empty($artifacts)) {
            throw new Exception(__('No valid artifacts specified.', 'ai-content-for-woocommerce'));
        }
        
        $results = array();
        $api_client = RTAI_WC_API_Client::get_instance();
        
        foreach ($artifacts as $artifact) {
            try {
                // Build context
                $context = $this->build_context($product, $artifact, $context_overrides);
                
                // Generate content (use sync version for background processing)
                $response = $api_client->generate_sync($artifact, $context);
                
                // Save to history
                $this->save_to_history($post_id, $artifact, $context, $response);
                
                $results[$artifact] = array(
                    'success' => true,
                    'content' => $response['content'] ?? '',
                    'tokens' => $response['usage']['total_tokens'] ?? 0,
                    'model' => $response['model'] ?? '',
                    'cost_estimate' => $response['cost_estimate'] ?? 0,
                );
                
            } catch (Exception $e) {
                $results[$artifact] = array(
                    'success' => false,
                    'error' => $e->getMessage(),
                );
            }
        }
        
        return $results;
    }
    
    /**
     * Build context for generation
     */
    public function build_context($product, $artifact, $overrides = array()) {
        $product = wc_get_product($product);
        if (!$product) {
            throw new Exception(__('Product not found.', 'ai-content-for-woocommerce'));
        }
        $context = array(
            'product_name' => $product->get_name(),
            'product_type' => $product->get_type(),
            'price' => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'sku' => $product->get_sku(),
            'stock_status' => $product->get_stock_status(),
            'categories' => array(),
            'tags' => array(),
            'attributes' => array(),
            'images' => array(),
            'current_content' => array(),
            'variations' => array(),
        );
        
        // Categories
        $categories = get_the_terms($product->get_id(), 'product_cat');
        if ($categories && !is_wp_error($categories)) {
            $context['categories'] = wp_list_pluck($categories, 'name');
        }
        
        // Tags
        $tags = get_the_terms($product->get_id(), 'product_tag');
        if ($tags && !is_wp_error($tags)) {
            $context['tags'] = wp_list_pluck($tags, 'name');
        }
        
        // Attributes
        $attributes = $product->get_attributes();
        foreach ($attributes as $attribute) {
            if ($attribute->is_taxonomy()) {
                $terms = get_terms(array(
                    'taxonomy' => $attribute->get_name(),
                    'include' => $attribute->get_options(),
                ));
                if (!is_wp_error($terms)) {
                    $context['attributes'][$attribute->get_name()] = wp_list_pluck($terms, 'name');
                }
            } else {
                $context['attributes'][$attribute->get_name()] = $attribute->get_options();
            }
        }
        
        // Images
        $image_ids = $product->get_gallery_image_ids();
        array_unshift($image_ids, $product->get_image_id());
        
        foreach (array_filter($image_ids) as $image_id) {
            $alt_text = get_post_meta($image_id, '_wp_attachment_image_alt', true);
            $caption = wp_get_attachment_caption($image_id);
            
            if ($alt_text || $caption) {
                $context['images'][] = array(
                    'alt' => $alt_text,
                    'caption' => $caption,
                );
            }
        }
        
        // Current content
        $context['current_content'] = array(
            'title' => $product->get_name(),
            'short_description' => $product->get_short_description(),
            'long_description' => $product->get_description(),
        );
        
        // SEO data (if Yoast or RankMath is active)
        $context['current_seo'] = $this->get_current_seo_data($product->get_id());
        
        // Variations (for variable products)
        if ($product->is_type('variable')) {
            $variations = $product->get_available_variations();
            foreach ($variations as $variation_data) {
                $variation = wc_get_product($variation_data['variation_id']);
                if ($variation) {
                    $context['variations'][] = array(
                        'attributes' => $variation->get_variation_attributes(),
                        'price' => $variation->get_price(),
                        'sku' => $variation->get_sku(),
                    );
                }
            }
        }
        
        // Apply overrides
        $context = array_merge($context, $overrides);
        
        // Apply filters for extensibility
        $context = apply_filters('rtai_wc_prompt_context', $context, $artifact);
        
        return $context;
    }
    
    /**
     * Get current SEO data
     */
    private function get_current_seo_data($post_id) {
        $seo_data = array();
        
        // Yoast SEO
        if (defined('WPSEO_VERSION')) {
            $seo_data['yoast_title'] = get_post_meta($post_id, '_yoast_wpseo_title', true);
            $seo_data['yoast_description'] = get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
            $seo_data['yoast_focus_keyword'] = get_post_meta($post_id, '_yoast_wpseo_focuskw', true);
        }
        
        // Rank Math
        if (defined('RANK_MATH_VERSION')) {
            $seo_data['rankmath_title'] = get_post_meta($post_id, 'rank_math_title', true);
            $seo_data['rankmath_description'] = get_post_meta($post_id, 'rank_math_description', true);
            $seo_data['rankmath_focus_keyword'] = get_post_meta($post_id, 'rank_math_focus_keyword', true);
        }
        
        return $seo_data;
    }
    
    /**
     * Apply content to product
     */
    public function apply_content($post_id, $artifact, $content) {
        $product = wc_get_product($post_id);
        if (!$product) {
            throw new Exception(__('Product not found.', 'ai-content-for-woocommerce'));
        }
        
        // Check if field is locked
        $flags = get_post_meta($post_id, '_rtai_flags', true) ?: array();
        if (!empty($flags['locked'][$artifact])) {
            throw new Exception(__('This field is locked from AI updates.', 'ai-content-for-woocommerce'));
        }
        
        switch ($artifact) {
            case 'title':
                wp_update_post(array(
                    'ID' => $post_id,
                    'post_title' => sanitize_text_field($content),
                ));
                break;
                
            case 'short_description':
                wp_update_post(array(
                    'ID' => $post_id,
                    'post_excerpt' => wp_kses_post($content),
                ));
                break;
                
            case 'long_description':
                wp_update_post(array(
                    'ID' => $post_id,
                    'post_content' => wp_kses_post($content),
                ));
                break;
                
            case 'seo_title':
                $this->apply_seo_title($post_id, $content);
                break;
                
            case 'seo_description':
                $this->apply_seo_description($post_id, $content);
                break;
                
            case 'bullets':
            case 'faq':
            case 'attributes':
                // Store as meta for later use
                update_post_meta($post_id, '_rtai_' . $artifact, $content);
                break;
        }
        
        // Update last artifacts cache
        $last_artifacts = get_post_meta($post_id, '_rtai_last_artifacts', true) ?: array();
        $last_artifacts[$artifact] = array(
            'content' => $content,
            'applied_at' => current_time('mysql'),
            'user_id' => get_current_user_id(),
        );
        update_post_meta($post_id, '_rtai_last_artifacts', $last_artifacts);
        
        do_action('rtai_wc_content_applied', $post_id, $artifact, $content);
        
        return true;
    }
    
    /**
     * Apply SEO title
     */
    private function apply_seo_title($post_id, $content) {
        // Yoast SEO
        if (defined('WPSEO_VERSION')) {
            update_post_meta($post_id, '_yoast_wpseo_title', sanitize_text_field($content));
        }
        
        // Rank Math
        if (defined('RANK_MATH_VERSION')) {
            update_post_meta($post_id, 'rank_math_title', sanitize_text_field($content));
        }
        
        // Fallback to custom meta
        update_post_meta($post_id, '_rtai_seo_title', sanitize_text_field($content));
    }
    
    /**
     * Apply SEO description
     */
    private function apply_seo_description($post_id, $content) {
        // Yoast SEO
        if (defined('WPSEO_VERSION')) {
            update_post_meta($post_id, '_yoast_wpseo_metadesc', sanitize_text_field($content));
        }
        
        // Rank Math
        if (defined('RANK_MATH_VERSION')) {
            update_post_meta($post_id, 'rank_math_description', sanitize_text_field($content));
        }
        
        // Fallback to custom meta
        update_post_meta($post_id, '_rtai_seo_description', sanitize_text_field($content));
    }
    
    /**
     * Save generation to history
     */
    private function save_to_history($post_id, $artifact, $context, $response) {
        $history = get_post_meta($post_id, '_rtai_history', true) ?: array();
        
        $entry = array(
            'id' => uniqid(),
            'timestamp' => current_time('mysql'),
            'artifact' => $artifact,
            'prompt_hash' => md5(wp_json_encode($context)),
            'input_summary' => $this->summarize_context($context),
            'output' => $response['content'] ?? '',
            'model' => $response['model'] ?? '',
            'tokens' => $response['usage']['total_tokens'] ?? 0,
            'cost_estimate' => $response['cost_estimate'] ?? 0,
            'user_id' => get_current_user_id(),
        );
        
        // Add to beginning of array
        array_unshift($history, $entry);
        
        // Keep only last 20 entries per product
        $history = array_slice($history, 0, 20);
        
        update_post_meta($post_id, '_rtai_history', $history);
    }
    
    /**
     * Summarize context for history
     */
    private function summarize_context($context) {
        return array(
            'product_name' => $context['product_name'] ?? '',
            'categories' => $context['categories'] ?? array(),
            'attributes_count' => count($context['attributes'] ?? array()),
            'variations_count' => count($context['variations'] ?? array()),
        );
    }
    
    /**
     * Rollback to previous version
     */
    public function rollback_content($post_id, $history_id) {
        $history = get_post_meta($post_id, '_rtai_history', true) ?: array();
        
        $entry = null;
        foreach ($history as $item) {
            if ($item['id'] === $history_id) {
                $entry = $item;
                break;
            }
        }
        
        if (!$entry) {
            throw new Exception(__('History entry not found.', 'ai-content-for-woocommerce'));
        }
        
        // Apply the historical content
        return $this->apply_content($post_id, $entry['artifact'], $entry['output']);
    }
    
    /**
     * Get product history
     */
    public function get_history($post_id) {
        return get_post_meta($post_id, '_rtai_history', true) ?: array();
    }
    
    /**
     * Apply context filters (hook for extensions)
     */
    public function apply_context_filters($context, $artifact) {
        return $context;
    }
    
    /**
     * Lock/unlock field from AI updates
     */
    public function toggle_field_lock($post_id, $artifact, $locked = true) {
        $flags = get_post_meta($post_id, '_rtai_flags', true) ?: array();
        
        if (!isset($flags['locked'])) {
            $flags['locked'] = array();
        }
        
        if ($locked) {
            $flags['locked'][$artifact] = current_time('mysql');
        } else {
            unset($flags['locked'][$artifact]);
        }
        
        update_post_meta($post_id, '_rtai_flags', $flags);
    }
    
    /**
     * Check if field is locked
     */
    public function is_field_locked($post_id, $artifact) {
        $flags = get_post_meta($post_id, '_rtai_flags', true) ?: array();
        return !empty($flags['locked'][$artifact]);
    }
    
    /**
     * Extract attributes from description
     */
    public function extract_attributes($post_id, $description) {
        $api_client = RTAI_WC_API_Client::get_instance();
        
        $context = array(
            'description' => $description,
            'extract_task' => 'attributes',
            'format' => 'key_value_pairs',
        );
        
        try {
            $response = $api_client->generate_sync('attributes', $context);
            return $response['content'] ?? array();
        } catch (Exception $e) {
            throw new Exception(__('Failed to extract attributes: ', 'ai-content-for-woocommerce') . $e->getMessage());
        }
    }
}