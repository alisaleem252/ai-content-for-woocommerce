<?php
/**
 * Main plugin class
 */

if (!defined('ABSPATH')) {
    exit;
}

class RTAI_WC_Plugin {
    
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
        $this->init_hooks();
        $this->init_components();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'load_textdomain'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // Check for Action Scheduler
        add_action('plugins_loaded', array($this, 'check_action_scheduler'));
        
        // Add WooCommerce hooks
        add_action('woocommerce_init', array($this, 'init_woocommerce_integration'));
        
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Product edit hooks
        add_action('add_meta_boxes', array($this, 'add_product_meta_box'));
        add_action('save_post', array($this, 'save_product_meta'));
        
        // Bulk actions
        add_filter('bulk_actions-edit-product', array($this, 'add_bulk_actions'));
        add_filter('handle_bulk_actions-edit-product', array($this, 'handle_bulk_actions'), 10, 3);
        
        // AJAX handlers
        add_action('wp_ajax_rtai_wc_generate_content', array($this, 'ajax_generate_content'));
        add_action('wp_ajax_rtai_wc_get_stream_config', array($this, 'ajax_get_stream_config'));
        add_action('wp_ajax_rtai_wc_get_job_status', array($this, 'ajax_get_job_status'));
        add_action('wp_ajax_rtai_wc_apply_content', array($this, 'ajax_apply_content'));
        add_action('wp_ajax_rtai_wc_rollback_content', array($this, 'ajax_rollback_content'));
        add_action('wp_ajax_rtai_wc_test_connection', array($this, 'ajax_test_connection'));
    }
    
    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Initialize API client
        RTAI_WC_API_Client::get_instance();
        
        // Initialize admin UI
        if (is_admin()) {
            RTAI_WC_Admin_UI::get_instance();
        }
        
        // Initialize composer
        RTAI_WC_Composer::get_instance();
        
        // Initialize jobs manager
        RTAI_WC_Jobs::get_instance();
        
        // Initialize REST API
        RTAI_WC_Rest_API::get_instance();
        
        // Initialize integrations
        RTAI_WC_Integrations::get_instance();
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('rapidtextai-woocommerce', false, dirname(RTAI_WC_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on relevant pages
        if (!in_array($hook, array('post.php', 'post-new.php', 'edit.php', 'settings_page_rtai-wc-settings'))) {
            return;
        }
        
        $screen = get_current_screen();
        if ($screen && $screen->post_type !== 'product' && $hook !== 'settings_page_rtai-wc-settings') {
            return;
        }
        
        wp_enqueue_style(
            'rtai-wc-admin',
            RTAI_WC_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            RTAI_WC_VERSION
        );
        
        wp_enqueue_script(
            'rtai-wc-admin',
            RTAI_WC_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-util'),
            RTAI_WC_VERSION,
            true
        );
        
        wp_localize_script('rtai-wc-admin', 'rtaiWC', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rtai_wc_nonce'),
            'strings' => array(
                'generating' => __('Generating content...', 'rapidtextai-woocommerce'),
                'error' => __('Error occurred while generating content.', 'rapidtextai-woocommerce'),
                'success' => __('Content generated successfully!', 'rapidtextai-woocommerce'),
                'confirm_apply' => __('Are you sure you want to apply this content?', 'rapidtextai-woocommerce'),
                'confirm_rollback' => __('Are you sure you want to rollback to this version?', 'rapidtextai-woocommerce'),
            )
        ));
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Frontend assets if needed
    }
    
    /**
     * Check for Action Scheduler dependency
     */
    public function check_action_scheduler() {
        if (!class_exists('ActionScheduler') && !function_exists('as_schedule_single_action')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-warning"><p>' . 
                     esc_html__('RapidTextAI for WooCommerce: Action Scheduler not found. Bulk operations will use WordPress cron instead.', 'rapidtextai-woocommerce') . 
                     '</p></div>';
            });
        }
    }
    
    /**
     * Initialize WooCommerce integration
     */
    public function init_woocommerce_integration() {
        // WooCommerce specific initialization
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('RapidTextAI Settings', 'rapidtextai-woocommerce'),
            __('RapidTextAI', 'rapidtextai-woocommerce'),
            'manage_options',
            'rtai-wc-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        RTAI_WC_Admin_UI::get_instance()->render_settings_page();
    }
    
    /**
     * Add product meta box
     */
    public function add_product_meta_box() {
        add_meta_box(
            'rtai-wc-composer',
            __('RapidTextAI Composer', 'rapidtextai-woocommerce'),
            array($this, 'render_product_meta_box'),
            'product',
            'side',
            'high'
        );
    }
    
    /**
     * Render product meta box
     */
    public function render_product_meta_box($post) {
        RTAI_WC_Admin_UI::get_instance()->render_product_meta_box($post);
    }
    
    /**
     * Save product meta
     */
    public function save_product_meta($post_id) {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (get_post_type($post_id) !== 'product') {
            return;
        }
        
        // Handle meta saving if needed
    }
    
    /**
     * Add bulk actions
     */
    public function add_bulk_actions($actions) {
        $actions['rtai_generate_content'] = __('Generate AI Content', 'rapidtextai-woocommerce');
        return $actions;
    }
    
    /**
     * Handle bulk actions
     */
    public function handle_bulk_actions($redirect_to, $doaction, $post_ids) {
        if ($doaction !== 'rtai_generate_content') {
            return $redirect_to;
        }
        
        if (!current_user_can('edit_posts')) {
            return $redirect_to;
        }
        
        // Queue bulk generation jobs
        $batch_id = RTAI_WC_Jobs::get_instance()->queue_bulk_generation($post_ids);
        
        $redirect_to = add_query_arg(array(
            'rtai_bulk_started' => 1,
            'batch_id' => $batch_id,
            'count' => count($post_ids)
        ), $redirect_to);
        
        return $redirect_to;
    }
    
    /**
     * AJAX: Generate content
     */
    public function ajax_generate_content() {
        check_ajax_referer('rtai_wc_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Insufficient permissions.', 'rapidtextai-woocommerce'));
        }
        
        $post_id = intval($_POST['post_id'] ?? 0);
        $artifacts = array_map('sanitize_key', $_POST['artifacts'] ?? array());
        $context_overrides = wp_unslash($_POST['context_overrides'] ?? array());
        
        try {
            $result = RTAI_WC_Composer::get_instance()->generate_content(
                $post_id,
                $artifacts,
                $context_overrides
            );
            
            wp_send_json_success($result);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * AJAX: Get job status
     */
    public function ajax_get_job_status() {
        check_ajax_referer('rtai_wc_nonce', 'nonce');
        
        $job_id = intval($_POST['job_id'] ?? 0);
        $batch_id = sanitize_text_field($_POST['batch_id'] ?? '');
        
        if ($job_id) {
            $status = RTAI_WC_Jobs::get_instance()->get_job_status($job_id);
        } elseif ($batch_id) {
            $status = RTAI_WC_Jobs::get_instance()->get_batch_status($batch_id);
        } else {
            wp_send_json_error(__('Invalid job or batch ID.', 'rapidtextai-woocommerce'));
        }
        
        wp_send_json_success($status);
    }
    
    /**
     * AJAX: Apply content
     */
    public function ajax_apply_content() {
        check_ajax_referer('rtai_wc_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Insufficient permissions.', 'rapidtextai-woocommerce'));
        }
        
        $post_id = intval($_POST['post_id'] ?? 0);
        $artifact = sanitize_key($_POST['artifact'] ?? '');
        $content = wp_unslash($_POST['content'] ?? '');
        
        try {
            $result = RTAI_WC_Composer::get_instance()->apply_content($post_id, $artifact, $content);
            wp_send_json_success($result);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * AJAX: Rollback content
     */
    public function ajax_rollback_content() {
        check_ajax_referer('rtai_wc_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Insufficient permissions.', 'rapidtextai-woocommerce'));
        }
        
        $post_id = intval($_POST['post_id'] ?? 0);
        $history_id = sanitize_text_field($_POST['history_id'] ?? '');
        
        try {
            $result = RTAI_WC_Composer::get_instance()->rollback_content($post_id, $history_id);
            wp_send_json_success($result);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * AJAX: Get stream configuration
     */
    public function ajax_get_stream_config() {
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
        
        check_ajax_referer('rtai_wc_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Insufficient permissions.', 'rapidtextai-woocommerce'));
        }
        
        $post_id = intval($_POST['post_id'] ?? 0);
        $artifact = sanitize_key($_POST['artifact'] ?? '');
        $context_overrides = wp_unslash($_POST['context_overrides'] ?? array());
        
        try {
            // Get product context
            $context = RTAI_WC_Composer::get_instance()->build_context($post_id, $context_overrides);
            
            // Get streaming configuration from API client
            $stream_config = RTAI_WC_API_Client::get_instance()->generate($artifact, $context);
            
            wp_send_json_success($stream_config);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * AJAX: Test API connection
     */
    public function ajax_test_connection() {
        check_ajax_referer('rtai_wc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'rapidtextai-woocommerce'));
        }
        
        try {
            $result = RTAI_WC_API_Client::get_instance()->test_connection();
            wp_send_json_success($result);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Plugin activation
     */
    public static function activate() {
        // Create database tables
        self::create_tables();
        
        // Set default options
        self::set_default_options();
        
        // Schedule cleanup cron
        if (!wp_next_scheduled('rtai_wc_cleanup')) {
            wp_schedule_event(time(), 'daily', 'rtai_wc_cleanup');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('rtai_wc_cleanup');
        
        // Cancel pending jobs
        if (class_exists('RTAI_WC_Jobs')) {
            RTAI_WC_Jobs::get_instance()->cancel_all_jobs();
        }
    }
    
    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rtai_jobs';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL,
            artifact varchar(50) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'queued',
            request_payload longtext,
            response longtext,
            error text,
            model varchar(100),
            tokens int unsigned,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            started_at datetime,
            finished_at datetime,
            user_id bigint(20) unsigned,
            batch_id varchar(50),
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY status (status),
            KEY batch_id (batch_id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Set default options
     */
    private static function set_default_options() {
        $default_settings = array(
            'api_key' => '',
            'model_profile' => 'gpt-4o',
            'temperature' => 0.7,
            'max_tokens' => 2000,
            'tone' => 'professional',
            'profanity_filter' => true,
            'brand_safety' => true,
            'templates' => array(
                'title' => 'Generate a compelling product title for {product_name} that highlights its key features and appeals to {audience}.',
                'short_description' => 'Write a brief, engaging product description for {product_name} that captures the main benefits in 2-3 sentences.',
                'long_description' => 'Create a detailed product description for {product_name} including features, benefits, and use cases. Use a {tone} tone.',
                'seo_title' => 'Generate an SEO-optimized title for {product_name} targeting keywords: {keywords}',
                'seo_description' => 'Write an SEO meta description for {product_name} that includes target keywords and stays under 155 characters.',
                'bullets' => 'Create 5-7 bullet points highlighting the key features and benefits of {product_name}.',
                'faq' => 'Generate 5 frequently asked questions and answers about {product_name}.',
            ),
        );
        
        add_option('rtai_wc_settings', $default_settings);
    }
}