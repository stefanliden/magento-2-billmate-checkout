<?php
 
namespace Billmate\Billmate\Model;
 
/**
 * Pay In Store payment method model
 */

class PaymentMethod extends \Billmate\Billmate\Model\BillmatePayments
{
    /**
     * @var string
     */
    protected $_code = 'billmate_invoice';
}