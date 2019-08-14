<?php
class Folio3_Overdue_Block_Payment_Methods extends Folio3_Overdue_Block_Invoice {
    
    /**
     * Prepare children blocks
     */
    protected function _prepareLayout()
    {
        /**
         * Create child blocks for payment methods forms
         */
        foreach ($this->getMethods() as $method) {
            $block = $this->helper('payment')->getMethodFormBlock($method);
            $block->setMethod($method);

            $this->setChild(
                'folio3.payment.method.'. $method->getCode(), $block
            );
        }

        return parent::_prepareLayout();
    }

    public function getMethods(){
        $methods = array();

        $Invoice = $this->getInvoice();
        foreach ($this->helper('payment')->getStoreMethods($Invoice->getStoreId(), $Invoice->getOrder()) as $method) {
            if ($this->_canUseMethod($method, $Invoice->getOrder())) {

                $this->_assignMethod($method, $Invoice->getOrder());
                $methods[] = $method;
            }
        }

        //$methods = Mage::getSingleton('payment/config')->getActiveMethods();
        return $methods;
    }
    /**
     * Payment method form html getter
     * @param Mage_Payment_Model_Method_Abstract $method
     */
    public function getPaymentMethodFormHtml(Mage_Payment_Model_Method_Abstract $method) {
        return $this->getChildHtml('folio3.payment.method.' . $method->getCode());
    }

    /**
     * Return method title for payment selection page
     *
     * @param Mage_Payment_Model_Method_Abstract $method
     */
    public function getMethodTitle(Mage_Payment_Model_Method_Abstract $method)
    {
        $form = $this->getChild('folio3.payment.method.' . $method->getCode());
        if ($form && $form->hasMethodTitle()) {
            return $form->getMethodTitle();
        }
        return $method->getTitle();
    }

    /**
     * Payment method additional label part getter
     * @param Mage_Payment_Model_Method_Abstract $method
     */
    public function getMethodLabelAfterHtml(Mage_Payment_Model_Method_Abstract $method)
    {
        if ($form = $this->getChild('folio3.payment.method.' . $method->getCode())) {
            return $form->getMethodLabelAfterHtml();
        }
    }

    /**
     * Declare template for payment method form block
     *
     * @param   string $method
     * @param   string $template
     * @return  Mage_Payment_Block_Form_Container
     */
    public function setMethodFormTemplate($method='', $template='')
    {
        if (!empty($method) && !empty($template)) {
            if ($block = $this->getChild('folio3.payment.method.'.$method)) {
                $block->setTemplate($template);
            }
        }
        return $this;
    }

    protected function _canUseMethod($method){
        if (($method instanceof Mage_Payment_Model_Method_Abstract) && $method->canUseCheckout() && $method->isAvailable($this->getOrder())) {
            if (!$method->canUseForCountry($this->getOrder()->getBillingAddress()->getCountry())) {
                return false;
            }

            if (!$method->canUseForCurrency($this->getOrder()->getStore()->getBaseCurrencyCode())) {
                return false;
            }

            /**
             * Checking for min/max order total for assigned payment method
             */
            $total = $this->getOrder()->getBaseGrandTotal();
            $minTotal = $method->getConfigData('min_order_total');
            $maxTotal = $method->getConfigData('max_order_total');

            if((!empty($minTotal) && ($total < $minTotal)) || (!empty($maxTotal) && ($total > $maxTotal))) {
                return false;
            }

            $AllowedMethods = Mage::helper('folio3_overdue')->getAllowedMethods();
            $canUse = false;

            foreach($AllowedMethods as $AllowedMethod){
                if($method->getCode() == $AllowedMethod){
                    $canUse = true;
                }
            }

            return $canUse;
        }

        return false;
    }

    /**
     * Check payment method model
     *
     * @param Mage_Payment_Model_Method_Abstract|null
     * @return bool
     */
    protected function __canUseMethod($method, $Order)
    {
        $AllowedMethods = (array) Mage::getConfig()->getNode('default/folio3_overdue/allowed_methods');
        $canUse = false;

        if($method && $method->canUseCheckout() && $method->isApplicableToQuote(
                $Order, Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_COUNTRY
                | Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_CURRENCY
                | Mage_Payment_Model_Method_Abstract::CHECK_ORDER_TOTAL_MIN_MAX
            )){
            
            foreach($AllowedMethods as $AllowedMethod=>$isEnabled){
                if($method->getCode() == $AllowedMethod && $isEnabled){
                    $canUse = true;
                }
            }
        }

        return $canUse;
    }

    /**
     * Check and prepare payment method model
     *
     * Redeclare this method in child classes for declaring method info instance
     *
     * @param Mage_Payment_Model_Method_Abstract $method
     * @return bool
     */
    protected function _assignMethod($method, $Order)
    {
        $method->setInfoInstance($Order->getPayment());
        return $this;
    }

    /**
     * Retrieve code of current payment method
     *
     * @return mixed
     */
    public function getSelectedMethodCode()
    {
        if ($method = $this->getInvoice()->getOrder()->getPayment()->getMethod()) {
            return $method;
        }
        return false;
    }
}

?>