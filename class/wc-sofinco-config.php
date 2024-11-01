<?php

class WC_Sofinco_Config
{
    /**
     * @var array
     */
    private $_values;

    /**
     * @var array
     */
    private $_defaults = array(
        'icon' => 'logo.png',
        'amount' => '90',
        'amountmax' => '2000',
        'debug' => 'no',
        'enabled' => 'yes',
        'fees' => 'yes',
        'delay' => 0,
        'environment' => 'TEST',
        'hmackey' => '0123456789ABCDEF0123456789ABCDEF0123456789ABCDEF0123456789ABCDEF0123456789ABCDEF0123456789ABCDEF0123456789ABCDEF0123456789ABCDEF',
        'identifier' => 3262411,
        'ips' => '194.2.122.158,195.25.7.166,195.101.99.76',
        'rank' => 73,
        'site' => 8888872,
    );

    /**
     * @var SofincoEncrypt
     */
    private $encryption;

    public function __construct(array $values, $defaultTitle, $defaultDesc)
    {
        $this->_values = $values;
        $this->_defaults['title'] = $defaultTitle;
        $this->_defaults['description'] = $defaultDesc;
        $this->encryption = new SofincoEncrypt();
    }

    protected function _getOption($name)
    {
        if (isset($this->_values[$name])) {
            return $this->_values[$name];
        }

        return $this->getDefaultOption($name);
    }

    /**
     * Retrieve the default value for a specific configuration key
     *
     * @param string $name
     * @return mixed
     */
    protected function getDefaultOption($name)
    {
        if (isset($this->_defaults[$name])) {
            return $this->_defaults[$name];
        }
        return null;
    }

    /**
     * Retrieve all settings by using defined or default value
     *
     * @return array
     */
    public function getFields()
    {
        $settings = array();
        foreach (array_keys($this->_defaults) as $configKey) {
            $settings[$configKey] = $this->_getOption($configKey);
        }

        return $settings;
    }

    public function getAmount()
    {
        $value = $this->_getOption('amount');
        return empty($value) ? null : floatval($value);
    }

    public function getAmountMax()
    {
        $value = $this->_getOption('amountmax');
        return empty($value) ? null : floatval($value);
    }

    public function getAllowedIps()
    {
        return explode(',', $this->_getOption('ips'));
    }

    public function getDefaults()
    {
        return $this->_defaults;
    }

    public function getDelay()
    {
        return (int)$this->_getOption('delay');
    }

    public function getDescription()
    {
        return $this->_getOption('description');
    }

    public function getHmacAlgo()
    {
        return 'SHA512';
    }

    public function getHmacKey()
    {
        if (isset($this->_values['hmackey']) && $this->_values['hmackey'] != $this->_defaults['hmackey']) {
            return $this->encryption->decrypt($this->_values['hmackey']);
        }

        return $this->_defaults['hmackey'];
    }

    public function getIdentifier()
    {
        return $this->_getOption('identifier');
    }

    public function getRank()
    {
        return $this->_getOption('rank');
    }

    public function getSite()
    {
        return $this->_getOption('site');
    }

    public function isFeeFree()
    {
        return $this->_getOption('fees');
    }

    public function getSystemProductionUrls()
    {
        return array(
            'https://tpeweb.paybox.com/php/',
            'https://tpeweb1.paybox.com/php/',
        );
    }

    public function getSystemTestUrls()
    {
        return array(
            'https://preprod-tpeweb.paybox.com/php/'
            // 'https://itg3-tpeweb.paybox.fr/php/',
        );
    }

    public function getSystemUrls()
    {
        if ($this->isProduction()) {
            return $this->getSystemProductionUrls();
        }
        return $this->getSystemTestUrls();
    }

    public function getTitle()
    {
        return $this->_getOption('title');
    }

    public function getIcon()
    {
        return $this->_getOption('icon');
    }

    public function isDebug()
    {
        return $this->_getOption('debug') === 'yes';
    }

    public function isProduction()
    {
        return $this->_getOption('environment') === 'PRODUCTION';
    }
}
