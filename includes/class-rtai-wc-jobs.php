<?php
/**
 * Jobs Management Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class RTAI_WC_Jobs {
    
    private static $instance = null;
    
    const STATUS_QUEUED = 'queued';
    const STATUS_RUNNING = 'running';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    
    const MAX_RETRIES = 3;
    const RETRY_DELAYS = array(30, 120, 600); // 30s, 2m, 10m
    
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
        add_action('rtai_wc_process_job', array($this, 'process_job'));
        add_action('rtai_wc_cleanup', array($this, 'cleanup_old_jobs'));
        
        // Hook into Action Scheduler if available
        if (function_exists('as_schedule_single_action')) {
            add_action('rtai_wc_job_scheduled', array($this, 'schedule_with_action_scheduler'));
        }
    }
    
    /**
     * Queue bulk generation
     */
    public function queue_bulk_generation($post_ids, $artifacts = null, $context_overrides = array()) {
        if (empty($post_ids)) {
            throw new Exception(__('No products selected.', 'rapidtextai-woocommerce'));
        }
        
        // Default artifacts for bulk operations
        if (empty($artifacts)) {
            $artifacts = array('title', 'short_description', 'seo_title', 'seo_description');
        }
        
        $batch_id = uniqid('batch_');
        $job_ids = array();
        
        foreach ($post_ids as $post_id) {
            $post_id = intval($post_id);
            
            // Verify it's a product
            if (get_post_type($post_id) !== 'product') {
                continue;
            }
            
            foreach ($artifacts as $artifact) {
                $job_id = $this->queue_job($post_id, $artifact, $context_overrides, $batch_id);
                if ($job_id) {
                    $job_ids[] = $job_id;
                }
            }
        }
        
        if (empty($job_ids)) {
            throw new Exception(__('No valid jobs could be created.', 'rapidtextai-woocommerce'));
        }
        
        // Start processing
        $this->start_batch_processing($batch_id);
        
        return $batch_id;
    }
    
    /**
     * Queue single job
     */
    public function queue_job($post_id, $artifact, $context_overrides = array(), $batch_id = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rtai_jobs';
        
        // Check for duplicate job
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name 
             WHERE post_id = %d AND artifact = %s AND status IN ('queued', 'running') 
             ORDER BY created_at DESC LIMIT 1",
            $post_id,
            $artifact
        ));
        
        if ($existing) {
            return $existing; // Job already queued
        }
        
        $request_payload = array(
            'post_id' => $post_id,
            'artifact' => $artifact,
            'context_overrides' => $context_overrides,
        );
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'post_id' => $post_id,
                'artifact' => $artifact,
                'status' => self::STATUS_QUEUED,
                'request_payload' => wp_json_encode($request_payload),
                'user_id' => get_current_user_id(),
                'batch_id' => $batch_id,
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%s', '%d', '%s', '%s')
        );
        
        if ($result === false) {
            throw new Exception(__('Failed to queue job.', 'rapidtextai-woocommerce'));
        }
        
        $job_id = $wpdb->insert_id;
        
        // Schedule the job
        $this->schedule_job($job_id);
        
        return $job_id;
    }
    
    /**
     * Schedule job processing
     */
    private function schedule_job($job_id, $delay = 0) {
        if (function_exists('as_schedule_single_action')) {
            // Use Action Scheduler
            as_schedule_single_action(
                time() + $delay,
                'rtai_wc_process_job',
                array($job_id),
                'rtai-wc-jobs'
            );
        } else {
            // Fallback to WordPress cron
            wp_schedule_single_event(
                time() + $delay,
                'rtai_wc_process_job',
                array($job_id)
            );
        }
        
        do_action('rtai_wc_job_scheduled', $job_id, $delay);
    }
    
    /**
     * Start batch processing
     */
    private function start_batch_processing($batch_id) {
        // Process first few jobs immediately
        $this->process_next_jobs($batch_id, 3);
    }
    
    /**
     * Process next jobs in batch
     */
    private function process_next_jobs($batch_id, $limit = 5) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rtai_jobs';
        
        $jobs = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM $table_name 
             WHERE batch_id = %s AND status = %s 
             ORDER BY created_at ASC LIMIT %d",
            $batch_id,
            self::STATUS_QUEUED,
            $limit
        ));
        
        foreach ($jobs as $job) {
            $this->schedule_job($job->id, 0);
        }
    }
    
    /**
     * Process a single job
     */
    public function process_job($job_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rtai_jobs';
        
        // Get job details
        $job = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $job_id
        ));
        
        if (!$job || $job->status !== self::STATUS_QUEUED) {
            return; // Job not found or already processed
        }
        
        // Mark as running
        $wpdb->update(
            $table_name,
            array(
                'status' => self::STATUS_RUNNING,
                'started_at' => current_time('mysql'),
            ),
            array('id' => $job_id),
            array('%s', '%s'),
            array('%d')
        );
        
        try {
            $request_data = json_decode($job->request_payload, true);
            
            // Generate content
            $composer = RTAI_WC_Composer::get_instance();
            $result = $composer->generate_content(
                $request_data['post_id'],
                array($request_data['artifact']),
                $request_data['context_overrides'] ?? array()
            );
            
            $artifact_result = $result[$request_data['artifact']];
            
            if ($artifact_result['success']) {
                // Auto-apply for bulk operations
                $composer->apply_content(
                    $request_data['post_id'],
                    $request_data['artifact'],
                    $artifact_result['content']
                );
                
                // Mark as success
                $wpdb->update(
                    $table_name,
                    array(
                        'status' => self::STATUS_SUCCESS,
                        'response' => wp_json_encode($artifact_result),
                        'model' => $artifact_result['model'] ?? '',
                        'tokens' => $artifact_result['tokens'] ?? 0,
                        'finished_at' => current_time('mysql'),
                    ),
                    array('id' => $job_id),
                    array('%s', '%s', '%s', '%d', '%s'),
                    array('%d')
                );
                
                // Process next jobs in batch
                if ($job->batch_id) {
                    $this->process_next_jobs($job->batch_id, 2);
                }
                
            } else {
                throw new Exception($artifact_result['error']);
            }
            
        } catch (Exception $e) {
            // Handle failure
            $this->handle_job_failure($job_id, $e->getMessage());
        }
        
        do_action('rtai_wc_job_status_changed', $job_id, $job->status);
    }
    
    /**
     * Handle job failure
     */
    private function handle_job_failure($job_id, $error_message) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rtai_jobs';
        
        $job = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $job_id
        ));
        
        if (!$job) {
            return;
        }
        
        // Count retries
        $retry_count = intval($job->response ? json_decode($job->response, true)['retry_count'] ?? 0 : 0);
        
        if ($retry_count < self::MAX_RETRIES) {
            // Schedule retry
            $retry_count++;
            $delay = self::RETRY_DELAYS[$retry_count - 1] ?? 600;
            
            $wpdb->update(
                $table_name,
                array(
                    'status' => self::STATUS_QUEUED,
                    'response' => wp_json_encode(array('retry_count' => $retry_count)),
                    'error' => $error_message,
                ),
                array('id' => $job_id),
                array('%s', '%s', '%s'),
                array('%d')
            );
            
            $this->schedule_job($job_id, $delay);
            
        } else {
            // Mark as failed
            $wpdb->update(
                $table_name,
                array(
                    'status' => self::STATUS_FAILED,
                    'error' => $error_message,
                    'finished_at' => current_time('mysql'),
                ),
                array('id' => $job_id),
                array('%s', '%s', '%s'),
                array('%d')
            );
        }
    }
    
    /**
     * Get job status
     */
    public function get_job_status($job_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rtai_jobs';
        
        $job = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $job_id
        ));
        
        if (!$job) {
            return null;
        }
        
        return array(
            'id' => $job->id,
            'post_id' => $job->post_id,
            'artifact' => $job->artifact,
            'status' => $job->status,
            'error' => $job->error,
            'created_at' => $job->created_at,
            'started_at' => $job->started_at,
            'finished_at' => $job->finished_at,
            'response' => json_decode($job->response, true),
        );
    }
    
    /**
     * Get batch status
     */
    public function get_batch_status($batch_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rtai_jobs';
        
        $stats = $wpdb->get_results($wpdb->prepare(
            "SELECT status, COUNT(*) as count FROM $table_name 
             WHERE batch_id = %s GROUP BY status",
            $batch_id
        ));
        
        $status_counts = array();
        $total = 0;
        
        foreach ($stats as $stat) {
            $status_counts[$stat->status] = intval($stat->count);
            $total += intval($stat->count);
        }
        
        $completed = ($status_counts[self::STATUS_SUCCESS] ?? 0) + ($status_counts[self::STATUS_FAILED] ?? 0);
        $progress = $total > 0 ? round(($completed / $total) * 100) : 0;
        
        return array(
            'batch_id' => $batch_id,
            'total' => $total,
            'completed' => $completed,
            'progress' => $progress,
            'status_counts' => $status_counts,
            'is_complete' => $completed >= $total,
        );
    }
    
    /**
     * Cancel all jobs in batch
     */
    public function cancel_batch($batch_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rtai_jobs';
        
        $wpdb->update(
            $table_name,
            array('status' => self::STATUS_CANCELLED),
            array('batch_id' => $batch_id, 'status' => self::STATUS_QUEUED),
            array('%s'),
            array('%s', '%s')
        );
    }
    
    /**
     * Cancel all pending jobs
     */
    public function cancel_all_jobs() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rtai_jobs';
        
        $wpdb->update(
            $table_name,
            array('status' => self::STATUS_CANCELLED),
            array('status' => self::STATUS_QUEUED),
            array('%s'),
            array('%s')
        );
    }
    
    /**
     * Cleanup old jobs
     */
    public function cleanup_old_jobs() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rtai_jobs';
        
        // Delete jobs older than 30 days
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE created_at < %s",
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ));
    }
    
    /**
     * Get recent jobs for user
     */
    public function get_recent_jobs($user_id = null, $limit = 20) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rtai_jobs';
        
        $where = '';
        $args = array();
        
        if ($user_id) {
            $where = 'WHERE user_id = %d';
            $args[] = $user_id;
        }
        
        $args[] = $limit;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name $where 
             ORDER BY created_at DESC LIMIT %d",
            ...$args
        ));
    }
    
    /**
     * Get usage statistics
     */
    public function get_usage_stats($days = 30) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'rtai_jobs';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE(created_at) as date,
                COUNT(*) as total_jobs,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful_jobs,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_jobs,
                SUM(tokens) as total_tokens
             FROM $table_name 
             WHERE created_at >= %s 
             GROUP BY DATE(created_at) 
             ORDER BY date DESC",
            date('Y-m-d', strtotime("-$days days"))
        ));
    }
}