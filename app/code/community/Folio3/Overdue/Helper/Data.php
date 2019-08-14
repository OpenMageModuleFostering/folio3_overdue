<?php

class Folio3_Overdue_Helper_Data extends Mage_Core_Helper_Abstract{
    public function canPayDues(){
        return true;
    }

    public function getInvoice(){
        $invoice_id = Mage::app()->getRequest()->getParam('invoice_id');
        $invoice = Mage::getModel('sales/order_invoice')->load($invoice_id);

        return $invoice;
    }

    public function getOrder(){
        $invoice_id = Mage::app()->getRequest()->getParam('invoice_id');
        $invoice = Mage::getModel('sales/order_invoice')->load($invoice_id);

        return $invoice->getOrder();
    }

    public function getAllowedMethods(){
        $methods = Mage::getStoreConfig('Overdue/config/allowedMethods');
        return explode(',', $methods);
    }
}