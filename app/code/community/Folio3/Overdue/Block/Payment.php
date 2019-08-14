<?php
class Folio3_Overdue_Block_Payment extends Mage_Checkout_Block_Onepage_Payment{
    public function __construct(){
        parent::__construct();
    }

    public function getPayPostUrl(){
        return $this->getUrl('*/*/payPost');
    }

    public function getBackUrl(){
        return $this->getUrl('*/*/overdue');
    }

    /**
     * Retrieve code of current payment method
     *
     * @return mixed
     */
    public function getSelectedMethodCode()
    {
        $invoiceId = Mage::app()->getRequest()->getParam('invoice_id');
        if($invoiceId){
            $invoice = Mage::getModel('sales/order_invoice')->load($invoiceId);
            if ($method = $invoice->getOrder()->getPayment()->getMethod()) {
                return $method;
            }
        }
        return false;
    }
}
?>