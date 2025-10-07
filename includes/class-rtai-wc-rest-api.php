<?php
/**
 * REST API Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class RTAI_WC_Rest_API {
    
    private static $instance = null;
    
    const NAMESPACE = 'rtai-wc/v1';
    
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
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Generate content
        register_rest_route(self::NAMESPACE, '/generate', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'generate_content'),
            'permission_callback' => array($this, 'check_generate_permission'),
            'args' => array(
                'post_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'validate_callback' => array($this, 'validate_product_id'),
                ),
                'artifacts' => array(
                    'required' => true,
                    'type' => 'array',
                    'items' => array('type' => 'string'),
                    'validate_callback' => array($this, 'validate_artifacts'),
                ),
                'context_overrides' => array(
                    'required' => false,
                    'type' => 'object',
                    'default' => array(),
                ),
                'model_profile' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => null,
                ),
                'apply' => array(
                    'required' => false,
                    'type' => 'boolean',
                    'default' => false,
                ),
            ),
        ));
        
        // Get job status
        register_rest_route(self::NAMESPACE, '/jobs', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_jobs'),
            'permission_callback' => array($this, 'check_generate_permission'),
            'args' => array(
                'batch_id' => array(
                    'required' => false,
                    'type' => 'string',
                ),
                'job_id' => array(
                    'required' => false,
                    'type' => 'integer',
                ),
            ),
        ));
        
        // Apply content
        register_rest_route(self::NAMESPACE, '/apply', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'apply_content'),
            'permission_callback' => array($this, 'check_generate_permission'),
            'args' => array(
                'post_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'validate_callback' => array($this, 'validate_product_id'),
                ),
                'artifact' => array(
                    'required' => true,
                    'type' => 'string',
                    'validate_callback' => array($this, 'validate_artifact'),
                ),
                'content' => array(
                    'required' => true,
                    'type' => 'string',
                ),
            ),
        ));
        
        // Rollback content
        register_rest_route(self::NAMESPACE, '/rollback', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'rollback_content'),
            'permission_callback' => array($this, 'check_generate_permission'),
            'args' => array(
                'post_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'validate_callback' => array($this, 'validate_product_id'),
                ),
                'history_id' => array(
                    'required' => true,
                    'type' => 'string',
                ),
            ),
        ));
        
        // Get quota
        register_rest_route(self::NAMESPACE, '/quota', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_quota'),
            'permission_callback' => array($this, 'check_generate_permission'),
        ));
        
        // Get product history
        register_rest_route(self::NAMESPACE, '/history/(?P<id>\d+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_product_history'),
            'permission_callback' => array($this, 'check_generate_permission'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'validate_callback' => array($this, 'validate_product_id'),
                ),
            ),
        ));
        
        // Test connection
        register_rest_route(self::NAMESPACE, '/test-connection', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'test_connection'),
            'permission_callback' => array($this, 'check_settings_permission'),
        ));
        
        // Bulk generate
        register_rest_route(self::NAMESPACE, '/bulk-generate', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'bulk_generate'),
            'permission_callback' => array($this, 'check_generate_permission'),
            'args' => array(
                'post_ids' => array(
                    'required' => true,
                    'type' => 'array',
                    'items' => array('type' => 'integer'),
                ),
                'artifacts' => array(
                    'required' => false,
                    'type' => 'array',
                    'items' => array('type' => 'string'),
                    'default' => array('title', 'short_description', 'seo_title', 'seo_description'),
                ),
                'context_overrides' => array(
                    'required' => false,
                    'type' => 'object',
                    'default' => array(),
                ),
            ),
        ));
    }
    
    /**
     * Generate content endpoint
     */
    public function generate_content($request) {
        try {
            $post_id = $request->get_param('post_id');
            $artifacts = $request->get_param('artifacts');
            $context_overrides = $request->get_param('context_overrides');
            $apply = $request->get_param('apply');
            
            $composer = RTAI_WC_Composer::get_instance();
            $result = $composer->generate_content($post_id, $artifacts, $context_overrides);
            
            // Auto-apply if requested
            if ($apply) {
                foreach ($result as $artifact => $data) {
                    if ($data['success']) {
                        $composer->apply_content($post_id, $artifact, $data['content']);
                    }
                }
            }
            
            return rest_ensure_response($result);
            
        } catch (Exception $e) {
            return new WP_Error('generation_failed', $e->getMessage(), array('status' => 400));
        }
    }
    
    /**
     * Get jobs endpoint
     */
    public function get_jobs($request) {
        $jobs_manager = RTAI_WC_Jobs::get_instance();
        
        $batch_id = $request->get_param('batch_id');
        $job_id = $request->get_param('job_id');
        
        if ($job_id) {
            $result = $jobs_manager->get_job_status($job_id);
        } elseif ($batch_id) {
            $result = $jobs_manager->get_batch_status($batch_id);
        } else {
            $result = $jobs_manager->get_recent_jobs(get_current_user_id(), 20);
        }
        
        return rest_ensure_response($result);
    }
    
    /**
     * Apply content endpoint
     */
    public function apply_content($request) {
        try {
            $post_id = $request->get_param('post_id');
            $artifact = $request->get_param('artifact');
            $content = $request->get_param('content');
            
            $composer = RTAI_WC_Composer::get_instance();
            $result = $composer->apply_content($post_id, $artifact, $content);
            
            return rest_ensure_response(array('success' => $result));
            
        } catch (Exception $e) {
            return new WP_Error('apply_failed', $e->getMessage(), array('status' => 400));
        }
    }
    
    /**
     * Rollback content endpoint
     */
    public function rollback_content($request) {
        try {
            $post_id = $request->get_param('post_id');
            $history_id = $request->get_param('history_id');
            
            $composer = RTAI_WC_Composer::get_instance();
            $result = $composer->rollback_content($post_id, $history_id);
            
            return rest_ensure_response(array('success' => $result));
            
        } catch (Exception $e) {
            return new WP_Error('rollback_failed', $e->getMessage(), array('status' => 400));
        }
    }
    
    /**
     * Get quota endpoint
     */
    public function get_quota($request) {
        try {
            $api_client = RTAI_WC_API_Client::get_instance();
            $quota = $api_client->get_quota();
            
            return rest_ensure_response($quota);
            
        } catch (Exception $e) {
            return new WP_Error('quota_failed', $e->getMessage(), array('status' => 400));
        }
    }
    
    /**
     * Get product history endpoint
     */
    public function get_product_history($request) {
        $post_id = $request->get_param('id');
        
        $composer = RTAI_WC_Composer::get_instance();
        $history = $composer->get_history($post_id);
        
        return rest_ensure_response($history);
    }
    
    /**
     * Test connection endpoint
     */
    public function test_connection($request) {
        try {
            $api_client = RTAI_WC_API_Client::get_instance();
            $result = $api_client->test_connection();
            
            return rest_ensure_response($result);
            
        } catch (Exception $e) {
            return new WP_Error('connection_failed', $e->getMessage(), array('status' => 400));
        }
    }
    
    /**
     * Bulk generate endpoint
     */
    public function bulk_generate($request) {
        try {
            $post_ids = $request->get_param('post_ids');
            $artifacts = $request->get_param('artifacts');
            $context_overrides = $request->get_param('context_overrides');
            
            $jobs_manager = RTAI_WC_Jobs::get_instance();
            $batch_id = $jobs_manager->queue_bulk_generation($post_ids, $artifacts, $context_overrides);
            
            return rest_ensure_response(array(
                'batch_id' => $batch_id,
                'total_jobs' => count($post_ids) * count($artifacts),
            ));
            
        } catch (Exception $e) {
            return new WP_Error('bulk_failed', $e->getMessage(), array('status' => 400));
        }
    }
    
    /**
     * Check generate permission
     */
    public function check_generate_permission($request) {
        return current_user_can('edit_posts');
    }
    
    /**
     * Check settings permission
     */
    public function check_settings_permission($request) {
        return current_user_can('manage_options');
    }
    
    /**
     * Validate product ID
     */
    public function validate_product_id($param, $request, $key) {
        $product = wc_get_product($param);
        return $product !== false;
    }
    
    /**
     * Validate artifacts
     */
    public function validate_artifacts($param, $request, $key) {
        if (!is_array($param) || empty($param)) {
            return false;
        }
        
        $valid_artifacts = RTAI_WC_Composer::SUPPORTED_ARTIFACTS;
        
        foreach ($param as $artifact) {
            if (!in_array($artifact, $valid_artifacts)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate single artifact
     */
    public function validate_artifact($param, $request, $key) {
        return in_array($param, RTAI_WC_Composer::SUPPORTED_ARTIFACTS);
    }
}