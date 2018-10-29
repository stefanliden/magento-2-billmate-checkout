<?php

namespace Billmate\BillmateCheckout\Block;
 
class Checkout extends \Magento\Checkout\Block\Onepage {
	
    protected $helper;

    protected $objectManager;

    public function __construct(
		\Magento\Framework\View\Element\Template\Context $context, 
		array $data = [], 
		\Billmate\BillmateCheckout\Helper\Data $_helper,
		\Magento\Framework\ObjectManagerInterface $_objectManager,
		\Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Checkout\Helper\Data $checkoutHelper,
		\Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Checkout\Model\CompositeConfigProvider $configProvider,
        array $layoutProcessors = []
	) {
        parent::__construct($context, $formKey, $configProvider, $layoutProcessors, $data);
		$this->helper = $_helper;
		$this->objectManager = $_objectManager;
	}

    /**
     * @return string
     */
	public function getBillmateCheckout()
    {
		return $this->helper->getIframe();
	}
}