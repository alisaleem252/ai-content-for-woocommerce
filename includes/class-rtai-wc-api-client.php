<?php
/**
 * RapidTextAI API Client
 */

if (!defined('ABSPATH')) {
    exit;
}

class RTAI_WC_API_Client {
    
    private static $instance = null;
    
    const API_BASE_URL = 'https://app.rapidtextai.com/openai/v1/';
    const TIMEOUT = 30;
    
    private $api_key;
    private $quota_cache = null;
    private $cache_expiry = 0;
    
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
        $this->load_settings();
    }
    
    /**
     * Load settings
     */
    private function load_settings() {
        $settings = get_option('rtai_wc_settings', array());
        $this->api_key = $settings['api_key'] ?? '';
    }
    
    /**
     * Set API key
     */
    public function set_api_key($api_key) {
        $this->api_key = $api_key;
        
        $settings = get_option('rtai_wc_settings', array());
        $settings['api_key'] = $api_key;
        update_option('rtai_wc_settings', $settings);
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        if (empty($this->api_key)) {
            throw new Exception(esc_html__('API key is required.', 'ai-content-for-woocommerce'));
        }
        
        $response = $this->make_request('auth/test', array(), 'GET');
        
        if (is_wp_error($response)) {
            throw new Exception(esc_html($response->get_error_message()));
        }
        
        return array(
            'success' => true,
            'user' => $response['user'] ?? array(),
            'plan' => $response['plan'] ?? array(),
            'quota' => $response['quota'] ?? array(),
        );
    }
    
    /**
     * Get user quota
     */
    public function get_quota($force_refresh = false) {
        // Check cache
        if (!$force_refresh && $this->quota_cache && time() < $this->cache_expiry) {
            return $this->quota_cache;
        }
        
        try {
            $response = $this->make_request('account/quota', array(), 'GET');
            
            if (!is_wp_error($response)) {
                $this->quota_cache = $response;
                $this->cache_expiry = time() + 300; // Cache for 5 minutes
                
                // Save to option for persistent cache
                update_option('rtai_wc_usage_cache', array(
                    'quota' => $response,
                    'expires' => $this->cache_expiry
                ));
            }
            
            return $response;
        } catch (Exception $e) {
            // Return cached data if available
            $cached = get_option('rtai_wc_usage_cache', array());
            if (!empty($cached['quota'])) {
                return $cached['quota'];
            }
            
            throw $e;
        }
    }
    
    /**
     * Generate content (non-streaming version for background jobs)
     */
    public function generate_sync($artifact, $context, $model_profile = null) {
        if (empty($this->api_key)) {
            throw new Exception(esc_html__('API key is required.', 'ai-content-for-woocommerce'));
        }
        
        $settings = get_option('rtai_wc_settings', array());
        
        // Build the prompt using template and context
        $prompt = $this->build_prompt($artifact, $context, $settings);
        
        // Prepare OpenAI-compatible payload (no streaming)
        $payload = array(
            'model' => $model_profile ?: ($settings['model_profile'] ?? 'gpt-4o'),
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => $this->get_system_prompt($artifact, $settings)
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'temperature' => $settings['temperature'] ?? 0.7,
            'max_tokens' => $settings['max_tokens'] ?? 2000,
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
            'stream' => false,
        );
        
        $response = $this->make_request('chat/completions', $payload);
        
        if (is_wp_error($response)) {
            throw new Exception(esc_html($response->get_error_message()));
        }
        
        // Clear quota cache to force refresh
        $this->quota_cache = null;
        
        // Transform OpenAI response to our expected format
        return $this->transform_completion_response($response, $artifact);
    }
    
    /**
     * Generate content
     */
    public function generate($artifact, $context, $model_profile = null) {
        if (empty($this->api_key)) {
            throw new Exception(esc_html__('API key is required.', 'ai-content-for-woocommerce'));
        }
        
        $settings = get_option('rtai_wc_settings', array());
        
        // Build the prompt using template and context
        $prompt = $this->build_prompt($artifact, $context, $settings);
        
        // Prepare OpenAI-compatible payload
        $payload = array(
            'model' => $model_profile ?: ($settings['model_profile'] ?? 'gpt-4o'),
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => $this->get_system_prompt($artifact, $settings)
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'temperature' => $settings['temperature'] ?? 0.7,
            'max_tokens' => $settings['max_tokens'] ?? 2000,
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
        );
        
        // Add streaming parameter
        $payload['stream'] = true;
        
        $response = $this->make_request('chat/completions-stream', $payload, 'POST', true);
        
        if (is_wp_error($response)) {
            throw new Exception(esc_html($response->get_error_message()));
        }
        
        // Clear quota cache to force refresh
        $this->quota_cache = null;
        
        // For streaming, return the response directly
        return $response;
    }
    
    /**
     * Build prompt from template and context
     */
    private function build_prompt($artifact, $context, $settings) {
        $template = $settings['templates'][$artifact] ?? $this->get_default_template($artifact);
        
        // Replace variables in template
        $prompt = $template;
        
        // Basic replacements
        $replacements = array(
            '{product_name}' => $context['product_name'] ?? '',
            '{categories}' => is_array($context['categories']) ? implode(', ', $context['categories']) : '',
            '{attributes}' => $this->format_attributes($context['attributes'] ?? array()),
            '{price}' => $context['price'] ?? '',
            '{audience}' => $context['audience'] ?? '',
            '{tone}' => $context['tone'] ?? $settings['tone'] ?? 'professional',
            '{keywords}' => $context['keywords'] ?? '',
            '{features}' => $context['features'] ?? '',
        );
        
        foreach ($replacements as $placeholder => $value) {
            $prompt = str_replace($placeholder, $value, $prompt);
        }
        
        // Add context information
        $context_info = $this->build_context_info($context);
        if (!empty($context_info)) {
            $prompt .= "\n\nProduct Information:\n" . $context_info;
        }
        
        return $prompt;
    }
    
    /**
     * Get system prompt for artifact type
     */
    private function get_system_prompt($artifact, $settings) {
        $base_prompt = "You are a professional copywriter specializing in e-commerce content. Generate direct, concise content without conversational phrases like 'of course', 'certainly', or 'I'd be happy to help'. Focus on delivering the requested content immediately without explanations or introductory text. ";
        
        // Add safety filters
        if ($settings['profanity_filter'] ?? true) {
            $base_prompt .= "Avoid any profanity or inappropriate language. ";
        }
        
        if ($settings['brand_safety'] ?? true) {
            $base_prompt .= "Ensure content is brand-safe and appropriate for all audiences. ";
        }
        
        // Artifact-specific instructions
        switch ($artifact) {
            case 'title':
                return $base_prompt . "Generate compelling, SEO-friendly product titles that capture attention and highlight key benefits. Keep titles concise but descriptive.";
            
            case 'short_description':
                return $base_prompt . "Write brief, engaging product descriptions (2-3 sentences) that summarize the main benefits and appeal to customers.";
            
            case 'long_description':
                return $base_prompt . "Create detailed product descriptions that include features, benefits, use cases, and specifications. Use persuasive language and structure content with paragraphs or bullet points.";
            
            case 'seo_title':
                return $base_prompt . "Generate SEO-optimized titles for search engines. Include target keywords naturally while maintaining readability. Keep under 60 characters.";
            
            case 'seo_description':
                return $base_prompt . "Write SEO meta descriptions that encourage clicks from search results. Include target keywords and keep under 155 characters.";
            
            case 'bullets':
                return $base_prompt . "Create 5-7 bullet points highlighting key features and benefits. Use action-oriented language and focus on customer value.";
            
            case 'faq':
                return $base_prompt . "Generate 5 frequently asked questions and comprehensive answers about the product. Focus on common customer concerns and objections.";
            
            case 'attributes':
                return $base_prompt . "Extract product specifications and attributes from the description. Return as key-value pairs.";
            
            default:
                return $base_prompt . "Generate high-quality e-commerce content based on the provided template and context.";
        }
    }
    
    /**
     * Get default template for artifact
     */
    private function get_default_template($artifact) {
        $templates = array(
            'title' => 'Generate a compelling product title for {product_name} that highlights its key features and appeals to {audience}.',
            'short_description' => 'Write a brief, engaging product description for {product_name} that captures the main benefits in 2-3 sentences.',
            'long_description' => 'Create a detailed product description for {product_name} including features, benefits, and use cases. Use a {tone} tone.',
            'seo_title' => 'Generate an SEO-optimized title for {product_name} targeting keywords: {keywords}',
            'seo_description' => 'Write an SEO meta description for {product_name} that includes target keywords and stays under 155 characters.',
            'bullets' => 'Create 5-7 bullet points highlighting the key features and benefits of {product_name}.',
            'faq' => 'Generate 5 frequently asked questions and answers about {product_name}.',
            'attributes' => 'Extract key product specifications and attributes for {product_name}.',
        );
        
        return $templates[$artifact] ?? 'Generate content for {product_name}.';
    }
    
    /**
     * Format attributes for prompt
     */
    private function format_attributes($attributes) {
        if (empty($attributes) || !is_array($attributes)) {
            return '';
        }
        
        $formatted = array();
        foreach ($attributes as $key => $value) {
            if (is_array($value)) {
                $formatted[] = $key . ': ' . implode(', ', $value);
            } else {
                $formatted[] = $key . ': ' . $value;
            }
        }
        
        return implode('; ', $formatted);
    }
    
    /**
     * Build context information string
     */
    private function build_context_info($context) {
        $info_parts = array();
        
        if (!empty($context['product_name'])) {
            $info_parts[] = "Product: " . $context['product_name'];
        }
        
        if (!empty($context['categories']) && is_array($context['categories'])) {
            $info_parts[] = "Categories: " . implode(', ', $context['categories']);
        }
        
        if (!empty($context['price'])) {
            $info_parts[] = "Price: " . $context['price'];
        }
        
        if (!empty($context['current_content']['title'])) {
            $info_parts[] = "Current Title: " . $context['current_content']['title'];
        }
        
        if (!empty($context['current_content']['short_description'])) {
            $info_parts[] = "Current Short Description: " . wp_trim_words($context['current_content']['short_description'], 20);
        }
        
        if (!empty($context['variations']) && is_array($context['variations'])) {
            $info_parts[] = "Variations: " . count($context['variations']) . " variants available";
        }
        
        return implode("\n", $info_parts);
    }
    
    /**
     * Transform OpenAI completion response to our expected format
     */
    private function transform_completion_response($response, $artifact) {
        if (empty($response['choices']) || !isset($response['choices'][0]['message']['content'])) {
            throw new Exception(esc_html__('Invalid response format from API.', 'ai-content-for-woocommerce'));
        }
        
        $content = trim($response['choices'][0]['message']['content']);
        $usage = $response['usage'] ?? array();
        
        // Calculate estimated cost based on usage
        $cost_estimate = 0;
        if (!empty($usage['total_tokens'])) {
            // Rough estimate - adjust based on actual pricing
            $cost_estimate = ($usage['total_tokens'] / 1000) * 0.02;
        }
        
        return array(
            'content' => $content,
            'model' => $response['model'] ?? 'unknown',
            'usage' => array(
                'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
                'completion_tokens' => $usage['completion_tokens'] ?? 0,
                'total_tokens' => $usage['total_tokens'] ?? 0,
            ),
            'cost_estimate' => $cost_estimate,
            'artifact' => $artifact,
            'created' => $response['created'] ?? time(),
        );
    }
    
    /**
     * Get available models
     */
    public function get_models() {
        try {
            $response = $this->make_request('models', array(), 'GET');
            return is_wp_error($response) ? array() : $response;
        } catch (Exception $e) {
            return array();
        }
    }
    
    /**
     * Make API request
     */
    private function make_request($endpoint, $data = array(), $method = 'POST', $streaming = false) {
        $url = self::API_BASE_URL . ltrim($endpoint, '/');
        
        $headers = array(
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json',
            'User-Agent' => 'ai-content-for-woocommerce/' . RTAI_WC_VERSION,
        );
        
        // Add streaming headers
        if ($streaming) {
            $headers['Accept'] = 'text/event-stream';
            $headers['Cache-Control'] = 'no-cache';
        }
        
        $args = array(
            'method' => $method,
            'headers' => $headers,
            'timeout' => $streaming ? 120 : self::TIMEOUT, // Longer timeout for streaming
            'httpversion' => '1.1',
            'blocking' => true,
            'sslverify' => true,
        );
        
        if ($method === 'POST' && !empty($data)) {
            $args['body'] = wp_json_encode($data);
        } elseif ($method === 'GET' && !empty($data)) {
            $url = add_query_arg($data, $url);
        }
        
        // For streaming requests, we need to handle the response differently
        if ($streaming) {
            return $this->handle_streaming_request($url, $args);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        // Log the request for debugging
        $this->log_request($endpoint, $method, $data, $response_code, $response_body);
        
        if ($response_code >= 400) {
            $error_data = json_decode($response_body, true);
            $error_message = $error_data['message'] ?? $error_data['error'] ?? 'Unknown API error';
            
            // Handle specific error codes
            switch ($response_code) {
                case 401:
                    $error_message = __('Invalid API key. Please check your RapidTextAI credentials.', 'ai-content-for-woocommerce');
                    break;
                case 429:
                    $error_message = __('Rate limit exceeded. Please wait a moment and try again.', 'ai-content-for-woocommerce');
                    break;
                case 402:
                    $error_message = __('Quota exceeded. Please upgrade your RapidTextAI plan.', 'ai-content-for-woocommerce');
                    break;
                case 500:
                case 502:
                case 503:
                    $error_message = __('RapidTextAI service temporarily unavailable. Please try again later.', 'ai-content-for-woocommerce');
                    break;
            }
            
            return new WP_Error('rtai_api_error', $error_message, array(
                'status' => $response_code,
                'response' => $error_data
            ));
        }
        
        $decoded_response = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('rtai_json_error', __('Invalid JSON response from API.', 'ai-content-for-woocommerce'));
        }
        
        return $decoded_response;
    }
    
    /**
     * Handle streaming request
     */
    private function handle_streaming_request($url, $args) {
        // For WordPress, we'll return a special response that the frontend can use
        // to make its own streaming request via JavaScript
        return array(
            'stream_url' => $url,
            'headers' => $args['headers'],
            'body' => $args['body'] ?? '',
            'method' => $args['method'] ?? 'POST',
        );
    }
    
    /**
     * Log API request for debugging
     */
    private function log_request($endpoint, $method, $data, $response_code, $response_body) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'endpoint' => $endpoint,
            'method' => $method,
            'request_data' => $data,
            'response_code' => $response_code,
            'response_size' => strlen($response_body),
        );
        
        // Don't log full response in production
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('RTAI API Request: ' . wp_json_encode($log_entry));
        }
    }
    
    /**
     * Get model profiles
     */
    public function get_model_profiles() {
        return array(
            'gpt-4o' => array(
                'name' => 'GPT-4o',
                'provider' => 'openai',
                'description' => 'Latest GPT-4 Omni model with superior reasoning',
                'max_tokens' => 4096,
                'cost_per_1k' => 0.03,
            ),
            'gpt-4o-mini' => array(
                'name' => 'GPT-4o Mini',
                'provider' => 'openai', 
                'description' => 'Faster, cost-effective GPT-4 variant',
                'max_tokens' => 4096,
                'cost_per_1k' => 0.015,
            ),
            'claude-3-5-sonnet' => array(
                'name' => 'Claude 3.5 Sonnet',
                'provider' => 'anthropic',
                'description' => 'Anthropic\'s most capable model',
                'max_tokens' => 4096,
                'cost_per_1k' => 0.03,
            ),
            'gemini-1-5-pro' => array(
                'name' => 'Gemini 1.5 Pro',
                'provider' => 'google',
                'description' => 'Google\'s advanced multimodal model',
                'max_tokens' => 8192,
                'cost_per_1k' => 0.025,
            ),
            'deepseek-chat' => array(
                'name' => 'DeepSeek Chat',
                'provider' => 'deepseek',
                'description' => 'High-quality, cost-effective model',
                'max_tokens' => 4096,
                'cost_per_1k' => 0.002,
            ),
        );
    }
    
    /**
     * Check if API key is valid
     */
    public function is_connected() {
        try {
            $this->test_connection();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get usage statistics
     */
    public function get_usage_stats($days = 30) {
        try {
            $response = $this->make_request('account/usage', array(
                'days' => $days,
                'source' => 'woocommerce'
            ), 'GET');
            
            return is_wp_error($response) ? array() : $response;
        } catch (Exception $e) {
            return array();
        }
    }
}