<?php

class WC_Sofinco_Standard_Gateway extends WC_Sofinco_Abstract_Gateway
{
    /**
     * @var string
     */
    protected $defaultTitle = 'Sofinco 3XCB payment';

    /**
     * @var string
     */
    protected $defaultDesc = 'xxxx';

    /**
     * @var string
     */
    protected $type = 'standard';

    public function __construct()
    {
        // Some properties
        $this->id = 'sofinco_std';
        $this->method_title = __('Sofinco 3XCB', WC_SOFINCO_PLUGIN);
        $this->originalTitle = $this->title = __('Sofinco 3XCB payment', WC_SOFINCO_PLUGIN);
        $this->defaultDesc = __('Choose your mean of payment directly on secured payment page of Sofinco', WC_SOFINCO_PLUGIN);
        $this->has_fields = false;
        parent::__construct();
    }

    private function _showDetailRow($label, $value)
    {
        return '<strong>'.$label.'</strong> '.__($value, WC_SOFINCO_PLUGIN);
    }

    public function showDetails($order)
    {
        $orderId = $order->get_id();
        $payment = $this->_sofinco->getOrderPayments($orderId, 'capture');

        if (!empty($payment)) {
            $data = unserialize($payment->data);
            $rows = array();
            $rows[] = $this->_showDetailRow(__('Reference:', WC_SOFINCO_PLUGIN), $data['reference']);
            if (isset($data['ip']) && !empty($data['ip'])) {
                $rows[] = $this->_showDetailRow(__('Country of IP:', WC_SOFINCO_PLUGIN), $data['ip']);
            }
            $rows[] = $this->_showDetailRow(__('Processing date:', WC_SOFINCO_PLUGIN), (isset($data['date']) ? preg_replace('/^([0-9]{2})([0-9]{2})([0-9]{4})$/', '$1/$2/$3', $data['date']) : 'N/A') . " - " . (isset($data['time']) ? $data['time'] : 'N/A'));
            if (!empty($data['firstNumbers']) && !empty($data['lastNumbers'])) {
                $rows[] = $this->_showDetailRow(__('Card numbers:', WC_SOFINCO_PLUGIN), $data['firstNumbers'].'...'.$data['lastNumbers']);
            }
            if (!empty($data['validity'])) {
                $rows[] = $this->_showDetailRow(__('Validity date:', WC_SOFINCO_PLUGIN), preg_replace('/^([0-9]{2})([0-9]{2})$/', '$2/$1', $data['validity']));
            }
            $rows[] = $this->_showDetailRow(__('Transaction:', WC_SOFINCO_PLUGIN), $data['transaction']);
            $rows[] = $this->_showDetailRow(__('Call:', WC_SOFINCO_PLUGIN), $data['call']);
            if (isset($data['authorization'])) {
                $rows[] = $this->_showDetailRow(__('Authorization:', WC_SOFINCO_PLUGIN), $data['authorization']);
            }

            echo '<h4>'.__('Payment information', WC_SOFINCO_PLUGIN).'</h4>';
            echo '<p>'.implode('<br/>', $rows).'</p>';
        }
    }
}
