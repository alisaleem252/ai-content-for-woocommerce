<?php
/**
 * Plugin Name: AI Content for WooCommerce by RapidTextAI
 * Plugin URI: https://rapidtext.ai/woocommerce
 * Description: Generate AI-powered content for WooCommerce products with 1-click automation. Create titles, descriptions, SEO meta, FAQs, and translations using RapidTextAI's advanced models.
 * Version: 1.0.0
 * Author: RapidTextAI
 * Author URI: https://app.rapidtextai.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: rapidtextai-woocommerce
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 * Requires at least: 5.0
 * Tested up to: 6.5
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.5
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('RTAI_WC_VERSION', '1.0.0');
define('RTAI_WC_PLUGIN_FILE', __FILE__);
define('RTAI_WC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RTAI_WC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RTAI_WC_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>' . 
             esc_html__('RapidTextAI for WooCommerce requires WooCommerce to be installed and activated.', 'rapidtextai-woocommerce') . 
             '</p></div>';
    });
    return;
}

// Include core classes
require_once RTAI_WC_PLUGIN_DIR . 'includes/class-rtai-wc-plugin.php';
require_once RTAI_WC_PLUGIN_DIR . 'includes/class-rtai-wc-api-client.php';
require_once RTAI_WC_PLUGIN_DIR . 'includes/class-rtai-wc-admin-ui.php';
require_once RTAI_WC_PLUGIN_DIR . 'includes/class-rtai-wc-composer.php';
require_once RTAI_WC_PLUGIN_DIR . 'includes/class-rtai-wc-jobs.php';
require_once RTAI_WC_PLUGIN_DIR . 'includes/class-rtai-wc-rest-api.php';
require_once RTAI_WC_PLUGIN_DIR . 'includes/integrations/class-rtai-wc-integrations.php';

// Initialize the plugin
function rtai_wc_init() {
    if (class_exists('RTAI_WC_Plugin')) {
        RTAI_WC_Plugin::get_instance();
    }
}
add_action('plugins_loaded', 'rtai_wc_init');

// Activation hook
register_activation_hook(__FILE__, function() {
    if (class_exists('RTAI_WC_Plugin')) {
        RTAI_WC_Plugin::activate();
    }
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    if (class_exists('RTAI_WC_Plugin')) {
        RTAI_WC_Plugin::deactivate();
    }
});