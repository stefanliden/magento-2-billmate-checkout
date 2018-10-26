<?php
namespace Billmate\BillmateCheckout\Plugin;

class Url {

    /**
     * @var \Billmate\BillmateCheckout\Helper\Data
     */
    protected $helper;

    public function __construct(\Billmate\BillmateCheckout\Helper\Data $helper){
        $this->helper = $helper;
    }

    public function afterGetCheckoutUrl($subject,$result){
        if ($this->helper->getEnable()) {
            return $subject->getUrl('billmatecheckout');
        }
        return $result;
    }
}