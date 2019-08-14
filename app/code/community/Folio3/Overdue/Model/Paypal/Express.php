<?php

class Folio3_Overdue_Model_Paypal_Express extends Mage_Paypal_Model_Express {
    /**
     * Collect Dues of an Order Payment
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param float $amount
     * @return Apex_Deposit_Paypal_Model_Express
     */
    public function newAuthorization(Varien_Object $payment, $amount) {
        $order = $payment->getOrder();

        $returnURL = Mage::getUrl('*/*/auth', array('order_id' => $order->getId()));
        $calcelURL = Mage::getUrl('*/*/cancel', array('order_id' => $order->getId()));

        $api = $this->_pro->getApi()->setAmount($amount)
            ->setCurrencyCode($order->getBaseCurrencyCode())
            ->setInvNum($order->getIncrementId())
            ->setReturnUrl($returnURL)
            ->setCancelUrl($calcelURL)
            ->setSolutionType("sole")
            ->setPaymentAction($this->_pro->getConfig()->paymentAction);

        if ($this->_pro->getConfig()->requireBillingAddress == Mage_Paypal_Model_Config::REQUIRE_BILLING_ADDRESS_ALL) {
            $api->setRequireBillingAddress(1);
        }

        // supress or export shipping address
        if ($order->getIsVirtual()) {
            if ($this->_pro->getConfig()->requireBillingAddress == Mage_Paypal_Model_Config::REQUIRE_BILLING_ADDRESS_VIRTUAL) {
                $api->setRequireBillingAddress(1);
            }
            $api->setSuppressShipping(true);
        }
        else {
            $address = $order->getShippingAddress();
            $isOverriden = 0;

            if (true === $address->validate()) {
                $isOverriden = 1;
                $api->setAddress($address);
            }

            $order->getPayment()->setAdditionalInformation(
                Mage_Paypal_Model_Express_Checkout::PAYMENT_INFO_TRANSPORT_SHIPPING_OVERRIDEN, $isOverriden
            );

            $order->getPayment()->save();
        }

        // Add line items
        $paypalCart = Mage::getModel('paypal/cart', array($order));
        $api->setPaypalCart($paypalCart)
            ->setIsLineItemsEnabled($this->_pro->getConfig()->lineItemsEnabled);

        // Call API and redirect with token
        $api->callSetExpressCheckout();
        $token = $api->getToken();

        $redirectUrl = $this->_pro->getConfig()->getExpressCheckoutStartUrl($token);
        $order->getPayment()->setAdditionalInformation(Mage_Paypal_Model_Express_Checkout::PAYMENT_INFO_TRANSPORT_TOKEN, $token);
        $order->getPayment()->save();

        $this->_redirectUrl = $redirectUrl;
        return $this;
    }

    public function setNewTransactionInfo($payment, $token){
        $this->_pro->getApi()->setToken($token)->callGetExpressCheckoutDetails();

        Mage::getSingleton('paypal/info')->importToPayment($this->_pro->getApi(), $payment);
        $payment->setAdditionalInformation(Mage_Paypal_Model_Express_Checkout::PAYMENT_INFO_TRANSPORT_PAYER_ID, $this->_pro->getApi()->getPayerId())
            ->setAdditionalInformation(Mage_Paypal_Model_Express_Checkout::PAYMENT_INFO_TRANSPORT_TOKEN, $token)
        ;

        $payment->save();
    }

}
