<?php
class Folio3_Overdue_Model_Sales_Order_Payment extends Mage_Sales_Model_Order_Payment{
    function resetExistingPaymentInfo(){
        /*
         cc_exp_month = NULL,
         cc_exp_month = NULL,
         cc_last4 = NULL,
         last_trans_id = NULL,
         cc_owner = NULL,
         cc_type = NULL,
         po_number = NULL,
         cc_exp_year = NULL,
         cc_number_enc = NULL,
         additional_information = NULL
        */

        $this->setCcExpMonth(null);
        $this->setCcExpYear(null);
        $this->setCcLast4(null);
        $this->setLastTransId(null);
        $this->setCcOwner(null);
        $this->setCcType(null);
        $this->setPoNumber(null);
        $this->setCcNumberEnc(null);
        $this->setData('additional_information', NULL);

        return $this;
    }

    public function setCcInfo($ccInfo){
        $this->setCcType($ccInfo['cc_type']);
        $this->setCcExpMonth($ccInfo['cc_exp_month']);
        $this->setCcExpYear($ccInfo['cc_exp_year']);
        $this->setCcNumber($ccInfo['cc_number']);
        $this->setCcCid($ccInfo['cc_cid']);

        return $this;
    }
}

?>