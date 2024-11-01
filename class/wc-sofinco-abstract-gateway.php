<?php
/**
 * Sofinco - Payment Gateway class.
 *
 * Extended by individual payment gateways to handle payments.
 *
 * @class   WC_Sofinco_Abstract_Gateway
 * @extends WC_Payment_Gateway
 */
abstract class WC_Sofinco_Abstract_Gateway extends WC_Payment_Gateway
{
    /**
     * @var WC_Sofinco_Config
     */
    protected $_config;
    protected $defaultConfig;

    /**
     * @var WC_Sofinco
     */
    protected $_sofinco;

    /**
     * @var WC_Logger
     */
    private $logger;

    /**
     * @var SofincoEncrypt
     */
    protected $encryption;

    /**
     * @var WC_Sofinco_Abstract_Gateway
     */
    private static $pluginInstance = array();

    /**
     * @var string
     */
    protected $commonDescription = '';

    /**
     * @var string
     */
    protected $originalTitle = '';

    /**
     * Returns payment gateway instance
     *
     * @return WC_Sofinco_Abstract_Gateway
     */
    public static function getInstance($class)
    {
        if (empty(self::$pluginInstance[$class])) {
            self::$pluginInstance[$class] = new static();
        }

        return self::$pluginInstance[$class];
    }

    public function __construct()
    {
        global $wp;

        // Logger for debug if needed
        $this->logger = wc_get_logger();

        $this->method_description = '<center><img src="' . plugins_url('images/logo.png', plugin_basename(dirname(__FILE__))) . '"/></center>';

        // Load the settings
        $this->defaultConfig = new WC_Sofinco_Config(array(), $this->defaultTitle, $this->defaultDesc);
        $this->encryption = new SofincoEncrypt();
        $this->init_settings();
        $this->_config = new WC_Sofinco_Config($this->settings, $this->defaultTitle, $this->defaultDesc);
        $this->_sofinco = new WC_Sofinco($this->_config);

        $this->title = apply_filters('title', $this->_config->getTitle());
        $this->description = apply_filters('description', $this->_config->getDescription());
        $this->icon = apply_filters(WC_SOFINCO_PLUGIN, plugin_dir_url(__DIR__) . 'images/') . apply_filters('icon', $this->_config->getIcon());

        // Change title & description depending on the context
        if (!is_admin() && $this->getCurrentEnvMode() == 'test') {
            $this->title = apply_filters('title', $this->_config->getTitle() . ' (' . __('TEST MODE', WC_SOFINCO_PLUGIN) . ')');
            $this->description = apply_filters('description', '<strong>' . __('Test mode enabled - No debit will be made', WC_SOFINCO_PLUGIN) . '</strong><br /><br />' . $this->_config->getDescription());
            $this->commonDescription = apply_filters('description', '<strong>' . __('Test mode enabled - No debit will be made', WC_SOFINCO_PLUGIN) . '</strong><br /><br />');
        }

        if (is_admin()) {
            $this->title = apply_filters('title', $this->originalTitle);
        }

        // Prevent cart to be cleared when the customer is getting back after an order cancel
        $orderId = isset($wp->query_vars) && is_array($wp->query_vars) && isset($wp->query_vars['order-received']) ? absint($wp->query_vars['order-received']) : 0;
        if (!empty($orderId) && isset($_GET['key']) && !empty($_GET['key'])) {
            // Retrieve order key and order object
            $orderKey = wp_unslash($_GET['key']);
            $order = wc_get_order($orderId);

            // Compare order id, hash and payment method
            if ($orderId === $order->get_id()
                && hash_equals($order->get_order_key(), $orderKey) && $order->needs_payment()
                && $order->get_payment_method() == $this->id
            ) {
                // Prevent wc_clear_cart_after_payment to run in this specific case
                remove_action('get_header', 'wc_clear_cart_after_payment');
                // WooCommerce 6.4.0
                remove_action('template_redirect', 'wc_clear_cart_after_payment', 20);
            }
        }
    }

    /**
     * Register some hooks
     *
     * @return void
     */
    public function initHooksAndFilters()
    {
        // Actions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
        add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'api_call'));
        add_action('admin_notices', array($this, 'display_custom_admin_notices'));
        add_action('admin_enqueue_scripts', array($this, 'load_custom_admin_assets'));
    }

    /**
     * Retrieve form fields for the gateway plugin
     *
     * @return array
     */
    public function get_form_fields()
    {
        $fields = parent::get_form_fields();

        $fields += $this->getGlobalConfigurationFields();
        $fields += $this->getAccountConfigurationFields();

        return $fields;
    }

    /**
     * Init payment gateway settings + separately handle environment
     *
     * @return void
     */
    public function init_settings()
    {
        parent::init_settings();

        // Set default env if not exists (upgrade / new install cases for example)
        if (empty($this->settings['environment'])) {
            $defaults = $this->defaultConfig->getDefaults();
            $this->settings['environment'] = $defaults['environment'];
        }

        // Set custom setting for environment (global to any env)
        if (get_option($this->plugin_id . $this->id . '_env') === false && !empty($this->settings['environment'])) {
            update_option($this->plugin_id . $this->id . '_env', $this->settings['environment']);
            unset($this->settings['environment']);
            update_option($this->get_option_key(), $this->settings);
        }

        // Module upgrade case, copy same settings on test env
        if (get_option($this->plugin_id . $this->id . '_settings') !== false && get_option($this->plugin_id . $this->id . '_test_settings') === false) {
            // Apply the same configuration on test vs production
            $testConfiguration = get_option($this->plugin_id . $this->id . '_settings');
            $testConfiguration['environment'] = 'TEST';
            update_option($this->plugin_id . $this->id . '_test_settings', $testConfiguration);
        }

        // Define the current environment
        $this->settings['environment'] = get_option($this->plugin_id . $this->id . '_env');

        $this->_config = new WC_Sofinco_Config($this->settings, $this->defaultTitle, $this->defaultDesc);
        $this->settings = $this->_config->getFields();
    }

    /**
     * Handle custom config key for test / production settings
     *
     * @return string
     */
    public function get_option_key()
    {
        // Inherit settings from the previous version
        if ($this->getCurrentConfigMode() != 'production') {
            return $this->plugin_id . $this->id . '_' .  $this->getCurrentConfigMode() . '_settings';
        }

        return parent::get_option_key();
    }

    /**
     * save_hmackey
     * Used to save the settings field of the custom type HSK
     * @param  array $field
     * @return void
     */
    public function process_admin_options()
    {
        // Handle encrypted fields
        foreach (array('hmackey') as $field) {
            $_POST[$this->plugin_id . $this->id . '_' . $field] = $this->encryption->encrypt($_POST[$this->plugin_id . $this->id . '_' . $field]);
        }

        // Handle environment config data separately
        if (isset($_POST[$this->plugin_id . $this->id . '_environment'])
        && in_array($_POST[$this->plugin_id . $this->id . '_environment'], array('TEST', 'PRODUCTION'))) {
            update_option($this->plugin_id . $this->id . '_env', $_POST[$this->plugin_id . $this->id . '_environment']);
            unset($_POST[$this->plugin_id . $this->id . '_environment']);
        }

        parent::process_admin_options();
    }


    /**
     * Check the current context so allow/disallow a specific display action
     *
     * @return bool
     */
    protected function allowDisplay()
    {
        if (!function_exists('get_current_screen')) {
            return false;
        }

        $screen = get_current_screen();
        // Prevent display on others pages than setting, and if the current id isn't the one we are trying to configure
        if (
            !is_object($screen)
            || empty($screen->id)
            || $screen->id != 'woocommerce_page_wc-settings'
            || empty($_GET['section'])
            || $this->id != $_GET['section']
        ) {
            return false;
        }

        return true;
    }

    /**
     * Load the needed assets for the plugin configuration
     *
     * @return void
     */
    public function load_custom_admin_assets()
    {
        if (!$this->allowDisplay()) {
            return;
        }

        // Register JS & CSS files
        wp_register_style('admin.css', WC_SOFINCO_PLUGIN_URL . 'assets/css/admin.css', array(), WC_SOFINCO_VERSION);
        wp_enqueue_style('admin.css');
        wp_register_script('admin.js', WC_SOFINCO_PLUGIN_URL . 'assets/js/admin.js', array(), WC_SOFINCO_VERSION);
        wp_enqueue_script('admin.js');
    }

    /**
     * Used to display some specific notices regarding the current gateway env
     *
     * @return void
     */
    public function display_custom_admin_notices()
    {
        static $displayed = false;

        // HMAC or WooCommerce alerts
        if (isWoocommerceActiveSofinco()) {
            if ($this->allowDisplay() && !$this->checkCrypto()) {
                echo "
                <div class='notice notice-error is-dismissible'>
                    <p>
                        <strong>" . sprintf(__("Warning for %s plugin (%s) :", WC_SOFINCO_PLUGIN), $this->get_title(), $this->getCurrentConfigMode()) . "</strong>
                        " . __('HMAC key cannot be decrypted please re-enter or reinitialise it.', WC_SOFINCO_PLUGIN) . "
                    </p>
                </div>";
            }
        } else {
            echo "
            <div class='notice notice-error is-dismissible'>
                <p>
                    <strong>" . sprintf(__("Warning for %s plugin (%s) :", WC_SOFINCO_PLUGIN), $this->get_title(), $this->getCurrentConfigMode()) . "</strong>
                    " . __('Woocommerce is not active !', WC_SOFINCO_PLUGIN) . "
                </p>
            </div>";
        }

        if (!$this->allowDisplay() || $displayed) {
            return;
        }

        // Display alert banner if the extension is into TEST mode
        if (get_option($this->plugin_id . $this->id . '_env') == 'TEST') {
            $displayed = true; ?>
            <div id="pbx-alert-mode" class="pbx-alert-box notice notice-warning notice-alt">
                <div class="dashicons dashicons-warning"></div>
                <div class="pbx-alert-box-content">
                    <strong class="pbx-alert-title"><?= __('Test mode enabled', WC_SOFINCO_PLUGIN); ?></strong>
                    <?= __('No debit will be made', WC_SOFINCO_PLUGIN); ?>
                </div>
                <div class="dashicons dashicons-warning"></div>
            </div>
            <?php
        }
    }

    /**
     * Retrieve current environment mode (production / test)
     *
     * @return string
     */
    protected function getCurrentEnvMode()
    {
        // Use current defined mode into the global configuration
        if (!empty(get_option($this->plugin_id . $this->id . '_env')) && in_array(get_option($this->plugin_id . $this->id . '_env'), array('TEST', 'PRODUCTION'))) {
            return strtolower(get_option($this->plugin_id . $this->id . '_env'));
        }

        // Use the default mode from WC_Sofinco_Config
        $defaults = $this->defaultConfig->getDefaults();

        return strtolower($defaults['environment']);
    }

    /**
     * Retrieve current configuration mode (production / test)
     *
     * @return string
     */
    protected function getCurrentConfigMode()
    {
        // Check previous configuration mode before computing the option key (upgrade case)
        $settings = get_option($this->plugin_id . $this->id . '_settings');
        if (get_option($this->plugin_id . $this->id . '_env') === false && !empty($settings['environment'])) {
            update_option($this->plugin_id . $this->id . '_env', $settings['environment']);
            unset($settings['environment']);
            update_option($this->plugin_id . $this->id . '_settings', $settings);
        }

        // Use current defined mode into the URL (only if request is from admin)
        if (is_admin() && !empty($_GET['config_mode']) && in_array($_GET['config_mode'], array('test', 'production'))) {
            return $_GET['config_mode'];
        }

        // Use current defined mode into the global configuration
        if (!empty(get_option($this->plugin_id . $this->id . '_env')) && in_array(get_option($this->plugin_id . $this->id . '_env'), array('TEST', 'PRODUCTION'))) {
            return strtolower(get_option($this->plugin_id . $this->id . '_env'));
        }

        // Use the default mode from WC_Sofinco_Config
        $defaults = $this->defaultConfig->getDefaults();

        return $defaults['environment'];
    }

    public function admin_options()
    {
        $this->settings['hmackey'] = $this->_config->getHmacKey();

        ?>
        <script>
            var pbxUrl = <?= json_encode(admin_url('admin.php?page=wc-settings&tab=checkout&section=' . $this->id)) ?>;
            var pbxConfigModeMessage = <?= json_encode(__('Do you really want to change the current shop environment mode?', WC_SOFINCO_PLUGIN)) ?>;
            var pbxGatewayId = <?= json_encode($this->id) ?>;
        </script>

        <div id="pbx-plugin-configuration">
            <div class="pbx-flex-container">
                <div>
                    <div id="pbx-plugin-image"></div>
                </div>
                <div id="pbx-current-mode-selector" class="pbx-current-mode-<?= $this->getCurrentEnvMode(); ?>">
                    <table class="form-table">
                        <?= $this->generate_settings_html($this->get_payment_mode_fields()); ?>
                    </table>
                </div>
            </div>
            <div class="clear"></div>

            <div class="pbx-current-config-mode pbx-current-config-mode-<?= $this->getCurrentConfigMode() ?>">
                <span class="dashicons dashicons-<?= ($this->getCurrentConfigMode() == 'test' ? 'warning' : 'yes-alt') ?>"></span>
                <?= sprintf(__('You are currently editing the <strong><u>%s</u></strong> configuration', WC_SOFINCO_PLUGIN), $this->getCurrentConfigMode()); ?>
                <span class="dashicons dashicons-<?= ($this->getCurrentConfigMode() == 'test' ? 'warning' : 'yes-alt') ?>"></span>
                <br /><br />
                <a href="<?= admin_url('admin.php?page=wc-settings&tab=checkout&section=' . $this->id) ?>&config_mode=<?= ($this->getCurrentConfigMode() == 'production' ? 'test' : 'production') ?>">
                    <?= sprintf(__('=> Click here to switch to the <strong>%s</strong> configuration', WC_SOFINCO_PLUGIN), ($this->getCurrentConfigMode() == 'production' ? 'test' : 'production')); ?>
                </a>
            </div>

            <h2 id="pbx-tabs" class="nav-tab-wrapper">
                <a href="#pbx-pbx-account-configuration" class="nav-tab nav-tab-active">
                    <?= __('My account', WC_SOFINCO_PLUGIN); ?>
                </a>
                <a href="#pbx-global-configuration" class="nav-tab">
                    <?= __('Global configuration', WC_SOFINCO_PLUGIN); ?>
                </a>
            </h2>
            <div id="pbx-pbx-account-configuration" class="tab-content tab-active">
                <table class="form-table">
                <?= $this->generate_account_configuration_html(); ?>
                </table>
            </div>
            <div id="pbx-global-configuration" class="tab-content">
                <table class="form-table">
                <?= $this->generate_global_configuration_html(); ?>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Generate configuration form for the global configuration
     *
     * @return void
     */
    protected function generate_global_configuration_html()
    {
        $this->generate_settings_html($this->getGlobalConfigurationFields());
    }

    /**
     * Generate configuration form for the account configuration
     *
     * @return void
     */
    protected function generate_account_configuration_html()
    {
        $this->generate_settings_html($this->getAccountConfigurationFields());
    }

    /**
     * Retrieve specific fields, dedicated to environment
     *
     * @return array
     */
    protected function get_payment_mode_fields()
    {
        $defaults = $this->defaultConfig->getDefaults();

        return array(
            'environment' => array(
                'title' => __('Current shop environment mode', WC_SOFINCO_PLUGIN),
                'type' => 'select',
                // 'description' => __('In test mode your payments will not be sent to the bank.', WC_SOFINCO_PLUGIN),
                'options' => array(
                    'PRODUCTION' => __('Production', WC_SOFINCO_PLUGIN),
                    'TEST' => __('Test (no debit)', WC_SOFINCO_PLUGIN),
                ),
                'default' => $defaults['environment'],
            ),
        );
    }

    /**
     * Retrieve the fields for the global configuration
     *
     * @return array
     */
    protected function getGlobalConfigurationFields()
    {
        if (!isset($this->_config)) {
            $this->_config = $this->defaultConfig;
        }
        $defaults = $this->defaultConfig->getDefaults();

        $formFields = array();
        $formFields['enabled'] = array(
            'title' => __('Enable/Disable', WC_SOFINCO_PLUGIN),
            'type' => 'checkbox',
            'label' => __('Enable Sofinco Payment', WC_SOFINCO_PLUGIN),
            'default' => 'yes'
        );
        $formFields['fees'] = array(
            'title' => __('Fee management', WC_SOFINCO_PLUGIN),
            'type' => 'select',
            'options' => array(
                'no' => __('Share the fees with the customer', WC_SOFINCO_PLUGIN),
                'yes' => __('I pay the fees (no cost for the customer)', WC_SOFINCO_PLUGIN),
            ),
            'default' => $defaults['fees'],
        );
        $formFields['title'] = array(
            'title' => __('Title', WC_SOFINCO_PLUGIN),
            'type' => 'text',
            'description' => __('This controls the title which the user sees during checkout.', WC_SOFINCO_PLUGIN),
            'default' => __($defaults['title'], WC_SOFINCO_PLUGIN),
        );
        $formFields['description'] = array(
            'title' => __('Description', WC_SOFINCO_PLUGIN),
            'type' => 'textarea',
            'description' => __('Payment method description that the customer will see on your checkout.', WC_SOFINCO_PLUGIN),
            'default' => __($defaults['description'], WC_SOFINCO_PLUGIN),
        );
        $formFields['amount'] = array(
            'title' => __('Min & Max amount', WC_SOFINCO_PLUGIN),
            'type' => 'minmax',
            'description' => __('Enable this payment method for order between theses min & max amount below (leave empty to ignore this condition)', WC_SOFINCO_PLUGIN),
            'default' => null,
            'min' => array(
                'key' => 'amount',
                'default' => $defaults['amount'],
                'custom_attributes' => array(
                    'min' => '0',
                    'max' => '2000',
                    'size' => '6',
                ),
            ),
            'max' => array(
                'key' => 'amountmax',
                'default' => $defaults['amountmax'],
                'custom_attributes' => array(
                    'min' => '0',
                    'max' => '2000',
                    'size' => '6',
                ),
            ),
        );
        // Needed to process the "amountmax" form value
        $formFields['amountmax'] = array(
            'hidden' => true,
            'type' => 'minmax',
            'default' => null,
        );

        return $formFields;
    }

    /**
     * Generate two input for min/max values
     *
     * @param string $key Field key.
     * @param array  $data Field data.
     * @see WC_Settings_API::generate_text_html
     * @return string
     */
    protected function generate_minmax_html($key, $data)
    {
        if (!empty($data['hidden'])) {
            return;
        }

        $defaults  = array(
            'title'             => '',
            'disabled'          => false,
            'class'             => '',
            'css'               => '',
            'placeholder'       => '',
            'type'              => 'text',
            'desc_tip'          => false,
            'description'       => '',
            'custom_attributes' => array(),
        );

        $data['type'] = 'number';

        $data = wp_parse_args($data, $defaults);
        $dataMin = wp_parse_args($data['min'], $defaults);
        $dataMax = wp_parse_args($data['max'], $defaults);

        ob_start();
        ?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo $this->get_field_key(esc_attr($dataMin['key'])); ?>"><?php echo wp_kses_post($data['title']); ?> <?php echo $this->get_tooltip_html($data); // WPCS: XSS ok.?></label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<input class="input-text regular-input <?php echo esc_attr( $data['class'] ); ?>" type="<?php echo esc_attr( $data['type'] ); ?>" name="<?php echo $this->get_field_key(esc_attr( $dataMin['key'] )); ?>" id="<?php echo $this->get_field_key(esc_attr( $dataMin['key'] )); ?>" style="<?php echo esc_attr( $dataMin['css'] ); ?>" value="<?php echo esc_attr( $this->get_option( $dataMin['key'] ) ); ?>" placeholder="<?php echo esc_attr( $dataMin['placeholder'] ); ?>" <?php disabled( $dataMin['disabled'], true ); ?> <?php echo $this->get_custom_attribute_html( $dataMin ); // WPCS: XSS ok. ?> />
					<input class="input-text regular-input <?php echo esc_attr( $data['class'] ); ?>" type="<?php echo esc_attr( $data['type'] ); ?>" name="<?php echo $this->get_field_key(esc_attr( $dataMax['key'] )); ?>" id="<?php echo $this->get_field_key(esc_attr( $dataMax['key'] )); ?>" style="<?php echo esc_attr( $dataMax['css'] ); ?>" value="<?php echo esc_attr( $this->get_option( $dataMax['key'] ) ); ?>" placeholder="<?php echo esc_attr( $dataMax['placeholder'] ); ?>" <?php disabled( $dataMax['disabled'], true ); ?> <?php echo $this->get_custom_attribute_html( $dataMax ); // WPCS: XSS ok. ?> />
					<?php echo $this->get_description_html( $data ); // WPCS: XSS ok. ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
    }

    /**
     * Retrieve the fields for the account configuration
     *
     * @return array
     */
    protected function getAccountConfigurationFields()
    {
        if (!isset($this->_config)) {
            $this->_config = $defaults;
        }
        $defaults = $this->defaultConfig->getDefaults();

        $formFields = array();
        $formFields['site'] = array(
            'title' => __('Site number', WC_SOFINCO_PLUGIN),
            'type' => 'text',
            'description' => __('Site number provided by Sofinco.', WC_SOFINCO_PLUGIN),
            'default' => $defaults['site'],
            'custom_attributes' => array(
                'pattern' => '[0-9]{1,7}',
            ),
        );
        $formFields['rank'] = array(
            'title' => __('Rank number', WC_SOFINCO_PLUGIN),
            'type' => 'text',
            'description' => __('Rank number provided by Sofinco (two last digits).', WC_SOFINCO_PLUGIN),
            'default' => $defaults['rank'],
            'custom_attributes' => array(
                'pattern' => '[0-9]{1,3}',
            ),
        );
        $formFields['identifier'] = array(
            'title' => __('Login', WC_SOFINCO_PLUGIN),
            'type' => 'text',
            'description' => __('Internal login provided by Sofinco.', WC_SOFINCO_PLUGIN),
            'default' => $defaults['identifier'],
            'custom_attributes' => array(
                'pattern' => '[0-9]+',
            ),
        );
        $formFields['hmackey'] = array(
            'title' => __('HMAC', WC_SOFINCO_PLUGIN),
            'type' => 'text',
            'description' => __('Secrete HMAC key to create using the Sofinco interface.', WC_SOFINCO_PLUGIN),
            'default' => $defaults['hmackey'],
            'custom_attributes' => array(
                'pattern' => '[0-9a-fA-F]{128}',
            ),
        );
        $formFields['technical'] = array(
            'title' => __('Technical settings', WC_SOFINCO_PLUGIN),
            'type' => 'title',
            'default' => null,
        );
        $formFields['ips'] = array(
            'title' => __('Sofinco IPs', WC_SOFINCO_PLUGIN),
            'type' => 'text',
            'description' => __('A coma separated list of Sofinco IPs IPN.', WC_SOFINCO_PLUGIN),
            'default' => $defaults['ips'],
            'custom_attributes' => array(
                'readonly' => 'readonly',
            ),
        );
        $formFields['debug'] = array(
            'title' => __('Debug', WC_SOFINCO_PLUGIN),
            'type' => 'checkbox',
            'label' => __('Enable some debugging information', WC_SOFINCO_PLUGIN),
            'default' => $defaults['debug'],
        );

        return $formFields;
    }

    /**
     * Check If The Gateway Is Available For Use
     *
     * @access public
     * @return bool
     */
    public function is_available()
    {
        if (!parent::is_available()) {
            return false;
        }
        $minimal = $this->_config->getAmount();
        $maximal = $this->_config->getAmountMax();
        if (empty($minimal) && empty($maximal)) {
            return true;
        }

        // Retrieve total from cart, or order
        $total = null;
        if (is_checkout_pay_page() && get_query_var('order-pay')) {
            $order = wc_get_order((int)get_query_var('order-pay'));
            if (!empty($order)) {
                $total = $order->get_total();
            }
        } elseif (WC()->cart) {
            $total = WC()->cart->total;
        }

        if ($total === null) {
            // Unable to retrieve order/cart total
            return false;
        }

        return ($total >= $minimal && $total <= $maximal);
    }

    /**
     * Process the payment, redirecting user to Sofinco.
     *
     * @param int $order_id The order ID
     * @return array
     */
    public function process_payment($orderId)
    {
        $order = wc_get_order($orderId);

        $message = __('Customer is redirected to Sofinco payment page', WC_SOFINCO_PLUGIN);
        $this->_sofinco->addOrderNote($order, $message);

        return array(
            'result' => 'success',
            'redirect' => add_query_arg('order-pay', $order->get_id(), add_query_arg('key', $order->get_order_key(), $order->get_checkout_order_received_url())),
        );
    }

    public function receipt_page($orderId)
    {
        $order = wc_get_order($orderId);
        $urls = $this->getReturnUrls('', $order);

        $params = $this->_sofinco->buildSystemParams($order, $this->type, $urls);

        try {
            $url = $this->_sofinco->getSystemUrl();
        } catch (Exception $e) {
            echo "<p>" . $e->getMessage() . "</p>";
            echo "<form><center><button onClick='history.go(-1);return true;'>" . __('Back...', WC_SOFINCO_PLUGIN) . "</center></button></form>";
            exit;
        }

        // Output the payment form
        $this->outputPaymentForm($order, $url, $params);
    }

    /**
     * Retrieve all return URL
     *
     * @param string $suffix
     * @param WC_Order $order
     * @return array
     */
    protected function getReturnUrls($suffix = '', $order = null)
    {
        $pbxAnnule = null;
        if (!empty($order)) {
            $pbxAnnule = $order->get_checkout_payment_url();
        }

        if (!is_multisite()) {
            return array(
                'PBX_ANNULE' => (!empty($pbxAnnule) ? $pbxAnnule : add_query_arg('status', 'cancel' . $suffix, add_query_arg('wc-api', get_class($this), get_permalink()))),
                'PBX_EFFECTUE' => add_query_arg('status', 'success' . $suffix, add_query_arg('wc-api', get_class($this), get_permalink())),
                'PBX_REFUSE' => add_query_arg('status', 'failed' . $suffix, add_query_arg('wc-api', get_class($this), get_permalink())),
                'PBX_REPONDRE_A' => add_query_arg('status', 'ipn' . $suffix, add_query_arg('wc-api', get_class($this), get_permalink())),
            );
        }

        return array(
            'PBX_ANNULE' => (!empty($pbxAnnule) ? $pbxAnnule : add_query_arg(array(
                'wc-api' => get_class($this),
                'status' => 'cancel' . $suffix,
            ), trailingslashit(site_url()))),
            'PBX_EFFECTUE' => add_query_arg(array(
                'wc-api' => get_class($this),
                'status' => 'success' . $suffix,
            ), trailingslashit(site_url())),
            'PBX_REFUSE' => add_query_arg(array(
                'wc-api' => get_class($this),
                'status' => 'failed' . $suffix,
            ), trailingslashit(site_url())),
            'PBX_REPONDRE_A' => add_query_arg(array(
                'wc-api' => get_class($this),
                'status' => 'ipn' . $suffix,
            ), trailingslashit(site_url())),
        );
    }

    /**
     * Output the payment form
     *
     * @param WC_Order $order
     * @param string $url
     * @param array $params
     * @return void
     */
    protected function outputPaymentForm($order, $url, $params)
    {
        $debugMode = $this->_config->isDebug();
        ?>
        <form id="pbxep_form" method="post" action="<?php echo esc_url($url); ?>" enctype="application/x-www-form-urlencoded">
            <?php if ($debugMode) : ?>
                <p>
                    <?php echo __('This is a debug view. Click continue to be redirected to Sofinco payment page.', WC_SOFINCO_PLUGIN); ?>
                </p>
            <?php else : ?>
                <p>
                    <?php echo __('You will be redirected to the Sofinco payment page. If not, please use the button bellow.', WC_SOFINCO_PLUGIN); ?>
                </p>
                <script type="text/javascript">
                    window.setTimeout(function () {
                        document.getElementById('pbxep_form').submit();
                    }, 1);
                </script>
            <?php endif; ?>
            <center><button><?php echo __('Continue...', WC_SOFINCO_PLUGIN); ?></button></center>
            <?php
            $type = $debugMode ? 'text' : 'hidden';
            foreach ($params as $name => $value) {
                $name = esc_attr($name);
                $value = esc_attr($value);
                if ($debugMode) {
                    echo '<p><label for="' . $name . '">' . $name . '</label>';
                }
                echo '<input type="' . $type . '" id="' . $name . '" name="' . $name . '" value="' . $value . '" />';
                if ($debugMode) {
                    echo '</p>';
                }
            }
            ?>
        </form>
        <?php
    }

    public function api_call()
    {
        if (!isset($_GET['status'])) {
            header('Status: 404 Not found', true, 404);
            die();
        }

        switch ($_GET['status']) {
            case 'cancel':
                return $this->on_payment_canceled();
                break;

            case 'failed':
                return $this->on_payment_failed();
                break;

            case 'ipn':
                return $this->on_ipn();
                break;

            case 'success':
                return $this->on_payment_succeed();
                break;

            default:
                header('Status: 404 Not found', true, 404);
                die();
        }
    }

    public function on_payment_failed()
    {
        $order = null;
        try {
            $params = $this->_sofinco->getParams();

            if ($params !== false) {
                $order = $this->_sofinco->untokenizeOrder($params['reference']);
                $message = __('Customer is back from Sofinco payment page.', WC_SOFINCO_PLUGIN);
                $message .= ' ' . __('Payment refused by Sofinco', WC_SOFINCO_PLUGIN);
                $this->_sofinco->addCartErrorMessage($message);
            }
        } catch (Exception $e) {
            // Ignore
        }

        $this->redirectToCheckout($order);
    }

    public function on_payment_canceled()
    {
        $order = null;
        try {
            $params = $this->_sofinco->getParams();

            if ($params !== false) {
                $order = $this->_sofinco->untokenizeOrder($params['reference']);
                $message = __('Payment canceled', WC_SOFINCO_PLUGIN);
                $this->_sofinco->addCartErrorMessage($message);
            }
        } catch (Exception $e) {
            // Ignore
        }

        $this->redirectToCheckout($order);
    }

    public function on_payment_succeed()
    {
        $order = null;
        try {
            $params = $this->_sofinco->getParams();
            if ($params === false) {
                return;
            }

            // Retrieve order
            $order = $this->_sofinco->untokenizeOrder($params['reference']);

            // Check required parameters
            $this->checkRequiredParameters($order, $params);

            $message = __('Customer is back from Sofinco payment page.', WC_SOFINCO_PLUGIN);
            $this->_sofinco->addOrderNote($order, $message);
            WC()->cart->empty_cart();

            // Payment success
            $this->addPaymentInfosAndChangeOrderStatus($order, $params, 'customer');

            wp_redirect($order->get_checkout_order_received_url());
            die();
        } catch (Exception $e) {
            // Ignore
        }

        $this->redirectToCheckout($order);
    }

    /**
     * Check required parameters on IPN / Customer back on shop
     *
     * @param WC_Order $order
     * @param array $params
     * @return void
     */
    protected function checkRequiredParameters(WC_Order $order, $params)
    {
        $requiredParams = array('amount', 'transaction', 'error', 'reference', 'sign');
        foreach ($requiredParams as $requiredParam) {
            if (!isset($params[$requiredParam])) {
                $message = sprintf(__('Missing %s parameter in Sofinco call', WC_SOFINCO_PLUGIN), $requiredParam);
                $this->_sofinco->addOrderNote($order, $message);
                throw new Exception($message);
            }
        }
    }

    /**
     * Save payment infos, add note on order and change its status
     *
     * @param WC_Order $order
     * @param array $params
     * @param string $context (ipn or customer)
     * @return void
     */
    protected function addPaymentInfosAndChangeOrderStatus(WC_Order $order, $params, $context = null)
    {
        // Payment success
        if ($params['error'] == '00000') {
            $this->writeLog("Payment success");
            switch ($this->type) {
                case 'standard':
                    $this->_sofinco->addOrderNote($order, __('Payment was authorized and captured by Sofinco.', WC_SOFINCO_PLUGIN));
                    $order->payment_complete($params['transaction']);
                    $this->_sofinco->addOrderPayment($order, 'capture', $params);
                    break;
                default:
                    $message = sprintf(__('Unexpected type %s', WC_SOFINCO_PLUGIN), $type);
                    $this->writeLog($message);
                    $this->_sofinco->addOrderNote($order, $message);
                    throw new Exception($message);
            }
        } else {
            // Payment refused
            $error = $this->_sofinco->toErrorMessage($params['error']);
            $message = sprintf(__('Payment was refused by Sofinco (%s).', WC_SOFINCO_PLUGIN), $error);
            $this->writeLog($message);
            $this->_sofinco->addOrderNote($order, $message);
        }
    }

    public function on_ipn()
    {
        try {
            $params = $this->_sofinco->getParams();

            if ($params === false) {
                return;
            }

            $order = $this->_sofinco->untokenizeOrder($params['reference']);

            // Check required parameters
            $this->checkRequiredParameters($order, $params);

            // Payment success
            $this->addPaymentInfosAndChangeOrderStatus($order, $params, 'ipn');
        } catch (Exception $e) {
            $this->writeLog(print_r($e->getMessage(), true));
            throw $e;
        }
    }

    public function redirectToCheckout($order)
    {
        if ($order !== null) {
            // Try to pay again, redirect to checkout page
            wp_redirect($order->get_checkout_payment_url());
        } else {
            // Unable to retrieve the order, redirect to shopping cart
            wp_redirect(WC()->cart->get_cart_url());
        }
        die();
    }

    public function writeLog($string, $level = "debug")
    {
        // Prevent writing to log if debug is not enabled
        if (!$this->_config->isDebug()) {
            return;
        }

        $context = array('source' => 'sofinco');
        switch ($level) {
            case "debug":
                $this->logger->debug($string, $context);
                break;
            case "info":
                $this->logger->info($string, $context);
                break;
            case "error":
                $this->logger->error($string, $context);
                break;
        }
    }

    public function checkCrypto()
    {
        return $this->encryption->decrypt($this->settings['hmackey']);
    }

    abstract public function showDetails($orderId);
}
