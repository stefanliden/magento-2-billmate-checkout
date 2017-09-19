<?php

namespace Billmate\BillmateCheckout\Block;
 
class Success extends \Magento\Framework\View\Element\Template {
	
    protected $helper;

    protected $objectManager;

    public function __construct(\Magento\Framework\View\Element\Template\Context $context, array $data = [], \Billmate\BillmateCheckout\Helper\Data $_helper, \Magento\Framework\ObjectManagerInterface $_objectManager){
        parent::__construct($context, $data);
		$this->helper = $_helper;
		$this->objectManager = $_objectManager;
	}
	
	protected function _toHtml(){
		return parent::_toHtml();
	}
	
	public function getSuccess(){
		$str = $this->helper->getCheckout();
		$this->helper->clearSession();
		return $str;
	}
}