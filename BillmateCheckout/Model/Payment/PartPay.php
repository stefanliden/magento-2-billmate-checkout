<?php
 
namespace Billmate\BillmateCheckout\Model\Payment;
 
/**
 * Pay In Store payment method model
 */

class PartPay extends \Billmate\BillmateCheckout\Model\Payment\BillmatePayments
{
    /**
     * @var string
     */
    protected $_code = 'billmate_partpay';
}