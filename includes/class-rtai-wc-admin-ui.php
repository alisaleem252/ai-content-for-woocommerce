<?php
/**
 * Admin UI Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class RTAI_WC_Admin_UI {
    
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
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_notices', array($this, 'show_admin_notices'));
        add_action('wp_ajax_rtai_wc_save_api_key', array($this, 'handle_save_api_key_ajax'));
    }
    
    /**
     * Initialize settings
     */
    public function init_settings() {
        register_setting('rtai_wc_settings', 'rtai_wc_settings', array($this, 'sanitize_settings'));
    }
    /**
     * Handle AJAX save API key
     */
    public function handle_save_api_key_ajax() {
        // Check nonce
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'rtai_wc_save_api_key_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'rapidtextai-woocommerce')));
            return;
        }
        
        // Get and sanitize API key
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        
        if (empty($api_key)) {
            wp_send_json_error(array('message' => __('API key is required.', 'rapidtextai-woocommerce')));
            return;
        }
        
        // Get current settings
        $settings = get_option('rtai_wc_settings', array());
        $settings['api_key'] = $api_key;
        
        // Save settings
        update_option('rtai_wc_settings', $settings);
        
        // Update API client
        RTAI_WC_API_Client::get_instance()->set_api_key($api_key);
        
        // Test connection
        try {
            $connection_test = RTAI_WC_API_Client::get_instance()->test_connection();
            if ($connection_test['success']) {
                wp_send_json_success(array('message' => __('API key saved and connection verified successfully!', 'rapidtextai-woocommerce')));
            } else {
                wp_send_json_error(array('message' => __('API key saved but connection test failed.', 'rapidtextai-woocommerce')));
            }
        } catch (Exception $e) {
            wp_send_json_error(array('message' => sprintf(__('API key saved but connection test failed: %s', 'rapidtextai-woocommerce'), $e->getMessage())));
        }
    }
    /**
     * Show admin notices
     */
    public function show_admin_notices() {
        // Show connection notice
        if (!RTAI_WC_API_Client::get_instance()->is_connected()) {
            $this->show_connection_notice();
        }
        
        // Show bulk operation results
        if (isset($_GET['rtai_bulk_started'])) {
            $count = intval($_GET['count'] ?? 0);
            $batch_id = sanitize_text_field($_GET['batch_id'] ?? '');
            
            echo '<div class="notice notice-info is-dismissible">';
            echo '<p>' . sprintf(
                esc_html__('AI content generation started for %d products. Batch ID: %s', 'rapidtextai-woocommerce'),
                $count,
                esc_html($batch_id)
            ) . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Show connection notice
     */
    private function show_connection_notice() {
        $settings_url = admin_url('options-general.php?page=rtai-wc-settings');
        
        echo '<div class="notice notice-warning">';
        echo '<p>' . sprintf(
            esc_html__('RapidTextAI for WooCommerce is not connected. %sConnect now%s to start generating AI content.', 'rapidtextai-woocommerce'),
            '<a href="' . esc_url($settings_url) . '">',
            '</a>'
        ) . '</p>';
        echo '</div>';
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        $settings = get_option('rtai_wc_settings', array());
        $api_client = RTAI_WC_API_Client::get_instance();
        
        // Handle form submission
        if (isset($_POST['submit'])) {
            check_admin_referer('rtai_wc_settings');
            $this->handle_settings_save();
        }
        
        // Test connection if requested
        $connection_status = null;
        if (isset($_POST['test_connection'])) {
            check_admin_referer('rtai_wc_settings');
            try {
                $connection_status = $api_client->test_connection();
            } catch (Exception $e) {
                $connection_status = array('error' => $e->getMessage());
            }
        }
        
        // Get quota information
        $quota = null;
        try {
            if ($api_client->is_connected()) {
                $quota = $api_client->get_quota();
            }
        } catch (Exception $e) {
            // Ignore quota errors
        }
        
        include RTAI_WC_PLUGIN_DIR . 'templates/admin-settings.php';
    }
    
    /**
     * Handle settings save
     */
    private function handle_settings_save() {
        $settings = array();
        
        // Sanitize and save settings
        $settings['api_key'] = sanitize_text_field($_POST['api_key'] ?? '');
        $settings['model_profile'] = sanitize_key($_POST['model_profile'] ?? 'gpt-4o');
        $settings['temperature'] = floatval($_POST['temperature'] ?? 0.7);
        $settings['max_tokens'] = intval($_POST['max_tokens'] ?? 2000);
        $settings['tone'] = sanitize_text_field($_POST['tone'] ?? 'professional');
        $settings['profanity_filter'] = isset($_POST['profanity_filter']);
        $settings['brand_safety'] = isset($_POST['brand_safety']);
        
        // Templates
        $templates = array();
        $template_fields = array('title', 'short_description', 'long_description', 'seo_title', 'seo_description', 'bullets', 'faq');
        
        foreach ($template_fields as $field) {
            $templates[$field] = wp_kses_post($_POST['template_' . $field] ?? '');
        }
        $settings['templates'] = $templates;
        
        update_option('rtai_wc_settings', $settings);
        
        // Update API client
        RTAI_WC_API_Client::get_instance()->set_api_key($settings['api_key']);
        
        add_settings_error('rtai_wc_settings', 'settings_updated', __('Settings saved successfully.', 'rapidtextai-woocommerce'), 'updated');
    }
    
    /**
     * Render product meta box
     */
    public function render_product_meta_box($post) {
        $product = wc_get_product($post->ID);
        if (!$product) {
            return;
        }
        
        // Get history
        $history = get_post_meta($post->ID, '_rtai_history', true) ?: array();
        
        // Get last artifacts
        $last_artifacts = get_post_meta($post->ID, '_rtai_last_artifacts', true) ?: array();
        
        // Check if connected
        $is_connected = RTAI_WC_API_Client::get_instance()->is_connected();       
        
        include RTAI_WC_PLUGIN_DIR . 'templates/product-meta-box.php';
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        $sanitized['api_key'] = sanitize_text_field($input['api_key'] ?? '');
        $sanitized['model_profile'] = sanitize_key($input['model_profile'] ?? 'gpt-4o');
        $sanitized['temperature'] = max(0, min(2, floatval($input['temperature'] ?? 0.7)));
        $sanitized['max_tokens'] = max(100, min(8000, intval($input['max_tokens'] ?? 2000)));
        $sanitized['tone'] = sanitize_text_field($input['tone'] ?? 'professional');
        $sanitized['profanity_filter'] = !empty($input['profanity_filter']);
        $sanitized['brand_safety'] = !empty($input['brand_safety']);
        
        // Sanitize templates
        if (isset($input['templates']) && is_array($input['templates'])) {
            $sanitized['templates'] = array();
            foreach ($input['templates'] as $key => $template) {
                $sanitized['templates'][sanitize_key($key)] = wp_kses_post($template);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Get model profiles for select
     */
    public function get_model_options() {
        $profiles = RTAI_WC_API_Client::get_instance()->get_model_profiles();
        $options = array();
        
        foreach ($profiles as $key => $profile) {
            $options[$key] = sprintf(
                '%s (%s - $%s/1K tokens)',
                $profile['name'],
                $profile['provider'],
                number_format($profile['cost_per_1k'], 3)
            );
        }
        
        return $options;
    }
    
    /**
     * Get tone options
     */
    public function get_tone_options() {
        return array(
            'professional' => __('Professional', 'rapidtextai-woocommerce'),
            'friendly' => __('Friendly', 'rapidtextai-woocommerce'),
            'casual' => __('Casual', 'rapidtextai-woocommerce'),
            'luxury' => __('Luxury', 'rapidtextai-woocommerce'),
            'technical' => __('Technical', 'rapidtextai-woocommerce'),
            'playful' => __('Playful', 'rapidtextai-woocommerce'),
            'persuasive' => __('Persuasive', 'rapidtextai-woocommerce'),
            'informative' => __('Informative', 'rapidtextai-woocommerce'),
        );
    }
    
    /**
     * Render bulk action modal
     */
    public function render_bulk_modal() {
        include RTAI_WC_PLUGIN_DIR . 'templates/bulk-modal.php';
    }
    
    /**
     * Add bulk action modal to products page
     */
    public function add_bulk_modal() {
        $screen = get_current_screen();
        if ($screen && $screen->id === 'edit-product') {
            add_action('admin_footer', array($this, 'render_bulk_modal'));
        }
    }
}