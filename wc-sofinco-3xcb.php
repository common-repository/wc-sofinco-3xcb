<?php
/**
 * Plugin Name: Sofinco 3XCB
 * Description: Sofinco 3XCB payment gateway for WooCommerce
 * Version: 0.9.9.5
 * Author: Verifone e-commerce
 * Author URI: http://www.sofinco.com
 * Text Domain: wc-sofinco-3xcb
 *
 * @package WordPress
 * @since 0.9.0
 */
// Ensure not called directly
if (!defined('ABSPATH')) {
    exit;
}

function isWoocommerceActiveSofinco()
{
    // Makes sure the WC_Payment_Gateway is defined before trying to use it
    if (!class_exists('WC_Payment_Gateway')) {
        return false;
    }
    return true;
}

defined('WC_SOFINCO_PLUGIN') or define('WC_SOFINCO_PLUGIN', 'wc-sofinco-3xcb');
defined('WC_SOFINCO_VERSION') or define('WC_SOFINCO_VERSION', '0.9.9.5');
defined('WC_SOFINCO_KEY_PATH') or define('WC_SOFINCO_KEY_PATH', ABSPATH . '/kek.php');
defined('WC_SOFINCO_PLUGIN_URL') or define('WC_SOFINCO_PLUGIN_URL', plugin_dir_url(__FILE__));

function woocommerce_sofinco_installation()
{
    global $wpdb;
    $installed_ver = get_option(WC_SOFINCO_PLUGIN . '_version');

    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    if (!isWoocommerceActiveSofinco()) {
        _e('WooCommerce must be activated', WC_SOFINCO_PLUGIN);
        die();
    }

    if ($installed_ver != WC_SOFINCO_VERSION) {
        $tableName = $wpdb->prefix.'wc_sofinco_payment';
        $sql = "CREATE TABLE $tableName (
            id int not null auto_increment,
            order_id bigint not null,
            type enum('capture', 'first_payment', 'second_payment', 'third_payment') not null,
            data varchar(2048) not null,
            KEY order_id (order_id),
            PRIMARY KEY  (id))";

        require_once(ABSPATH.'wp-admin/includes/upgrade.php');

        dbDelta($sql);

        update_option(WC_SOFINCO_PLUGIN.'_version', WC_SOFINCO_VERSION);
    }
}
function woocommerce_sofinco_initialization()
{
    $class = 'WC_Sofinco_Abstract_Gateway';

    if (!class_exists($class)) {
        require_once(dirname(__FILE__).'/class/wc-sofinco-config.php');
        require_once(dirname(__FILE__).'/class/wc-sofinco-iso4217currency.php');
        require_once(dirname(__FILE__).'/class/wc-sofinco-IsoCountry.php');
        require_once(dirname(__FILE__).'/class/wc-sofinco.php');
        require_once(dirname(__FILE__).'/class/wc-sofinco-abstract-gateway.php');
        require_once(dirname(__FILE__).'/class/wc-sofinco-standard-gateway.php');
        require_once(dirname(__FILE__).'/class/wc-sofinco-encrypt.php');
    }

    load_plugin_textdomain(WC_SOFINCO_PLUGIN, false, dirname(plugin_basename(__FILE__)).'/lang/');

    $crypto = new SofincoEncrypt();
    if (!file_exists(WC_SOFINCO_KEY_PATH)) {
        $crypto->generateKey();
    }

    if (get_site_option(WC_SOFINCO_PLUGIN.'_version') != WC_SOFINCO_VERSION) {
        woocommerce_sofinco_installation();
    }

    // Init hooks & filters
    wc_sofinco_register_hooks();
}

function wc_sofinco_register_hooks()
{
    // Register hooks & filters for each instance
    foreach (wc_get_sofinco_classes() as $gatewayClass) {
        $gatewayClass::getInstance($gatewayClass)->initHooksAndFilters();
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_sofinco_register');
    add_action('woocommerce_admin_order_data_after_billing_address', 'woocommerce_sofinco_show_details');
}

function wc_get_sofinco_classes()
{
    return array(
        'WC_Sofinco_Standard_Gateway',
    );
}

function woocommerce_sofinco_register(array $methods)
{
    return array_merge($methods, wc_get_sofinco_classes());
}

function woocommerce_sofinco_show_details(WC_Order $order)
{
    $method = get_post_meta($order->get_id(), '_payment_method', true);
    switch ($method) {
        case 'sofinco_std':
            $method = new WC_Sofinco_Standard_Gateway();
            $method->showDetails($order);
            break;
    }
}

register_activation_hook(__FILE__, 'woocommerce_sofinco_installation');
add_action('plugins_loaded', 'woocommerce_sofinco_initialization');
