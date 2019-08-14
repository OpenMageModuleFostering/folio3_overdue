<?php
class Folio3_Overdue_Block_Invoice extends Mage_Core_Block_Template{
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

        $invoiceId = Mage::app()->getRequest()->getParam('invoice_id');
        if($invoiceId){
            $invoices->addFieldToFilter('main_table.entity_id', $invoiceId); //Fetch Invoices which are pending!
        }

        //Zend_Debug::dump($invoices->getData()); exit;

        $this->setInvoices($invoices);
    }

    public function getInvoice(){
        return $this->getInvoices()->getFirstItem();
    }

    public function getOrder(){
        $invoice = $this->getInvoice();
        return $invoice->getOrder();
    }

    public function getPayPostUrl(){
        return $this->getUrl('*/*/payPost');
    }
}
?>