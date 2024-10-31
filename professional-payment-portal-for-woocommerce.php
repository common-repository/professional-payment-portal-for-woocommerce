<?php

/**
 * Professional Payment Portal for WooCommerce.
 *
 * @author            CodeBrain BV
 * @copyright         2024 CodeBrain BV
 *
 * @wordpress-plugin
 * Plugin Name:       Professional Payment Portal for WooCommerce
 * Plugin URI:        https://bitbucket.org/codebrainbv/ppp-woocommerce/
 * Description:       Accept payments through the Professional Payment Portal
 * Version:           1.0.2
 * Requires at least: 6.4
 * Tested up to:      6.6.1
 * Requires PHP:      7.4
 * WC tested up to:   9.1.4
 * Author:            CodeBrain BV
 * Author URI:        https://www.codebrain.nl/
 * Text Domain:       professional-payment-portal-for-woocommerce
 * Domain Path: 	  /languages
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Block output if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Plugin version
if (!defined('PPP4WOO_VERSION')) {
    define('PPP4WOO_VERSION', '1.0.1');
}

if (!defined('PPP4WOO_ROOT_PATH')) {
    define('PPP4WOO_ROOT_PATH', plugin_dir_path(__FILE__));
}
if (!defined('PPP4WOO_ROOT_URL')) {
    define('PPP4WOO_ROOT_URL', plugin_dir_url(__FILE__));
}

// Load default plugin functions
require_once ABSPATH.DIRECTORY_SEPARATOR.'wp-admin'.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'plugin.php';

// Load our libraries
if (!defined('PPP4WOO_FUNCTIONS_LOADED')) {
    require_once PPP4WOO_ROOT_PATH.'includes'.DIRECTORY_SEPARATOR.'functions.php';
}

// Load text domain
load_plugin_textdomain('professional-payment-portal-for-woocommerce', false, plugin_basename(dirname(__FILE__)).DIRECTORY_SEPARATOR.'languages/');

// Check if cUrl is installed on this server
if (!function_exists('curl_version')) {
    function ppp4woo_doShowCurlError()
    {
        echo '<div class="error"><p>Curl is not installed.<br>In order to use the PPP, you must install install CURL.<br>Ask your system administrator/hosting provider to install php_curl</p></div>';
    }
    add_action('admin_notices', 'ppp4woo_doShowCurlError');
}

// Is WooCommerce active on this Wordpress installation?
if (is_plugin_active('woocommerce'.DIRECTORY_SEPARATOR.'woocommerce.php') || is_plugin_active_for_network('woocommerce'.DIRECTORY_SEPARATOR.'woocommerce.php')) {
    include PPP4WOO_ROOT_PATH.'controllers'.DIRECTORY_SEPARATOR.'ppp4woo-controller.php';
    Ppp4wooController::init();
} else {
    // Woocommerce isn't active, show error
    function ppp4woo_doShowWoocommerceError()
    {
        echo '<div class="error"><p>Professional Payment Portal plugin requires WooCommerce to be active</p></div>';
    }
    add_action('admin_notices', 'ppp4woo_doShowWoocommerceError');
}

function ppp4woo_appendLinks($links_array, $plugin_file_name, $plugin_data, $status)
{
    if (strpos($plugin_file_name, basename(__FILE__))) {
        $links_array[] = '<a href="https://support.payocity.nl" target="_blank">Documentation/Support</a>';
    }

    return $links_array;
}
add_filter('plugin_row_meta', 'ppp4woo_appendLinks', 10, 4);

function ppp4woo_PluginLinks($links)
{
    $actionLinks = [
        'settings' => '<a href="'.admin_url('admin.php?page=wc-settings&tab=checkout').'" aria-label="'.esc_attr__('View WooCommerce settings', 'professional-payment-portal-for-woocommerce').'">'.esc_html__('Settings', 'professional-payment-portal-for-woocommerce').'</a>',
    ];

    return array_merge($actionLinks, $links);
}
add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'ppp4woo_PluginLinks');

add_action('plugins_loaded', 'ppp4woo_includeGateways', 0);
function ppp4woo_includeGateways()
{
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    } // if the WC payment gateway class

    include PPP4WOO_ROOT_PATH.'gateways/abstract.php';
    include PPP4WOO_ROOT_PATH.'gateways/ideal.php';
    // include PPP4WOO_ROOT_PATH.'gateways/ideal_fast.php';
}

add_filter('woocommerce_payment_gateways', 'ppp4woo_addGateways');

function ppp4woo_addGateways($gateways)
{
    $gateways[] = 'ppp4woo_ideal';
    // $gateways[] = 'ppp4woo_ideal_fast';

    return $gateways;
}

/*
 * Custom function to declare compatibility with cart_checkout_blocks feature.
 */
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);

        // Declare compatibility for 'custom_order_tables'
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

function ppp4woo_registerPaymentMethod()
{
    // Check if the required class exists
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }

    // Include the custom Blocks Checkout class
    require_once PPP4WOO_ROOT_PATH.'blocks/ideal.php';

    // Hook the registration function to the 'woocommerce_blocks_payment_method_type_registration' action
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
            // Register an instance of ppp4woo_ideal_blocks
            $payment_method_registry->register(new Ppp4woo_Ideal_Blocks());
        }
    );
}

// Hook the custom function to the 'woocommerce_blocks_loaded' action
add_action('woocommerce_blocks_loaded', 'ppp4woo_registerPaymentMethod');
