<?php

namespace Billmate\BillmateCheckout\Block;
 
class Checkout extends \Billmate\BillmateCheckout\Block\Cart {

    /**
     * @return string
     */
	public function getBillmateCheckout()
    {
		return $this->helper->getIframe();
	}
}