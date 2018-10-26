<?php

namespace Billmate\BillmateCheckout\Model\Config\Source;

class Attributes implements \Magento\Framework\Option\ArrayInterface {
	/**
	* Get status options
	*
	* @return array
	*/	
    public function toOptionArray(){
        return [
            'super' => 'Show Super Attributes',
            'all' => 'Show All Visible Attributes',
            'none' => 'Show no Attributes',
        ];
    }
}