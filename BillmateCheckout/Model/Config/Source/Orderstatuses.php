<?php

namespace Billmate\BillmateCheckout\Model\Config\Source;

class Orderstatuses implements \Magento\Framework\Option\ArrayInterface {
	
	protected $statusCollectionFactory;

    public function __construct(\Magento\Framework\View\Element\Template\Context $context, \Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory $statusCollectionFactory) {       
		$this->statusCollectionFactory = $statusCollectionFactory;
    }
	
    public function toOptionArray(){
		$options = $this->statusCollectionFactory->create()->toOptionArray();        
		return $options;
    }
}