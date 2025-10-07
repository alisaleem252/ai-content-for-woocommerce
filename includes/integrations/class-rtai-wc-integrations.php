<?php
/**
 * Third-party Integrations Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class RTAI_WC_Integrations {
    
    private static $instance = null;
    
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
        add_action('plugins_loaded', array($this, 'init_integrations'), 20);
    }
    
    /**
     * Initialize integrations
     */
    public function init_integrations() {
        // Yoast SEO
        if (defined('WPSEO_VERSION')) {
            $this->init_yoast_integration();
        }
        
        // Rank Math
        if (defined('RANK_MATH_VERSION')) {
            $this->init_rankmath_integration();
        }
        
        // WPML
        if (defined('ICL_SITEPRESS_VERSION')) {
            $this->init_wpml_integration();
        }
        
        // Polylang
        if (function_exists('pll_current_language')) {
            $this->init_polylang_integration();
        }
        
        // Elementor
        if (defined('ELEMENTOR_VERSION')) {
            $this->init_elementor_integration();
        }
        
        // WooCommerce Product Add-ons
        if (class_exists('WC_Product_Addons')) {
            $this->init_product_addons_integration();
        }
        
        // Advanced Custom Fields
        if (class_exists('ACF')) {
            $this->init_acf_integration();
        }
    }
    
    /**
     * Yoast SEO Integration
     */
    private function init_yoast_integration() {
        // Add RapidTextAI button to Yoast meta box
        add_action('wpseo_meta_box_content', array($this, 'add_yoast_button'));
        
        // Hook into Yoast's focus keyword for context
        add_filter('rtai_wc_prompt_context', array($this, 'add_yoast_context'), 10, 2);
    }
    
    /**
     * Add button to Yoast meta box
     */
    public function add_yoast_button() {
        global $post;
        
        if (!$post || get_post_type($post) !== 'product') {
            return;
        }
        
        echo '<div class="rtai-yoast-integration">';
        echo '<p><strong>' . esc_html__('RapidTextAI', 'rapidtextai-woocommerce') . '</strong></p>';
        echo '<button type="button" class="button rtai-generate-seo" data-post-id="' . esc_attr($post->ID) . '">';
        echo esc_html__('Generate SEO Content', 'rapidtextai-woocommerce');
        echo '</button>';
        echo '</div>';
    }
    
    /**
     * Add Yoast context to prompts
     */
    public function add_yoast_context($context, $artifact) {
        if (!in_array($artifact, array('seo_title', 'seo_description'))) {
            return $context;
        }
        
        if (isset($context['current_seo']['yoast_focus_keyword'])) {
            $context['focus_keywords'] = explode(',', $context['current_seo']['yoast_focus_keyword']);
        }
        
        return $context;
    }
    
    /**
     * Rank Math Integration
     */
    private function init_rankmath_integration() {
        // Add RapidTextAI button to Rank Math meta box
        add_action('rank_math/metabox/content', array($this, 'add_rankmath_button'));
        
        // Hook into Rank Math's focus keyword for context
        add_filter('rtai_wc_prompt_context', array($this, 'add_rankmath_context'), 10, 2);
    }
    
    /**
     * Add button to Rank Math meta box
     */
    public function add_rankmath_button() {
        global $post;
        
        if (!$post || get_post_type($post) !== 'product') {
            return;
        }
        
        echo '<div class="rtai-rankmath-integration">';
        echo '<h4>' . esc_html__('RapidTextAI', 'rapidtextai-woocommerce') . '</h4>';
        echo '<button type="button" class="button rtai-generate-seo" data-post-id="' . esc_attr($post->ID) . '">';
        echo esc_html__('Generate SEO Content', 'rapidtextai-woocommerce');
        echo '</button>';
        echo '</div>';
    }
    
    /**
     * Add Rank Math context to prompts
     */
    public function add_rankmath_context($context, $artifact) {
        if (!in_array($artifact, array('seo_title', 'seo_description'))) {
            return $context;
        }
        
        if (isset($context['current_seo']['rankmath_focus_keyword'])) {
            $context['focus_keywords'] = explode(',', $context['current_seo']['rankmath_focus_keyword']);
        }
        
        return $context;
    }
    
    /**
     * WPML Integration
     */
    private function init_wpml_integration() {
        // Add translation support
        add_action('wpml_after_save_post', array($this, 'sync_translations'));
        
        // Add language context
        add_filter('rtai_wc_prompt_context', array($this, 'add_wpml_context'), 10, 2);
        
        // Add translation artifact
        add_action('rtai_wc_content_applied', array($this, 'handle_wpml_translation'), 10, 3);
    }
    
    /**
     * Sync translations after content application
     */
    public function sync_translations($post_id) {
        if (get_post_type($post_id) !== 'product') {
            return;
        }
        
        // Get translations
        $translations = apply_filters('wpml_get_element_translations', null, $post_id, 'post_product');
        
        foreach ($translations as $lang_code => $translation) {
            if ($translation->element_id == $post_id) {
                continue; // Skip original
            }
            
            // Check if we should auto-translate
            $auto_translate = get_option('rtai_wc_auto_translate', false);
            if ($auto_translate) {
                $this->queue_translation($translation->element_id, $lang_code);
            }
        }
    }
    
    /**
     * Queue translation
     */
    private function queue_translation($post_id, $lang_code) {
        // Queue translation job
        $jobs_manager = RTAI_WC_Jobs::get_instance();
        
        $context_overrides = array(
            'target_language' => $lang_code,
            'translation_mode' => true,
        );
        
        $jobs_manager->queue_job($post_id, 'translations', $context_overrides);
    }
    
    /**
     * Add WPML context
     */
    public function add_wpml_context($context, $artifact) {
        if ($artifact === 'translations') {
            $context['current_language'] = apply_filters('wpml_current_language', null);
            $context['available_languages'] = apply_filters('wpml_active_languages', null);
        }
        
        return $context;
    }
    
    /**
     * Handle WPML translation application
     */
    public function handle_wpml_translation($post_id, $artifact, $content) {
        if ($artifact !== 'translations') {
            return;
        }
        
        // Parse translation content (assuming JSON format with language codes)
        $translations = json_decode($content, true);
        if (!is_array($translations)) {
            return;
        }
        
        foreach ($translations as $lang_code => $translated_content) {
            $translation_id = apply_filters('wpml_object_id', $post_id, 'product', false, $lang_code);
            
            if ($translation_id && $translation_id !== $post_id) {
                // Apply translated content
                wp_update_post(array(
                    'ID' => $translation_id,
                    'post_title' => $translated_content['title'] ?? '',
                    'post_content' => $translated_content['description'] ?? '',
                    'post_excerpt' => $translated_content['short_description'] ?? '',
                ));
            }
        }
    }
    
    /**
     * Polylang Integration
     */
    private function init_polylang_integration() {
        add_filter('rtai_wc_prompt_context', array($this, 'add_polylang_context'), 10, 2);
        add_action('rtai_wc_content_applied', array($this, 'handle_polylang_translation'), 10, 3);
    }
    
    /**
     * Add Polylang context
     */
    public function add_polylang_context($context, $artifact) {
        if ($artifact === 'translations') {
            $context['current_language'] = pll_current_language();
            $context['available_languages'] = pll_languages_list();
        }
        
        return $context;
    }
    
    /**
     * Handle Polylang translation
     */
    public function handle_polylang_translation($post_id, $artifact, $content) {
        if ($artifact !== 'translations') {
            return;
        }
        
        $translations = json_decode($content, true);
        if (!is_array($translations)) {
            return;
        }
        
        foreach ($translations as $lang_code => $translated_content) {
            $translation_id = pll_get_post($post_id, $lang_code);
            
            if ($translation_id && $translation_id !== $post_id) {
                wp_update_post(array(
                    'ID' => $translation_id,
                    'post_title' => $translated_content['title'] ?? '',
                    'post_content' => $translated_content['description'] ?? '',
                    'post_excerpt' => $translated_content['short_description'] ?? '',
                ));
            }
        }
    }
    
    /**
     * Elementor Integration
     */
    private function init_elementor_integration() {
        // Add RapidTextAI widget to Elementor
        add_action('elementor/widgets/widgets_registered', array($this, 'register_elementor_widget'));
        
        // Add button to Elementor editor
        add_action('elementor/editor/after_enqueue_scripts', array($this, 'enqueue_elementor_scripts'));
    }
    
    /**
     * Register Elementor widget
     */
    public function register_elementor_widget() {
        require_once RTAI_WC_PLUGIN_DIR . 'includes/integrations/elementor-widget.php';
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new RTAI_WC_Elementor_Widget());
    }
    
    /**
     * Enqueue Elementor scripts
     */
    public function enqueue_elementor_scripts() {
        wp_enqueue_script(
            'rtai-wc-elementor',
            RTAI_WC_PLUGIN_URL . 'assets/js/elementor.js',
            array('jquery'),
            RTAI_WC_VERSION,
            true
        );
    }
    
    /**
     * Product Add-ons Integration
     */
    private function init_product_addons_integration() {
        add_filter('rtai_wc_prompt_context', array($this, 'add_product_addons_context'), 10, 2);
    }
    
    /**
     * Add Product Add-ons context
     */
    public function add_product_addons_context($context, $artifact) {
        global $post;
        
        if (!$post) {
            return $context;
        }
        
        $addons = get_post_meta($post->ID, '_product_addons', true);
        if (!empty($addons)) {
            $context['product_addons'] = array();
            
            foreach ($addons as $addon) {
                $context['product_addons'][] = array(
                    'name' => $addon['name'] ?? '',
                    'type' => $addon['type'] ?? '',
                    'options' => $addon['options'] ?? array(),
                );
            }
        }
        
        return $context;
    }
    
    /**
     * ACF Integration
     */
    private function init_acf_integration() {
        add_filter('rtai_wc_prompt_context', array($this, 'add_acf_context'), 10, 2);
        add_action('rtai_wc_content_applied', array($this, 'handle_acf_fields'), 10, 3);
    }
    
    /**
     * Add ACF context
     */
    public function add_acf_context($context, $artifact) {
        global $post;
        
        if (!$post) {
            return $context;
        }
        
        // Get all ACF fields for the product
        $fields = get_fields($post->ID);
        if ($fields) {
            $context['custom_fields'] = array();
            
            foreach ($fields as $key => $value) {
                if (is_string($value) || is_numeric($value)) {
                    $context['custom_fields'][$key] = $value;
                }
            }
        }
        
        return $context;
    }
    
    /**
     * Handle ACF fields update
     */
    public function handle_acf_fields($post_id, $artifact, $content) {
        if ($artifact !== 'attributes') {
            return;
        }
        
        // Parse attributes and map to ACF fields
        $attributes = json_decode($content, true);
        if (!is_array($attributes)) {
            return;
        }
        
        foreach ($attributes as $key => $value) {
            // Convert attribute name to ACF field name
            $field_name = 'product_' . sanitize_key($key);
            
            if (function_exists('get_field_object') && get_field_object($field_name, $post_id)) {
                update_field($field_name, $value, $post_id);
            }
        }
    }
    
    /**
     * Check if integration is active
     */
    public function is_integration_active($integration) {
        switch ($integration) {
            case 'yoast':
                return defined('WPSEO_VERSION');
            case 'rankmath':
                return defined('RANK_MATH_VERSION');
            case 'wpml':
                return defined('ICL_SITEPRESS_VERSION');
            case 'polylang':
                return function_exists('pll_current_language');
            case 'elementor':
                return defined('ELEMENTOR_VERSION');
            case 'product_addons':
                return class_exists('WC_Product_Addons');
            case 'acf':
                return class_exists('ACF');
            default:
                return false;
        }
    }
    
    /**
     * Get integration status
     */
    public function get_integration_status() {
        return array(
            'yoast' => $this->is_integration_active('yoast'),
            'rankmath' => $this->is_integration_active('rankmath'),
            'wpml' => $this->is_integration_active('wpml'),
            'polylang' => $this->is_integration_active('polylang'),
            'elementor' => $this->is_integration_active('elementor'),
            'product_addons' => $this->is_integration_active('product_addons'),
            'acf' => $this->is_integration_active('acf'),
        );
    }
}