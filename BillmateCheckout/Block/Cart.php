<?php

namespace Billmate\BillmateCheckout\Block;
 
class Cart extends \Magento\Checkout\Block\Onepage {
	
    protected $helper;
	protected $storeManager;

    protected $objectManager;

    public function __construct(
		\Magento\Framework\View\Element\Template\Context $context, 
		array $data = [], \Billmate\BillmateCheckout\Helper\Data $_helper, 
		\Magento\Framework\ObjectManagerInterface $_objectManager,
		\Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Checkout\Helper\Data $checkoutHelper,
		\Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Checkout\Model\CompositeConfigProvider $configProvider,
        array $layoutProcessors = []
	){
        parent::__construct($context, $formKey, $configProvider, $layoutProcessors, $data);
		$this->helper = $_helper;
		$this->objectManager = $_objectManager;
        $this->storeManager = $_objectManager->get('\Magento\Store\Model\StoreManagerInterface');
	}
	
	protected function _toHtml(){
		return parent::_toHtml();
	}

	public function getCart(){
		return $this->helper->getCart();
	}
	
	public function getShippingMethods(){
		$methods = array();
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$cart = $objectManager->get('\Magento\Checkout\Model\Cart');
		
		$cart->getQuote()->getBillingAddress()->addData(array(
            'firstname' => 'Testperson',
            'lastname' => 'Approved',
            'street' => 'Teststreet',
            'city' => 'Testcity',
            'country_id' => 'SE',
            'postcode' => '12345',
            'telephone' => '0700123456'
        ));
        $cart->getQuote()->getShippingAddress()->addData(array(
            'firstname' => 'Testperson',
            'lastname' => 'Approved',
            'street' => 'Teststreet',
            'city' => 'Testcity',
            'country_id' => 'SE',
            'postcode' => '12345'
        ));
		
		$shippingAddress = $cart->getQuote()->getShippingAddress();
		$shippingAddress->setCollectShippingRates(true)->collectShippingRates();//->setShippingMethod('freeshipping_freeshipping');
		$shippingMethods = $shippingAddress->getGroupedAllShippingRates();
		foreach ($shippingMethods as $shippingMethod){
			foreach ($shippingMethod as $rate){
				$methods[] = array(
					'code' => $rate->getCode(),
					'title' => $rate->getMethodTitle(),
					'price' => $rate->getPrice()
				);
			}
		}
		return $methods;
	}
	
	public function showOptions(){
		return $this->helper->getShowAttribute();
	}
	
	public function qtyChangeEnable(){
		return $this->helper->getBtnEnable();
	}
	
	public function hasDiscount(){
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$cart = $objectManager->get('\Magento\Checkout\Model\Cart');
		return $cart->getQuote()->getSubtotal() != $cart->getQuote()->getSubtotalWithDiscount();
	}
	
	public function getDiscount(){
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$priceHelper = $objectManager->create('Magento\Framework\Pricing\Helper\Data');
		$cart = $objectManager->get('\Magento\Checkout\Model\Cart');
		return $priceHelper->currency($cart->getQuote()->getSubtotalWithDiscount()-$cart->getQuote()->getSubtotal(), true, false);
	}
	
	public function getGrandTotal(){
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$priceHelper = $objectManager->create('Magento\Framework\Pricing\Helper\Data');
		$cart = $objectManager->get('\Magento\Checkout\Model\Cart');
		return $priceHelper->currency($cart->getQuote()->getGrandTotal()+$_SESSION['shippingPrice'], true, false);
	}
	
	public function getTax(){
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$priceHelper = $objectManager->create('Magento\Framework\Pricing\Helper\Data');
		$cart = $objectManager->get('\Magento\Checkout\Model\Cart');
		$totals = $cart->getQuote()->getTotals();
		foreach ($totals as $total){
			if ($total->getData()['code'] == 'tax'){
				if (isset($total->getData()['value_incl_tax'])){
					return $priceHelper->currency($total->getData()['value_incl_tax'], true, false);
				}
				else {
					return $priceHelper->currency($total->getData()['value'], true, false);
				}
			}
		}
		return $priceHelper->currency(0, true, false);
	}
	

    /**
     * Fetch cart shipping and store in session when not already stored in session
     */
    private function maybeFetchShippingPrices()
    {
        if (
                !isset($_SESSION['shippingPrice'])
                || !isset($_SESSION['shipping_code'])
                || !isset($_SESSION['billmate_shipping_tax'])
        ) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $cart = $objectManager->get('\Magento\Checkout\Model\Cart');
            $cart->getQuote()->getBillingAddress()->addData(array(
                'firstname' => 'Testperson',
                'lastname' => 'Approved',
                'street' => 'Teststreet',
                'city' => 'Testcity',
                'country_id' => 'SE',
                'postcode' => '12345',
                'telephone' => '0700123456'
            ));
            $cart->getQuote()->getShippingAddress()->addData(array(
                'firstname' => 'Testperson',
                'lastname' => 'Approved',
                'street' => 'Teststreet',
                'city' => 'Testcity',
                'country_id' => 'SE',
                'postcode' => '12345'
            ));
            $shippingAddress = $cart->getQuote()->getShippingAddress();
            $shippingAddress->setCollectShippingRates(true)->collectShippingRates()->setShippingMethod('freeshipping_freeshipping');
            $first = true;
            $methods = $shippingAddress->getGroupedAllShippingRates();
            foreach ($methods as $method){
                foreach ($method as $rate){
                    if ($first){
                        $first = false;
                        $lShippingPrice = $rate->getPrice();
                        $_SESSION['shippingPrice'] = $lShippingPrice;
                        $_SESSION['shipping_code'] = $rate->getCode();
                        $_SESSION['billmate_shipping_tax'] = $rate->getShippingTaxAmount();
                    }
                }
            }
        }
    }

	public function getShippingPrice(){
        $this->maybeFetchShippingPrices();
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$priceHelper = $objectManager->create('Magento\Framework\Pricing\Helper\Data');
		return $priceHelper->currency($_SESSION['shippingPrice'], true, false);
	}
	
	public function getCartProducts(){
		$prods = array();
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$cart = $objectManager->get('\Magento\Checkout\Model\Cart');
        $itemsVisible = $cart->getQuote()->getAllVisibleItems();
		
		$currentStore = $this->storeManager->getStore();
		$currentStoreId = $currentStore->getId();
		$taxCalculation = $this->objectManager->get('\Magento\Tax\Model\Calculation');
		$request = $taxCalculation->getRateRequest(null, null, null, $currentStoreId);
		
		foreach ($itemsVisible as $item){
			$product = $item->getProduct();
            $taxClassId = $product->getTaxClassId();
            $percent = $taxCalculation->getRate($request->setProductClassId($taxClassId));
			
			$activeOptions = "";
			if ($item->getProduct()->getTypeId() == 'configurable'){
				$productTypeInstance = $objectManager->get('Magento\ConfigurableProduct\Model\Product\Type\Configurable');
				$productAttributeOptions = $productTypeInstance->getConfigurableAttributesAsArray($item->getProduct());
				$vals = $item->getBuyRequest()->getData()['super_attribute'];
				foreach ($vals as $key => $val){
					$activeOptions .= $productAttributeOptions[$key]['label'];
					foreach ($productAttributeOptions[$key]['values'] as $value){
						if ($value['value_index'] == $val){
							$activeOptions .= ": ".$value['label'];
						}
					}
				}
			}
			$prods[] = array(
				'name' => $item->getProduct()->getName(),
				'options' => $activeOptions,
				'price' => $item->getPrice()*(1+($percent/100)),
				'id' => $item->getId(),
				'qty' => $item->getQty()
			);
		}
		return $prods;
	}
	
	public function getTotals(){
		return [];
	}
	
	public function getAjaxUrl() {
		return $this->getUrl('billmatecheckout/billmateajax/billmateajax');
	}	
}