<?php
class Folio3_Overdue_Block_Grid extends Mage_Core_Block_Template{
    public function __construct() {
        parent::__construct();

        $invoices = Mage::getModel('sales/order_invoice')->getCollection()
            ->join(
                array('order'=> 'order'),
                'order.entity_id = order_id',
                array(
                    'order.customer_id',
                    'order.total_paid',
                    'order.total_due'
                )
            )
            ->addFieldToFilter('order.customer_id', Mage::getSingleton('customer/session')->getCustomer()->getId())
            ->addFieldToFilter('main_table.state', 1) //Fetch Invoices which are pending!
            ->setOrder('main_table.created_at', 'desc');

        //Zend_Debug::dump($invoices->getData()); exit;

        $this->setInvoices($invoices);
    }

    public function getViewUrl(Mage_Sales_Model_Order_Invoice $invoice) {
        return $this->getUrl('sales/order/view', array('order_id' => $invoice->getOrderId()));
    }

    public function getPayUrl(Mage_Sales_Model_Order_Invoice $invoice) {
       return $this->getUrl('*/*/pay', array('invoice_id' => $invoice->getId()));
    }
}
?>