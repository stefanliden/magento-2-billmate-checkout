<?php

namespace Billmate\BillmateCheckout\Block;
 
class Success extends \Magento\Framework\View\Element\Template {
	
    protected $helper;

    protected $objectManager;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Billmate\BillmateCheckout\Helper\Data $_helper,
        \Magento\Framework\ObjectManagerInterface $_objectManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
		$this->helper = $_helper;
		$this->objectManager = $_objectManager;
	}
	
	public function getSuccess()
    {
		$str = $this->helper->getCheckout();
		$this->helper->clearSession();
		return $str;
	}
}