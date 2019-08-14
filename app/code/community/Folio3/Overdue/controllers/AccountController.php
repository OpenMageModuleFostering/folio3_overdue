<?php
require_once Mage::getModuleDir('controllers', 'Mage_Customer').DS.'AccountController.php';
class Folio3_Overdue_AccountController extends Mage_Customer_AccountController{
    /**
     * Default customer account page
     */
    public function indexAction()
    {
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('catalog/session');

        $this->getLayout()->getBlock('content')->append(
            $this->getLayout()->createBlock('customer/account_dashboard')
        );

        $this->getLayout()->getBlock('head')->setTitle($this->__('My Account'));
        $this->renderLayout();
    }

    public function overdueAction(){
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('catalog/session');

        $this->renderLayout();
    }

    public function payAction(){
        $invoice_id = Mage::app()->getRequest()->getParam('invoice_id');

        if(!$invoice_id){
            $this->_redirect('*/*/overdue');
            return;
        }

        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('catalog/session');

        $this->renderLayout();
    }

    public function payPostAction(){
        if (!$this->_validateFormKey()) {
            $this->_redirect('*/*/overdue');
            return;
        }

        $params = Mage::app()->getRequest()->getPost();
        if($this->_validatePayPost($params)){
            $invoice_id = Mage::app()->getRequest()->getParam('invoice_id');
            $invoice = Mage::getModel('sales/order_invoice')->load($invoice_id);
            $order = $invoice->getOrder();

            $amount = (float) ($order->getTotalDue());
            $payment = $order->getPayment()->resetExistingPaymentInfo();
            $PaymentInfo = $params['payment'];

            //--- Change Payment Method
            $payment->setMethod($PaymentInfo['method']);
            //$payment->getMethodInstance()->assignData($PaymentInfo);

            try {
                $AuthInfo = null;
                if($payment->getMethodInstance()->isGateway()){
                    $payment->setCcInfo($PaymentInfo);
                    $AuthInfo = $payment->getMethodInstance()->authorize($payment, $amount);

                    if($AuthInfo instanceof Mes_Gateway_Model_Paymentmodel){
                        $payment->setParentTransactionId($payment->getTransactionId());
                    }
                }
                else{
                    $AuthInfo = $payment->getMethodInstance()->newAuthorization($payment, $amount, $PaymentInfo);
                }

                if (is_object($AuthInfo) && $AuthInfo instanceof Mage_Payment_Model_Method_Abstract) {
                    if ($AuthInfo->isGateway()) {
                        //-- For Gateway Payment Methods (Credit Card)
                        $this->_savePaymentInfo($payment, $amount);
                        return;
                    } else {
                        //-- For NonGateway Payment Methods (Paypal Express)
                        $this->_redirectUrl($AuthInfo->getData('_redirect_url'));
                        return;
                    }
                }

                throw new Exception(__('Authorization returned Invalid Object!'));
            }
            catch (Exception $e) {
                Mage::getSingleton('customer/session')->addError($e->getMessage());
                $this->_redirectUrl(Mage::getUrl('*/*/overdue'));
                return;
            }
        }
        else{
            Mage::getSingleton('customer/session')->addError('Invalid Post Data!');

            if(isset($params['order_id']))
                $this->_redirect('*/*/pay', array('order_id' => $params['order_id']));
            else
                $this->_redirect('*/*/overdue');

            return;
        }

        //--- Your Payment Code Business Logic Here!
        
    }

    public function authAction() {
        $order_id = (int) $this->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($order_id);
        $payment = $order->getPayment();

        try {
            $amount = (float) ($order->getTotalDue());
            $token = Mage::app()->getRequest()->getParam('token');
            $payment->getMethodInstance()->setNewTransactionInfo($payment, $token);
            $payment->getMethodInstance()->authorize($payment, $amount);

            $this->_savePaymentInfo($payment, $amount);
        }
        catch (Exception $e) {
            Mage::getSingleton('customer/session')->addError($e->getMessage());
            $this->_redirectUrl(Mage::getUrl('*/*/overdue'));
            return;
        }
    }

    private function _savePaymentInfo(Mage_Sales_Model_Order_Payment $payment, $amount) {
        $order = $payment->getOrder();

        $message = '';
        $state = $order->getState();
        $status = $order->getStatus();

        $formatedPrice = $order->getBaseCurrency()->formatTxt($amount);

        if ($payment->getIsTransactionPending()) {
            $message = Mage::helper('paypal')->__('Authorizing amount of %s is pending approval on gateway.', $formatedPrice);

            $state = Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW;
            if ($payment->getIsFraudDetected()) {
                $status = Mage_Sales_Model_Order::STATUS_FRAUD;
            }
        }
        else {
            $message = Mage::helper('paypal')->__('Authorized amount of %s.', $formatedPrice);
        }

        $transaction = $payment->addTransaction(
            Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, null, false, $message
        )->setIsClosed(0)->save();

        $payment->importTransactionInfo($transaction);

        $unpaidInvoice = $order->getInvoiceCollection()->getFirstItem();
        $unpaidInvoice->setIsPaid(false);
        $unpaidInvoice->capture();
        
        Mage::getModel('core/resource_transaction')
            ->addObject($payment)
            ->addObject($unpaidInvoice)
            ->addObject($order)
            ->save();

        Mage::getSingleton('customer/session')->addSuccess("Thank you! Your payment has been paid successfully.");
        $this->_redirectUrl(Mage::getUrl('*/*/overdue'));
    }


    private function _validatePayPost($params){
        if(isset($params['order_id']) && $params['order_id'] != '') {
            if (isset($params['invoice_id']) && $params['invoice_id'] != '') {
                $order = Mage::getModel('sales/order')->load($params['order_id']);
                $customer_id = Mage::getSingleton('customer/session')->getId();

                if ($order->getCustomerId() == $customer_id) {
                    return true;
                }
            }
        }

        return false;
    }
}
?>