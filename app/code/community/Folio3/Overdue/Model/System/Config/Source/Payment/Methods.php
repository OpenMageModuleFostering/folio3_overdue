<?php
class Folio3_Overdue_Model_System_Config_Source_Payment_Methods
{

    public function getAllOptions()
    {
        if (!$this->_options) {
            $methods = $this->_getActivPaymentMethods();
            $this->_options = $methods;
        }
        return $this->_options;
    }

    private function _getActivPaymentMethods()
    {
        $payments = Mage::getSingleton('payment/config')->getActiveMethods();
        foreach ($payments as $paymentCode=>$paymentModel) {
            if($paymentModel->canAuthorize()) {
                $paymentTitle = Mage::getStoreConfig('payment/' . $paymentCode . '/title');
                $methods[$paymentCode] = array(
                    'label' => $paymentTitle,
                    'value' => $paymentCode,
                );
            }
        }

        if(isset($methods['free'])){
            unset($methods['free']);
        }

        return $methods;

    }

    public function toOptionArray(){
        return $this->getAllOptions();
    }
}

?>