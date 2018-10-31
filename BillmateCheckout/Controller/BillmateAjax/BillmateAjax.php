<?php

namespace Billmate\BillmateCheckout\Controller\BillmateAjax;

use Magento\Framework\App\Action\Context;

class BillmateAjax extends \Magento\Framework\App\Action\Action {
    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    protected $formKey;

    /**
     * @var \Billmate\BillmateCheckout\Helper\Data
     */
	protected $helper;

    /**
     * @var \Billmate\BillmateCheckout\Helper\Iframe
     */
	protected $iframeHelper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
	protected $checkoutSession;
	
	public function __construct(
		Context $context, 
		\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Data\Form\FormKey $formKey,
		\Magento\Checkout\Model\Session $_checkoutSession,
		\Billmate\BillmateCheckout\Helper\Data $_helper,
        \Billmate\BillmateCheckout\Helper\Iframe $iframeHelper
		) {
        $this->formKey = $formKey;
		$this->helper = $_helper;
        $this->iframeHelper = $iframeHelper;
		$this->resultJsonFactory = $resultJsonFactory;
		$this->checkoutSession = $_checkoutSession;
		parent::__construct($context);
	}

	public function execute() {
		$result = $this->resultJsonFactory->create();
		if ($this->getRequest()->isAjax()) {
			$changed = false;
			if ($_POST['field2'] == 'sub'){
				$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
				$cart = $objectManager->get('\Magento\Checkout\Model\Cart');
				$product = $objectManager->get('\Magento\Catalog\Model\Product');
				$allItems = $cart->getQuote()->getAllVisibleItems();
				$id = explode('_', $_POST['field3'])[1];
				foreach ($allItems as $item) {
					if ($item->getId() == $id){
						$qty = $item->getQty();
						if ($qty > 1){
							$item->setQty($qty-1);
						}
						else {
							if (count($allItems) == 1){
								$this->helper->clearSession();
								return $result->setData("redirect");
							}
							else {
								$cart->getQuote()->removeItem($item->getId());
							}
						}
						$cart->save();
						$changed = true;
					}
				}
			}
			else if ($_POST['field2'] == 'inc'){
				$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
				$cart = $objectManager->get('\Magento\Checkout\Model\Cart');
				$product = $objectManager->get('\Magento\Catalog\Model\Product');
				$allItems = $cart->getQuote()->getAllVisibleItems();
				$id = explode('_', $_POST['field3'])[1];
				foreach ($allItems as $item){
					if ($item->getId() == $id){
						if ($item->getProduct()->getTypeId() == 'configurable'){
							$params = array(
								'form_key' => $this->formKey->getFormKey(),
								'product' => $item->getProduct()->getId(),
								'super_attribute' => $item->getBuyRequest()->getData()['super_attribute'],
								'qty' => 1,
								'price' => $item->getProduct()->getPrice()
							);
						}
						else {
							$params = array(
								'form_key' => $this->formKey->getFormKey(),
								'product' => $item->getProduct()->getId(),
								'qty' => 1,
								'price' => $item->getProduct()->getPrice()
							);
						}
						$_product = $product->load($item->getProduct()->getId());
						$cart->addProduct($_product, $params);
						$cart->save();
						$changed = true;
					}
				}
			}
			else if ($_POST['field2'] == 'radio'){
				$price = $this->helper->setShippingMethod($_POST['field3']);
				$changed = true;
			}	
			else if ($_POST['field2'] == 'submit'){
				$this->helper->setDiscountCode($_POST['field3']);
				$changed = true;
			}
			else if ($_POST['field2'] == 'del'){
				$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
				$cart = $objectManager->get('\Magento\Checkout\Model\Cart');
				$product = $objectManager->get('\Magento\Catalog\Model\Product');
				$allItems = $cart->getQuote()->getAllVisibleItems();
				$id = explode('_', $_POST['field3'])[1];
				foreach ($allItems as $item) {
					if ($item->getId() == $id){
						if (count($allItems) == 1){
							$this->helper->clearSession();
							return $result->setData("redirect");
						}
						else {
							$cart->getQuote()->removeItem($item->getId());
						}
						$cart->save();
						$changed = true;
					}
				}
			}
			else if ($_POST['field2'] == 'update'){
				$changed = true;
			}
			if ($changed) {
				$cart = $this->helper->getCart();
				$iframe = $this->iframeHelper->updateIframe();
				$return = array(
					'iframe' => $iframe,
					'cart' => $cart
				);
				return $result->setData($return);
			}
		}
	}
}
