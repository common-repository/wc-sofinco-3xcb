<?php

class WC_Sofinco
{
    /**
     * @var WC_Sofinco_Config
     */
    private $_config;

    /**
     * @var array
     */
    private $_currencyDecimals = array(
        '008' => 2,
        '012' => 2,
        '032' => 2,
        '036' => 2,
        '044' => 2,
        '048' => 3,
        '050' => 2,
        '051' => 2,
        '052' => 2,
        '060' => 2,
        '064' => 2,
        '068' => 2,
        '072' => 2,
        '084' => 2,
        '090' => 2,
        '096' => 2,
        '104' => 2,
        '108' => 0,
        '116' => 2,
        '124' => 2,
        '132' => 2,
        '136' => 2,
        '144' => 2,
        '152' => 0,
        '156' => 2,
        '170' => 2,
        '174' => 0,
        '188' => 2,
        '191' => 2,
        '192' => 2,
        '203' => 2,
        '208' => 2,
        '214' => 2,
        '222' => 2,
        '230' => 2,
        '232' => 2,
        '238' => 2,
        '242' => 2,
        '262' => 0,
        '270' => 2,
        '292' => 2,
        '320' => 2,
        '324' => 0,
        '328' => 2,
        '332' => 2,
        '340' => 2,
        '344' => 2,
        '348' => 2,
        '352' => 0,
        '356' => 2,
        '360' => 2,
        '364' => 2,
        '368' => 3,
        '376' => 2,
        '388' => 2,
        '392' => 0,
        '398' => 2,
        '400' => 3,
        '404' => 2,
        '408' => 2,
        '410' => 0,
        '414' => 3,
        '417' => 2,
        '418' => 2,
        '422' => 2,
        '426' => 2,
        '428' => 2,
        '430' => 2,
        '434' => 3,
        '440' => 2,
        '446' => 2,
        '454' => 2,
        '458' => 2,
        '462' => 2,
        '478' => 2,
        '480' => 2,
        '484' => 2,
        '496' => 2,
        '498' => 2,
        '504' => 2,
        '504' => 2,
        '512' => 3,
        '516' => 2,
        '524' => 2,
        '532' => 2,
        '532' => 2,
        '533' => 2,
        '548' => 0,
        '554' => 2,
        '558' => 2,
        '566' => 2,
        '578' => 2,
        '586' => 2,
        '590' => 2,
        '598' => 2,
        '600' => 0,
        '604' => 2,
        '608' => 2,
        '634' => 2,
        '643' => 2,
        '646' => 0,
        '654' => 2,
        '678' => 2,
        '682' => 2,
        '690' => 2,
        '694' => 2,
        '702' => 2,
        '704' => 0,
        '706' => 2,
        '710' => 2,
        '728' => 2,
        '748' => 2,
        '752' => 2,
        '756' => 2,
        '760' => 2,
        '764' => 2,
        '776' => 2,
        '780' => 2,
        '784' => 2,
        '788' => 3,
        '800' => 2,
        '807' => 2,
        '818' => 2,
        '826' => 2,
        '834' => 2,
        '840' => 2,
        '858' => 2,
        '860' => 2,
        '882' => 2,
        '886' => 2,
        '901' => 2,
        '931' => 2,
        '932' => 2,
        '934' => 2,
        '936' => 2,
        '937' => 2,
        '938' => 2,
        '940' => 0,
        '941' => 2,
        '943' => 2,
        '944' => 2,
        '946' => 2,
        '947' => 2,
        '948' => 2,
        '949' => 2,
        '950' => 0,
        '951' => 2,
        '952' => 0,
        '953' => 0,
        '967' => 2,
        '968' => 2,
        '969' => 2,
        '970' => 2,
        '971' => 2,
        '972' => 2,
        '973' => 2,
        '974' => 0,
        '975' => 2,
        '976' => 2,
        '977' => 2,
        '978' => 2,
        '979' => 2,
        '980' => 2,
        '981' => 2,
        '984' => 2,
        '985' => 2,
        '986' => 2,
        '990' => 0,
        '997' => 2,
        '998' => 2,
    );

    /**
     * @var array
     */
    private $_errorCode = array(
        '00000' => 'Successful operation',
        '00001' => 'Payment system not available',
        '00003' => 'Paybor error',
        '00004' => 'Card number or invalid cryptogram',
        '00006' => 'Access denied or invalid identification',
        '00008' => 'Invalid validity date',
        '00009' => 'Subscription creation failed',
        '00010' => 'Unknown currency',
        '00011' => 'Invalid amount',
        '00015' => 'Payment already done',
        '00016' => 'Existing subscriber',
        '00021' => 'Unauthorized card',
        '00029' => 'Invalid card',
        '00030' => 'Timeout',
        '00033' => 'Unauthorized IP country',
    );

    /**
     * @var array
     */
    private $_resultMapping = array(
        'M' => 'amount',
        'R' => 'reference',
        'T' => 'call',
        'A' => 'authorization',
        'B' => 'subscription',
        'C' => 'cardType',
        'D' => 'validity',
        'E' => 'error',
        'F' => '3ds',
        'H' => 'imprint',
        'I' => 'ip',
        'J' => 'lastNumbers',
        'K' => 'sign',
        'N' => 'firstNumbers',
        'o' => 'celetemType',
        'P' => 'paymentType',
        'Q' => 'time',
        'S' => 'transaction',
        'U' => 'subscriptionData',
        'W' => 'date',
        'Y' => 'country',
        'Z' => 'paymentIndex',
    );

    public function __construct(WC_Sofinco_Config $config)
    {
        $this->_config = $config;
    }

    public function addCartErrorMessage($message)
    {
        wc_add_notice($message, 'error');
    }

    public function addOrderNote(WC_Order $order, $message)
    {
        $order->add_order_note($message);
    }

    public function addOrderPayment(WC_Order $order, $type, array $data)
    {
        global $wpdb;
        $wpdb->insert($wpdb->prefix.'wc_sofinco_payment', array(
            'order_id' => $order->get_id(),
            'type' => $type,
            'data' => serialize($data),
        ));
    }

    /**
     * @params WC_Order $order Order
     * @params string $type Type of payment (standard)
     * @params array $additionalParams Additional parameters
     */
    public function buildSystemParams(WC_Order $order, $type, array $additionalParams = array())
    {
        global $wpdb;
        global $woocommerce;
        // Parameters
        $values = array();

        // Merchant information
        $values['PBX_SITE'] = $this->_config->getSite();
        $values['PBX_RANG'] = $this->_config->getRank();
        $values['PBX_IDENTIFIANT'] = $this->_config->getIdentifier();

        // Order information
        $values['PBX_PORTEUR'] = $order->get_billing_email();
        $values['PBX_DEVISE'] = $this->getCurrency();
        $values['PBX_CMD'] = $order->get_id().' - '.$this->getBillingName($order).' - '.time();

        // //Customer information
        $values['PBX_CUSTOMER'] = trim(substr($this->getCustomerInformation($order), 21));
        // //Billing information
        $values['PBX_BILLING'] = trim(substr($this->getBillingInformation($order), 21));
        $values['PBX_SHOPPINGCART'] = $this->getXmlShoppingCartInformation($order);

        // Amount
        $orderAmount = floatval($order->get_total());
        $amountScale = $this->_currencyDecimals[$values['PBX_DEVISE']];
        $amountScale = pow(10, $amountScale);
        switch ($type) {
            case 'standard':
                $delay = $this->_config->getDelay();
                if ($delay > 0) {
                    if ($delay > 7) {
                        $delay = 7;
                    }
                    $values['PBX_DIFF'] = sprintf('%02d', $delay);
                }
                $values['PBX_TOTAL'] = sprintf('%03d', round($orderAmount * $amountScale));
                break;

            default:
                $message  = __('Unexpected type %s', WC_SOFINCO_PLUGIN);
                $message = sprintf($message, $type);
                throw new Exception($message);
        }

        // Sofinco => Magento
        $values['PBX_RETOUR'] = 'M:M;R:R;T:T;A:A;B:B;C:C;D:D;E:E;F:F;G:G;I:I;J:J;N:N;O:O;P:P;Q:Q;S:S;W:W;Y:Y;K:K';
        $values['PBX_RUF1'] = 'POST';

        // Choose correct language
        $lang = get_locale();
        if (!empty($lang)) {
            $lang = preg_replace('#_.*$#', '', $lang);
        }
        $languages = $this->getLanguages();
        if (!array_key_exists($lang, $languages)) {
            $lang = 'default';
        }
        $values['PBX_LANGUE'] = $languages[$lang];

        //Sofinco
        $values['PBX_TYPEPAIEMENT'] = 'LIMONETIK';
        $values['PBX_TYPECARTE'] = 'SOF3X';
        if ($this->_config->isFeeFree()!="no") {
            $values['PBX_TYPECARTE'].="SF";
        }

        // Misc.
        $values['PBX_TIME'] = date('c');
        $values['PBX_HASH'] = strtoupper($this->_config->getHmacAlgo());
        $values['PBX_VERSION'] = "wp_".get_bloginfo('version');
        $values['PBX_VERSION'] .= "_woocommerce_".$woocommerce->version;
        $values['PBX_VERSION'] .= "_module_".WC_SOFINCO_VERSION;

        // Adding additionnal informations
        $values = array_merge($values, $additionalParams);

        // Sort parameters for simpler debug
        ksort($values);

        // Sign values
        $sign = $this->signValues($values);

        // Hash HMAC
        $values['PBX_HMAC'] = $sign;

        return $values;
    }

    /**generating xml for customer and billing**/
    public function getCustomerInformation(WC_Order $order)
    {
        $simpleXMLElement = new SimpleXMLElement("<Customer/>");
        $simpleXMLElement->addChild('Id', $order->get_customer_id());
        return  $simpleXMLElement->asXML();
    }

    /**
     * Format a value to respect specific rules
     *
     * @param string $value
     * @param string $type
     * @param int $maxLength
     * @return string
     */
    protected function formatTextValue($value, $type, $maxLength = null)
    {
        /*
        AN : Alphanumerical without special characters
        ANP : Alphanumerical with spaces and special characters
        ANS : Alphanumerical with special characters
        N : Numerical only
        A : Alphabetic only
        */

        switch ($type) {
            default:
            case 'AN':
                $value = remove_accents($value);
                $value = htmlspecialchars($value);
                // Remove entities
                $value = preg_replace('/&#?[a-z0-9]{2,8};/i', '', $value);
                break;
            case 'ANP':
                $value = remove_accents($value);
                $value = preg_replace('/[^-. a-zA-Z0-9]/', '', $value);
                break;
            case 'ANS':
                $value = remove_accents($value);
                $value = preg_replace('/[^a-zA-Z0-9\s]/', '', $value);
                break;
            case 'N':
                $value = preg_replace('/[^0-9.]/', '', $value);
                break;
            case 'A':
                $value = remove_accents($value);
                $value = preg_replace('/[^A-Za-z]/', '', $value);
                break;
        }
        // Remove carriage return characters
        $value = trim(preg_replace("/\r|\n/", '', $value));
        // Remove multiple space
        $value = preg_replace('/\\s+/', ' ', $value);

        // Cut the string when needed
        if (!empty($maxLength) && is_numeric($maxLength) && $maxLength > 0) {
            if (function_exists('mb_strlen')) {
                if (mb_strlen($value) > $maxLength) {
                    $value = mb_substr($value, 0, $maxLength);
                }
            } elseif (strlen($value) > $maxLength) {
                $value = substr($value, 0, $maxLength);
            }
        }

        return trim($value);
    }

    /**
     * Generate XML value for PBX_SHOPPINGCART parameter
     *
     * @param WC_Order $order
     * @return string
     */
    public function getXmlShoppingCartInformation(WC_Order $order = null)
    {
        $totalQuantity = 0;
        if (!empty($order)) {
            foreach ($order->get_items() as $item) {
                $totalQuantity += (int)$item->get_quantity();
            }
        } else {
            $totalQuantity = 1;
        }
        // totalQuantity must be less or equal than 99
        // totalQuantity must be greater or equal than 1
        $totalQuantity = max(1, min($totalQuantity, 99));

        return sprintf('<?xml version="1.0" encoding="utf-8"?><shoppingcart><total><totalQuantity>%d</totalQuantity></total></shoppingcart>', $totalQuantity);
    }

    public function getBillingInformation(WC_Order $order)
    {
        $firstName = $this->formatTextValue($order->get_billing_first_name(), 'ANS', 20);
        $lastName = $this->formatTextValue($order->get_billing_last_name(), 'ANS', 20);
        $address1 = $this->formatTextValue($order->get_billing_address_1(), 'ANS', 50);
        $address2 = $this->formatTextValue($order->get_billing_address_2(), 'ANS', 50);
        $zipCode = $this->formatTextValue($order->get_billing_postcode(), 'ANS', 16);
        $city = $this->formatTextValue($order->get_billing_city(), 'ANS', 50);
        $country = $order->get_billing_country();
        if (empty($country)) {
            // Force FR country if empty
            $country = 'FR';
        }
        $IsoCountry = new IsoCountry($country);
        $countryCode = $IsoCountry->IsoCode;
        $countryText = $IsoCountry->Name;
        $countryName = $this->formatTextValue(substr($countryText, 0, 1).strtolower(substr($countryText, 1)), 'ANS', 50);
        $countryCodeHomePhone =  "+".$IsoCountry->PhoneCode;
        $homePhone = $IsoCountry->normalizeNumber($order->get_billing_phone());
        $countryCodeMobilePhone = $countryCodeHomePhone;
        $mobilePhone = $IsoCountry->normalizeNumber($order->get_billing_phone());

        $title = "Mr";

        $simpleXMLElement = new SimpleXMLElement("<Billing/>");
        $addressXML = $simpleXMLElement->addChild('Address');
        $addressXML->addChild('Title', $title);
        $addressXML->addChild('FirstName', $firstName);
        $addressXML->addChild('LastName', $lastName);
        $addressXML->addChild('Address1', $address1);
        if (!empty($address2)) {
            $addressXML->addChild('Address2', $address2);
        }
        $addressXML->addChild('ZipCode', $zipCode);
        $addressXML->addChild('City', $city);
        $addressXML->addChild('CountryCode', $countryCode);
        $addressXML->addChild('CountryName', $countryName);
        $addressXML->addChild('CountryCodeHomePhone', $countryCodeHomePhone);
        $addressXML->addChild('HomePhone', $homePhone);
        $addressXML->addChild('CountryCodeMobilePhone', $countryCodeMobilePhone);
        $addressXML->addChild('MobilePhone', $mobilePhone);

        $xml = $simpleXMLElement->asXML();
        $xml = preg_replace("/&#?[a-z0-9]{2,8};/i", '', $xml);

        return $xml;
    }

    public function convertParams(array $params)
    {
        $result = array();
        foreach ($this->_resultMapping as $param => $key) {
            if (isset($params[$param])) {
                $result[$key] = utf8_encode($params[$param]);
            }
        }

        return $result;
    }

    public function getBillingName(WC_Order $order)
    {
        $name = $order->get_billing_first_name().' '.$order->get_billing_last_name();
        $name = remove_accents($name);
        $name = str_replace(' - ', '-', $name);
        $name = trim(preg_replace('/[^-. a-zA-Z0-9]/', '', $name));
        return $name;
    }

    public function getCurrency()
    {
        return WC_Sofinco_Iso4217Currency::getIsoCode(get_woocommerce_currency());
    }

    public function getLanguages()
    {
        return array(
            'fr' => 'FRA',
            'es' => 'ESP',
            'it' => 'ITA',
            'de' => 'DEU',
            'nl' => 'NLD',
            'sv' => 'SWE',
            'pt' => 'PRT',
            'default' => 'GBR',
        );
    }

    public function getOrderPayments($orderId, $type)
    {
        global $wpdb;
        $sql = 'select * from '.$wpdb->prefix.'wc_sofinco_payment where order_id = %d and type = %s';
        $sql = $wpdb->prepare($sql, $orderId, $type);
        return $wpdb->get_row($sql);
    }

    public function getParams()
    {
        // Retrieves data
        $data = file_get_contents('php://input');
        if (empty($data)) {
            $data = $_SERVER['QUERY_STRING'];
        }
        if (empty($data)) {
            $message = 'An unexpected error in Sofinco call has occured: no parameters.';
            throw new Exception(__($message, WC_SOFINCO_PLUGIN));
        }

        // Extract signature
        $matches = array();
        if (!preg_match('#^(.*)&K=(.*)$#', $data, $matches)) {
            $message = 'An unexpected error in Sofinco call has occured: missing signature.';
            throw new Exception(__($message, WC_SOFINCO_PLUGIN));
        }

        // Check signature
        $signature = base64_decode(urldecode($matches[2]));
        $pubkey = file_get_contents(dirname(__FILE__).'/pubkey.pem');
        $res = (boolean) openssl_verify($matches[1], $signature, $pubkey);

        if (!$res) {
            if (preg_match('#status=ipn&(.*)&K=(.*)$#', $data, $matches)) {
                $signature = base64_decode(urldecode($matches[2]));
                $res = (boolean) openssl_verify($matches[1], $signature, $pubkey);
            }

            if (!$res) {
                $message = 'An unexpected error in Sofinco call has occured: invalid signature.';
                throw new Exception(__($message, WC_SOFINCO_PLUGIN));
            }
        }

        $rawParams = array();
        parse_str($data, $rawParams);

        // Decrypt params
        $params = $this->convertParams($rawParams);
        if (empty($params)) {
            $message = 'An unexpected error in Sofinco call has occured: no parameters.';
            throw new Exception(__($message, WC_SOFINCO_PLUGIN));
        }

        return $params;
    }

    public function getSystemUrl()
    {
        $urls = $this->_config->getSystemUrls();
        if (empty($urls)) {
            $message = 'Missing URL for Sofinco system in configuration';
            throw new Exception(__($message, WC_SOFINCO_PLUGIN));
        }

        // look for valid peer
        $error = null;
        foreach ($urls as $url) {
            // $testUrl = preg_replace('#^([a-zA-Z0-9]+://[^/]+)(/.*)?$#', '\1/load.html', $url);

            // $connectParams = array(
            // 'timeout' => 5,
            // 'redirection' => 0,
            // 'user-agent' => 'Woocommerce Sofinco module',
            // 'httpversion' => '2',
            // );
            // try {
            // $response = wp_remote_get($testUrl, $connectParams);
            // if (is_array($response) && ($response['response']['code'] == 200)) {
            // if (preg_match('#<div id="server_status" style="text-align:center;">OK</div>#', $response['body']) == 1) {
            // return $url;
            // }
            // }
            // }
            // catch (Exception $e) {
            // $error = $e;
            // }
            return $url;
        }

        // Here, there's a problem
        throw new Exception(__('Sofinco not available. Please try again later.', WC_SOFINCO_PLUGIN));
    }

    public function signValues(array $values)
    {
        // Serialize values
        $params = array();
        foreach ($values as $name => $value) {
            $params[] = $name.'='.$value;
        }
        $query = implode('&', $params);

        // Prepare key
        $key = pack('H*', $this->_config->getHmacKey());

        // Sign values
        $sign = hash_hmac($this->_config->getHmacAlgo(), $query, $key);
        if ($sign === false) {
            $errorMsg = 'Unable to create hmac signature. Maybe a wrong configuration.';
            throw new Exception(__($errorMsg, WC_SOFINCO_PLUGIN));
        }

        return strtoupper($sign);
    }

    public function toErrorMessage($code)
    {
        if (isset($this->_errorCode[$code])) {
            return $this->_errorCode[$code];
        }

        return 'Unknown error '.$code;
    }

    /**
     * Load order from the $token
     * @param string $token Token (@see tokenizeOrder)
     * @return WC_Order
     */
    public function untokenizeOrder($token)
    {
        $parts = explode(' - ', $token, 3);
        if (count($parts) < 2) {
            $message = 'Invalid decrypted token "%s"';
            throw new Exception(sprintf(__($message, WC_SOFINCO_PLUGIN), $token));
        }

        // Retrieves order
        $order = wc_get_order((int)$parts[0]);
        if (empty($order)) {
            $message = 'Not existing order id from decrypted token "%s"';
            throw new Exception(sprintf(__($message, WC_SOFINCO_PLUGIN), $token));
        }

        $name = $this->getBillingName($order);
        if (($name != utf8_decode($parts[1])) && ($name != $parts[1])) {
            $message = 'Consistency error on descrypted token "%s"';
            throw new Exception(sprintf(__($message, WC_SOFINCO_PLUGIN), $token));
        }

        return $order;
    }
}
