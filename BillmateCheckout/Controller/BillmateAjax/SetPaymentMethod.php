<?php

namespace Billmate\BillmateCheckout\Controller\BillmateAjax;

use Magento\Framework\App\Action\Context;

class SetPaymentMethod extends \Magento\Framework\App\Action\Action {
	
    protected $formKey;
	protected $helper;
	protected $checkoutSession;
	
	public function __construct(
		Context $context, 
		\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Data\Form\FormKey $formKey,
		\Magento\Checkout\Model\Session $_checkoutSession,
		\Billmate\BillmateCheckout\Helper\Data $_helper
	) {
        $this->formKey = $formKey;
		$this->helper = $_helper;
		$this->resultJsonFactory = $resultJsonFactory;
		$this->checkoutSession = $_checkoutSession;
		parent::__construct($context);
	}

	public function execute() {
		$this->helper->setBmPaymentMethod($_POST['method']);
	}
}
