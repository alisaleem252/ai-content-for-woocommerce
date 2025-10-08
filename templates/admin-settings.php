<?php
/**
 * Admin Settings Page Template
 */
if (!defined('ABSPATH')) {
    exit;
}

$model_options = RTAI_WC_Admin_UI::get_instance()->get_model_options();
$tone_options = RTAI_WC_Admin_UI::get_instance()->get_tone_options();
$integrations = RTAI_WC_Integrations::get_instance()->get_integration_status();
$current_api_key = $settings['api_key'] ?? '';
?>

<div class="wrap rtai-wc-settings">
    <h1><?php esc_html_e('RapidTextAI for WooCommerce', 'ai-content-for-woocommerce'); ?></h1>
    
    <?php settings_errors('rtai_wc_settings'); ?>
    
    <?php if (isset($connection_status)): ?>
        <div class="notice <?php echo isset($connection_status['error']) ? 'notice-error' : 'notice-success'; ?>">
            <p>
                <?php if (isset($connection_status['error'])): ?>
                    <strong><?php esc_html_e('Connection Failed:', 'ai-content-for-woocommerce'); ?></strong>
                    <?php echo esc_html($connection_status['error']); ?>
                <?php else: ?>
                    <strong><?php esc_html_e('Connection Successful!', 'ai-content-for-woocommerce'); ?></strong>
                    <?php esc_html_e('Connected to RapidTextAI successfully.', 'ai-content-for-woocommerce'); ?>
                <?php endif; ?>
            </p>
        </div>
    <?php endif; ?>
    
    <div class="rtai-tabs">
        <nav class="nav-tab-wrapper">
            <a href="#connection" class="nav-tab nav-tab-active"><?php esc_html_e('Connection', 'ai-content-for-woocommerce'); ?></a>
            <a href="#models" class="nav-tab"><?php esc_html_e('Models & Settings', 'ai-content-for-woocommerce'); ?></a>
            <a href="#templates" class="nav-tab"><?php esc_html_e('Content Templates', 'ai-content-for-woocommerce'); ?></a>
            <a href="#integrations" class="nav-tab"><?php esc_html_e('Integrations', 'ai-content-for-woocommerce'); ?></a>
            <a href="#usage" class="nav-tab"><?php esc_html_e('Usage & Logs', 'ai-content-for-woocommerce'); ?></a>
        </nav>
        
        <form method="post" action="" id="rtai_wc_auth_form">
            <?php wp_nonce_field('rtai_wc_settings'); ?>
            
            <!-- Connection Tab -->
            <div id="connection" class="tab-content active">
                <h2><?php esc_html_e('API Connection', 'ai-content-for-woocommerce'); ?></h2>
                
                <div class="rtai-auth-section">
                    <div class="rtai-auth-button-wrapper">
                        <button type="button" id="rtai_wc_auth_button" class="button button-primary">
                            <span class="dashicons dashicons-admin-network"></span>
                            <?php esc_html_e('Authenticate with RapidTextAI', 'ai-content-for-woocommerce'); ?>
                        </button>
                    </div>
                    
                    <div id="rtai_wc_status_message" class="rtai-status-message"></div>
                    
                    <?php if (!empty($current_api_key)): ?>
                        <div class="notice notice-success inline">
                            <p>
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php esc_html_e('API Key is already configured. You can re-authenticate to refresh your connection.', 'ai-content-for-woocommerce'); ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="api_key"><?php esc_html_e('API Key', 'ai-content-for-woocommerce'); ?></label>
                        </th>
                        <td>
                            <input type="password" id="api_key" name="api_key" 
                                   value="<?php echo esc_attr($current_api_key); ?>" 
                                   class="regular-text" />
                            <button type="button" class="button" id="toggle-api-key">
                                <?php esc_html_e('Show', 'ai-content-for-woocommerce'); ?>
                            </button>
                            <p class="description">
                                <?php 
                                /* translators: %1$s: opening link tag, %2$s: closing link tag */
                                printf(
                                    esc_html__('Get your API key from %1$sRapidTextAI Dashboard%2$s or use the authenticate button above.', 'ai-content-for-woocommerce'),
                                    '<a href="https://app.rapidtextai.com/dashboard" target="_blank">',
                                    '</a>'
                                );
                                ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <div class="rtai-connection-status">
                    <?php if (!empty($current_api_key)): ?>
                        <h3><?php esc_html_e('Connection Status', 'ai-content-for-woocommerce'); ?></h3>
                        
                        <div id="rtai_wc_account_status" class="rtai-status-loading">
                            <div class="rtai-spinner"></div>
                            <span><?php esc_html_e('Loading account information...', 'ai-content-for-woocommerce'); ?></span>
                        </div>
                        
                        <p>
                            <button type="submit" name="test_connection" class="button button-secondary">
                                <?php esc_html_e('Test Connection', 'ai-content-for-woocommerce'); ?>
                            </button>
                        </p>
                    <?php else: ?>
                        <div class="rtai-connect-banner">
                            <h3><?php esc_html_e('Connect to RapidTextAI', 'ai-content-for-woocommerce'); ?></h3>
                            <p><?php esc_html_e('Get started with AI-powered content generation for your WooCommerce store!', 'ai-content-for-woocommerce'); ?></p>
                            <a href="https://app.rapidtextai.com/signup?source=woocommerce" target="_blank" class="button button-primary">
                                <?php esc_html_e('Create Account', 'ai-content-for-woocommerce'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Models Tab -->
            <div id="models" class="tab-content">
                <h2><?php esc_html_e('AI Model Settings', 'ai-content-for-woocommerce'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="model_profile"><?php esc_html_e('Default Model', 'ai-content-for-woocommerce'); ?></label>
                        </th>
                        <td>
                            <select id="model_profile" name="model_profile">
                                <?php foreach ($model_options as $value => $label): ?>
                                    <option value="<?php echo esc_attr($value); ?>" 
                                            <?php selected($settings['model_profile'] ?? 'gpt-4o', $value); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php esc_html_e('Choose the AI model for content generation. Different models have different capabilities and costs.', 'ai-content-for-woocommerce'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="temperature"><?php esc_html_e('Creativity Level', 'ai-content-for-woocommerce'); ?></label>
                        </th>
                        <td>
                            <input type="range" id="temperature" name="temperature" 
                                   min="0" max="2" step="0.1" 
                                   value="<?php echo esc_attr($settings['temperature'] ?? 0.7); ?>" />
                            <span class="temperature-value"><?php echo esc_html($settings['temperature'] ?? 0.7); ?></span>
                            <p class="description">
                                <?php esc_html_e('Lower values = more focused and deterministic. Higher values = more creative and varied.', 'ai-content-for-woocommerce'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="max_tokens"><?php esc_html_e('Max Length', 'ai-content-for-woocommerce'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="max_tokens" name="max_tokens" 
                                   value="<?php echo esc_attr($settings['max_tokens'] ?? 2000); ?>" 
                                   min="100" max="8000" class="small-text" />
                            <p class="description">
                                <?php esc_html_e('Maximum number of tokens (words) to generate per request.', 'ai-content-for-woocommerce'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="tone"><?php esc_html_e('Default Tone', 'ai-content-for-woocommerce'); ?></label>
                        </th>
                        <td>
                            <select id="tone" name="tone">
                                <?php foreach ($tone_options as $value => $label): ?>
                                    <option value="<?php echo esc_attr($value); ?>" 
                                            <?php selected($settings['tone'] ?? 'professional', $value); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <h3><?php esc_html_e('Content Safety', 'ai-content-for-woocommerce'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Safety Filters', 'ai-content-for-woocommerce'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="profanity_filter" value="1" 
                                           <?php checked($settings['profanity_filter'] ?? true); ?> />
                                    <?php esc_html_e('Enable profanity filter', 'ai-content-for-woocommerce'); ?>
                                </label><br>
                                
                                <label>
                                    <input type="checkbox" name="brand_safety" value="1" 
                                           <?php checked($settings['brand_safety'] ?? true); ?> />
                                    <?php esc_html_e('Enable brand safety filter', 'ai-content-for-woocommerce'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Templates Tab -->
            <div id="templates" class="tab-content">
                <h2><?php esc_html_e('Content Templates', 'ai-content-for-woocommerce'); ?></h2>
                <p><?php esc_html_e('Customize the prompts used for each type of content generation. Use placeholders like {product_name}, {categories}, {attributes}, etc.', 'ai-content-for-woocommerce'); ?></p>
                
                <div class="template-sections">
                    <?php 
                    $template_fields = array(
                        'title' => __('Product Title', 'ai-content-for-woocommerce'),
                        'short_description' => __('Short Description', 'ai-content-for-woocommerce'),
                        'long_description' => __('Long Description', 'ai-content-for-woocommerce'),
                        'seo_title' => __('SEO Title', 'ai-content-for-woocommerce'),
                        'seo_description' => __('SEO Meta Description', 'ai-content-for-woocommerce'),
                        'bullets' => __('Feature Bullets', 'ai-content-for-woocommerce'),
                        'faq' => __('FAQ Content', 'ai-content-for-woocommerce'),
                    );
                    ?>
                    
                    <?php foreach ($template_fields as $field => $label): ?>
                        <div class="template-section">
                            <h3><?php echo esc_html($label); ?></h3>
                            <textarea name="template_<?php echo esc_attr($field); ?>" 
                                      rows="3" class="large-text"><?php 
                                echo esc_textarea($settings['templates'][$field] ?? ''); 
                            ?></textarea>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="template-variables">
                    <h3><?php esc_html_e('Available Variables', 'ai-content-for-woocommerce'); ?></h3>
                    <div class="variables-grid">
                        <div class="variable-item">
                            <code>{product_name}</code>
                            <span><?php esc_html_e('Product name/title', 'ai-content-for-woocommerce'); ?></span>
                        </div>
                        <div class="variable-item">
                            <code>{categories}</code>
                            <span><?php esc_html_e('Product categories', 'ai-content-for-woocommerce'); ?></span>
                        </div>
                        <div class="variable-item">
                            <code>{attributes}</code>
                            <span><?php esc_html_e('Product attributes', 'ai-content-for-woocommerce'); ?></span>
                        </div>
                        <div class="variable-item">
                            <code>{price}</code>
                            <span><?php esc_html_e('Product price', 'ai-content-for-woocommerce'); ?></span>
                        </div>
                        <div class="variable-item">
                            <code>{audience}</code>
                            <span><?php esc_html_e('Target audience', 'ai-content-for-woocommerce'); ?></span>
                        </div>
                        <div class="variable-item">
                            <code>{tone}</code>
                            <span><?php esc_html_e('Content tone', 'ai-content-for-woocommerce'); ?></span>
                        </div>
                        <div class="variable-item">
                            <code>{keywords}</code>
                            <span><?php esc_html_e('Target keywords', 'ai-content-for-woocommerce'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Integrations Tab -->
            <div id="integrations" class="tab-content">
                <h2><?php esc_html_e('Plugin Integrations', 'ai-content-for-woocommerce'); ?></h2>
                
                <div class="integration-status">
                    <?php 
                    $integration_info = array(
                        'yoast' => array(
                            'name' => 'Yoast SEO',
                            'description' => __('Automatically populate Yoast SEO meta fields', 'ai-content-for-woocommerce'),
                        ),
                        'rankmath' => array(
                            'name' => 'Rank Math',
                            'description' => __('Automatically populate Rank Math SEO fields', 'ai-content-for-woocommerce'),
                        ),
                        'wpml' => array(
                            'name' => 'WPML',
                            'description' => __('Generate content in multiple languages', 'ai-content-for-woocommerce'),
                        ),
                        'polylang' => array(
                            'name' => 'Polylang',
                            'description' => __('Generate content in multiple languages', 'ai-content-for-woocommerce'),
                        ),
                        'elementor' => array(
                            'name' => 'Elementor',
                            'description' => __('AI content widget for Elementor editor', 'ai-content-for-woocommerce'),
                        ),
                        'product_addons' => array(
                            'name' => 'WooCommerce Product Add-ons',
                            'description' => __('Include add-on information in content generation', 'ai-content-for-woocommerce'),
                        ),
                        'acf' => array(
                            'name' => 'Advanced Custom Fields',
                            'description' => __('Include custom field data in content generation', 'ai-content-for-woocommerce'),
                        ),
                    );
                    ?>
                    
                    <?php foreach ($integration_info as $key => $info): ?>
                        <div class="integration-item <?php echo $integrations[$key] ? 'active' : 'inactive'; ?>">
                            <div class="integration-header">
                                <h3><?php echo esc_html($info['name']); ?></h3>
                                <span class="status-badge">
                                    <?php echo $integrations[$key] ? 
                                        esc_html__('Active', 'ai-content-for-woocommerce') : 
                                        esc_html__('Not Installed', 'ai-content-for-woocommerce'); ?>
                                </span>
                            </div>
                            <p><?php echo esc_html($info['description']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Usage Tab -->
            <div id="usage" class="tab-content">
                <h2><?php esc_html_e('Usage Statistics', 'ai-content-for-woocommerce'); ?></h2>
                
                <?php if ($quota): ?>
                    <div class="usage-overview">
                        <div class="usage-stat">
                            <h3><?php echo number_format($quota['used'] ?? 0); ?></h3>
                            <p><?php esc_html_e('Credits Used This Month', 'ai-content-for-woocommerce'); ?></p>
                        </div>
                        <div class="usage-stat">
                            <h3><?php echo number_format($quota['remaining'] ?? 0); ?></h3>
                            <p><?php esc_html_e('Credits Remaining', 'ai-content-for-woocommerce'); ?></p>
                        </div>
                        <div class="usage-stat">
                            <h3><?php echo esc_html($quota['plan']['name'] ?? 'Free'); ?></h3>
                            <p><?php esc_html_e('Current Plan', 'ai-content-for-woocommerce'); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="recent-activity">
                    <h3><?php esc_html_e('Recent Activity', 'ai-content-for-woocommerce'); ?></h3>
                    <div id="recent-jobs-list">
                        <p><?php esc_html_e('Loading recent activity...', 'ai-content-for-woocommerce'); ?></p>
                    </div>
                </div>
                
                <?php if (current_user_can('manage_options')): ?>
                    <div class="upgrade-section">
                        <h3><?php esc_html_e('Need More Credits?', 'ai-content-for-woocommerce'); ?></h3>
                        <p><?php esc_html_e('Upgrade your RapidTextAI plan to get more credits and unlock advanced features.', 'ai-content-for-woocommerce'); ?></p>
                        <a href="https://app.rapidtextai.com/pricing?source=woocommerce" target="_blank" class="button button-primary">
                            <?php esc_html_e('View Plans', 'ai-content-for-woocommerce'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <p class="submit">
                <input type="submit" name="submit" class="button-primary" 
                       value="<?php esc_attr_e('Save Settings', 'ai-content-for-woocommerce'); ?>" />
            </p>
        </form>
    </div>
</div>

<style>
    .rtai-auth-section {
        text-align: center;
        margin-bottom: 30px;
        padding: 20px;
        background: #f8f9fa;
        border: 1px solid #e2e4e7;
        border-radius: 8px;
    }

    .rtai-auth-button-wrapper {
        margin-bottom: 20px;
    }

    .rtai-status-message {
        min-height: 20px;
        margin-top: 16px;
    }

    .rtai-status-loading {
        display: flex;
        align-items: center;
        gap: 12px;
        color: #646970;
        font-size: 14px;
    }

    .rtai-spinner {
        width: 20px;
        height: 20px;
        border: 2px solid #e2e4e7;
        border-top: 2px solid #2271b1;
        border-radius: 50%;
        animation: rtai-spin 1s linear infinite;
    }

    @keyframes rtai-spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .rtai-connect-banner {
        text-align: center;
        padding: 20px;
        background: #f0f6fc;
        border: 1px solid #c3d7ea;
        border-radius: 8px;
    }

    #rtai_wc_account_status table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 16px;
    }

    #rtai_wc_account_status th,
    #rtai_wc_account_status td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #e2e4e7;
    }

    #rtai_wc_account_status th {
        background: #f8f9fa;
        font-weight: 600;
        color: #1d2327;
        width: 30%;
    }

    #rtai_wc_account_status td {
        color: #646970;
    }
</style>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        $('.tab-content').removeClass('active');
        $(target).addClass('active');
    });
    
    // RapidTextAI Authentication
    $('#rtai_wc_auth_button').on('click', function(e) {
        e.preventDefault();
        var authWindow = window.open('https://app.rapidtextai.com/log-in?action=popup', 'RapidTextAIAuth', 'width=500,height=600');
    });

    // Listen for authentication message
    window.addEventListener('message', function(event) {
        if (event.origin === 'https://app.rapidtextai.com') {
            var apiKey = event.data.api_key;
            if (apiKey) {
                $('#rtai_wc_status_message').html('Authentication successful! Saving API key...');
                $('#api_key').val(apiKey);

                $.post(ajaxurl, {
                    action: 'rtai_wc_save_api_key',
                    api_key: apiKey,
                    _wpnonce: '<?php echo esc_attr(wp_create_nonce('rtai_wc_save_api_key_nonce')); ?>'
                }, function(response) {
                    $('#rtai_wc_status_message').html(response.message);
                    if (response.success) {
                        $('#rtai_wc_status_message').append('<br><button id="rtai_wc_reload_button" class="button button-primary">Reload Page</button>');
                        $('#rtai_wc_reload_button').on('click', function() {
                            location.reload();
                        });
                    }
                });
            }
        }
    });
    
    // Toggle API key visibility
    $('#toggle-api-key').on('click', function() {
        var $input = $('#api_key');
        var $button = $(this);
        
        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $button.text('<?php esc_html_e('Hide', 'ai-content-for-woocommerce'); ?>');
        } else {
            $input.attr('type', 'password');
            $button.text('<?php esc_html_e('Show', 'ai-content-for-woocommerce'); ?>');
        }
    });
    
    // Temperature slider
    $('#temperature').on('input', function() {
        $('.temperature-value').text($(this).val());
    });
    
    // Load account status if API key exists
    <?php if (!empty($current_api_key)): ?>
    loadAccountStatus();
    
    function loadAccountStatus() {
        $.ajax({
            url: 'https://app.rapidtextai.com/api.php',
            type: 'GET',
            data: {
                gigsixkey: '<?php echo esc_js($current_api_key); ?>'
            },
            dataType: 'json',
            success: function(response_data) {
                var output = '';

                if (response_data.response_code) {
                    var code = response_data.response_code;

                    if (code == 1 || code == 2 || code == 4) {
                        output += '<table>';
                        output += '<tr><th><?php esc_html_e('Created', 'ai-content-for-woocommerce'); ?></th><td>' + (code == 1 ? response_data.create_at : 'N/A') + '</td></tr>';
                        output += '<tr><th><?php esc_html_e('Status', 'ai-content-for-woocommerce'); ?></th><td>' + (code == 1 ? response_data.subscription_status : 'Trial') + '</td></tr>';
                        output += '<tr><th><?php esc_html_e('Interval', 'ai-content-for-woocommerce'); ?></th><td>' + (code == 1 ? response_data.subscription_interval : 'N/A') + '</td></tr>';
                        output += '<tr><th><?php esc_html_e('Start', 'ai-content-for-woocommerce'); ?></th><td>' + (code == 1 ? response_data.current_period_start : 'N/A') + '</td></tr>';
                        output += '<tr><th><?php esc_html_e('End', 'ai-content-for-woocommerce'); ?></th><td>' + (code == 1 ? response_data.current_period_end : 'N/A') + '</td></tr>';
                        output += '<tr><th><?php esc_html_e('Requests', 'ai-content-for-woocommerce'); ?></th><td>' + (code == 1 ? response_data.requests + '/ ∞' : response_data.requests + '/ 100') + '</td></tr>';
                        output += '<tr><th><?php esc_html_e('Models', 'ai-content-for-woocommerce'); ?></th><td>' + response_data.models + '</td></tr>';
                        output += '</table>';
                    } else {
                        output = response_data.message;
                    }
                } else {
                    output = '<?php esc_html_e('Error retrieving data', 'ai-content-for-woocommerce'); ?>';
                }

                $('#rtai_wc_account_status').html(output);
            },
            error: function() {
                $('#rtai_wc_account_status').html('<?php esc_html_e('Error connecting to the server', 'ai-content-for-woocommerce'); ?>');
            }
        });
    }
    <?php endif; ?>
    
    // Load recent activity
    loadRecentActivity();
    
    function loadRecentActivity() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'rtai_wc_get_recent_activity',
                nonce: '<?php echo esc_attr(wp_create_nonce('rtai_wc_nonce')); ?>'
            },
            success: function(response) {
                if (response.success) {
                    displayRecentActivity(response.data);
                } else {
                    $('#recent-jobs-list').html('<p><?php esc_html_e('No recent activity found.', 'ai-content-for-woocommerce'); ?></p>');
                }
            },
            error: function() {
                $('#recent-jobs-list').html('<p><?php esc_html_e('Error loading recent activity.', 'ai-content-for-woocommerce'); ?></p>');
            }
        });
    }
    
    function displayRecentActivity(jobs) {
        var html = '<table class="wp-list-table widefat fixed striped">';
        html += '<thead><tr>';
        html += '<th><?php esc_html_e('Product', 'ai-content-for-woocommerce'); ?></th>';
        html += '<th><?php esc_html_e('Type', 'ai-content-for-woocommerce'); ?></th>';
        html += '<th><?php esc_html_e('Status', 'ai-content-for-woocommerce'); ?></th>';
        html += '<th><?php esc_html_e('Date', 'ai-content-for-woocommerce'); ?></th>';
        html += '</tr></thead><tbody>';
        
        if (jobs.length === 0) {
            html += '<tr><td colspan="4"><?php esc_html_e('No recent activity found.', 'ai-content-for-woocommerce'); ?></td></tr>';
        } else {
            jobs.forEach(function(job) {
                html += '<tr>';
                html += '<td>' + job.product_title + '</td>';
                html += '<td>' + job.artifact + '</td>';
                html += '<td><span class="status-' + job.status + '">' + job.status + '</span></td>';
                html += '<td>' + job.created_at + '</td>';
                html += '</tr>';
            });
        }
        
        html += '</tbody></table>';
        $('#recent-jobs-list').html(html);
    }
});
</script>