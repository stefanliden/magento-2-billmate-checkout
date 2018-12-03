<?php
 
namespace Billmate\Billmate\Model;
 
/**
 * Pay In Store payment method model
 */

class Card extends \Billmate\Billmate\Model\BillmatePayments
{
    /**
     * @var string
     */
    protected $_code = 'billmate_card';
}