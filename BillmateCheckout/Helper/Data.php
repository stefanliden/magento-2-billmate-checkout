<?php

namespace Billmate\BillmateCheckout\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper {

    protected $_storeManager;
    protected $_productFactory;
    protected $customerFactory;
    protected $customerRepository;
    protected $cartRepositoryInterface;
    protected $cartManagementInterface;
    protected $shippingRate;
    protected $quoteCollectionFactory;
    protected $shipconfig;
    protected $resource;
    protected $_product;
    protected $scopeConfig;
    protected $checkoutSession;
    protected $shippingMethodManagementInterface;
    protected $quoteManagement;
    protected $quote;
    protected $orderInterface;
	protected $shippingPrice;
	protected $_cart;

    const XML_PATH_GENERAL_ENABLE = 'billmate_billmatecheckout/general/enable';
    const XML_PATH_GENERAL_PUSHORDEREVENTS = 'billmate_billmatecheckout/general/pushorderevents';
    const XML_PATH_GENERAL_CUSTOMCSS = 'billmate_billmatecheckout/general/customcss';
    const XML_PATH_GENERAL_TERMS_URL = 'billmate_billmatecheckout/general/terms_url';
    const XML_PATH_GENERAL_PRIVACY_POLICY_PAGE = 'billmate_billmatecheckout/general/privacy_policy_page';
    const XML_PATH_CREDENTIALS_ID = 'billmate_billmatecheckout/credentials/billmate_id';
    const XML_PATH_CREDENTIALS_KEY = 'billmate_billmatecheckout/credentials/billmate_key';
    const XML_PATH_GENERAL_TESTMODE = 'billmate_billmatecheckout/credentials/testmode';
    const XML_PATH_PENDING_FETCH = 'billmate_billmatecheckout/pending_settings/fetch';
    const XML_PATH_PENDING_MULTISELECT = 'billmate_billmatecheckout/pending_settings/multiselect';
    const XML_PATH_PENDING_PENDING_CONTROL = 'billmate_billmatecheckout/pending_settings/bm_pending_control';
    const XML_PATH_PENDING_DENY = 'billmate_billmatecheckout/pending_settings/bm_deny';
    const XML_PATH_PENDING_ACTIVATED = 'billmate_billmatecheckout/pending_settings/bm_activated';
    const XML_PATH_PENDING_CANCELED = 'billmate_billmatecheckout/pending_settings/bm_canceled';
    const XML_PATH_PENDING_ENABLE = 'billmate_billmatecheckout/pending_settings/enable';

    public function __construct(
		\Magento\Framework\App\Helper\Context $context, 
		\Magento\Store\Model\StoreManagerInterface $storeManager, 
		\Magento\Catalog\Model\ProductFactory $productfact, 
		\Magento\Catalog\Model\Product $product, 
		\Magento\Customer\Model\CustomerFactory $customerFactory, 
		\Magento\Customer\Api\CustomerRepositoryInterface $customerRepository, 
		\Magento\Quote\Api\CartRepositoryInterface $cartRepositoryInterface, 
		\Magento\Quote\Api\CartManagementInterface $cartManagementInterface, 
		\Magento\Quote\Model\Quote\Address\Rate $shippingRate, 
		\Magento\Shipping\Model\Config $shipconfig, 
		\Magento\Sales\Api\Data\OrderInterface $order, 
		\Magento\Framework\App\ResourceConnection $resource, 
		\Magento\Checkout\Model\Session $_checkoutSession, 
		\Magento\Quote\Model\QuoteManagement $quoteManagement, 
		\Magento\Quote\Model\QuoteFactory $quote, 
		\Magento\Quote\Api\ShippingMethodManagementInterface $_shippingMethodManagementInterface, 
		\Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quoteCollectionFactory,
        \Magento\Checkout\Model\Cart $_cart,
        \Billmate\Billmate\Logger\Logger $logger
	){
        $this->_product = $product;
        $this->orderInterface = $order;
        $this->_storeManager = $storeManager;
        $this->_productFactory = $productfact;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->cartRepositoryInterface = $cartRepositoryInterface;
        $this->cartManagementInterface = $cartManagementInterface;
		$this->_cart = $_cart;
        $this->shippingRate = $shippingRate;
        $this->shipconfig = $shipconfig;
        $this->resource = $resource;
        $this->scopeConfig = $context->getScopeConfig();
        $this->checkoutSession = $_checkoutSession;
        $this->quoteManagement = $quoteManagement;
        $this->quote = $quote;
        $this->shippingMethodManagementInterface = $_shippingMethodManagementInterface;
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->logger = $logger;
        parent::__construct($context);
    }

	public function getShippingPrice($inclFormatting = true){
		
	}
	
	public function setShippingAddress($inputunf){
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$input = array();
		foreach ($inputunf as $key => $val){
			$input[$key] = utf8_decode($val);
		}
        $cart = $objectManager->get('\Magento\Checkout\Model\Cart');
		if (array_key_exists('telephone', $input)){
			$cart->getQuote()->getShippingAddress()->addData(array(
				'firstname' => $input['firstname'],
				'lastname' => $input['lastname'],
				'street' => $input['street'],
				'city' => $input['city'],
				'country_id' => $input['country_id'],
				'postcode' => $input['postcode'],
				'telephone' => $input['telephone']
			));
			$_SESSION['billmate_shipping_address'] = array(
				'firstname' => $input['firstname'],
				'lastname' => $input['lastname'],
				'street' => $input['street'],
				'city' => $input['city'],
				'country_id' => $input['country_id'],
				'postcode' => $input['postcode'],
				'telephone' => $input['telephone']
			);
		}
		else if (isset($_SESSION['billmate_telephone'])){
			$cart->getQuote()->getShippingAddress()->addData(array(
				'firstname' => $input['firstname'],
				'lastname' => $input['lastname'],
				'street' => $input['street'],
				'city' => $input['city'],
				'country_id' => $input['country_id'],
				'postcode' => $input['postcode'],
				'telephone' => $_SESSION['billmate_telephone']
			));
			$_SESSION['billmate_shipping_address'] = array(
				'firstname' => $input['firstname'],
				'lastname' => $input['lastname'],
				'street' => $input['street'],
				'city' => $input['city'],
				'country_id' => $input['country_id'],
				'postcode' => $input['postcode'],
				'telephone' => $_SESSION['billmate_telephone']
			);
		}
		else {
			$cart->getQuote()->getShippingAddress()->addData(array(
				'firstname' => $input['firstname'],
				'lastname' => $input['lastname'],
				'street' => $input['street'],
				'city' => $input['city'],
				'country_id' => $input['country_id'],
				'postcode' => $input['postcode']
			));
			$_SESSION['billmate_shipping_address'] = array(
				'firstname' => $input['firstname'],
				'lastname' => $input['lastname'],
				'street' => $input['street'],
				'city' => $input['city'],
				'country_id' => $input['country_id'],
				'postcode' => $input['postcode']
			);
		}
	}
	
	public function setBillingAddress($inputunf){
		$input = array();
		foreach ($inputunf as $key => $val){
			$input[$key] = utf8_decode($val);
		}
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cart = $objectManager->get('\Magento\Checkout\Model\Cart');
		$cart->getQuote()->getBillingAddress()->addData(array(
            'firstname' => $input['firstname'],
            'lastname' => $input['lastname'],
            'street' => $input['street'],
            'city' => $input['city'],
            'country_id' => $input['country_id'],
            'postcode' => $input['postcode'],
            'telephone' => $input['telephone']
        ));
		$_SESSION['billmate_billing_address'] = array(
            'firstname' => $input['firstname'],
            'lastname' => $input['lastname'],
            'street' => $input['street'],
            'city' => $input['city'],
            'country_id' => $input['country_id'],
            'postcode' => $input['postcode'],
            'telephone' => $input['telephone']
        );
		$_SESSION['billmate_telephone'] = $input['telephone'];
		if (!isset($_SESSION['billmate_shipping_address'])){
			$this->setShippingAddress($input);
		}
		$_SESSION['billmate_email'] = $input['email'];
	}
	
    public function setShippingMethod($methodInput){
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cart = $objectManager->get('\Magento\Checkout\Model\Cart');
		$shippingAddress = $cart->getQuote()->getShippingAddress();
		$shippingAddress->setCollectShippingRates(true)->collectShippingRates();//->setShippingMethod($methodInput);
		$currentStore = $this->_storeManager->getStore();
		$currentStoreId = $currentStore->getId();
		$taxCalculation = $objectManager->get('\Magento\Tax\Model\Calculation');
		$request = $taxCalculation->getRateRequest(null, null, null, $currentStoreId);
		$shippingTaxClass = $this->getShippingTaxClass();
		$shippingTax = $taxCalculation->getRate($request->setProductClassId($shippingTaxClass));
		$rates = $shippingAddress->getAllShippingRates();
		$_SESSION['shipping_code'] = $methodInput;
        foreach ($rates as $rate){
            if ($rate->getCode() == $methodInput){
				if ($shippingTax == 0){
					$lShippingPrice = $rate->getPrice();
				}
				else {
					$lShippingPrice = ($rate->getPrice()*(1+($shippingTax/100)));
				}
				$shippingAddress->setShippingMethod($rate->getCode());
				$shippingAddress->save();
				$first = false;
				$this->shippingRate->setCode($rate->getCode());
				$this->shippingPrice = $lShippingPrice;
				$_SESSION['shippingPrice'] = $lShippingPrice;
				$_SESSION['shipping_code'] = $rate->getCode();
				$_SESSION['billmate_shipping_tax'] = $rate->getShippingTaxAmount();
			}
        }
    }

    public function setDiscountCode($code){
	    $this->checkoutSession->getQuote()->setCouponCode($code)->collectTotals()->save();
	    $_SESSION['billmate_applied_discount_code'] = $code;
    }

    public function updateIframe(){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cart = $objectManager->get('\Magento\Checkout\Model\Cart');
        $itemsVisible = $cart->getQuote()->getAllVisibleItems();
        $allItems = $cart->getQuote()->getAllItems();
        $billmate_checkout_id = $_SESSION['billmate_checkout_id'];
		$lShippingPrice = $_SESSION['shippingPrice'];
        for ($i = 0; $i < 10; $i++){
            if (empty($allItems))
                sleep(1);
            else
                break;
        }

        if (empty($itemsVisible)){
            if (empty($allItems)){
                if (!is_object($cart->getQuote())){
                    if (!is_object($cart)){
                        if (!is_object($objectManager)){
                            return "<h1>object manager does not exist</h1><br>";
                        }
                        return "<h1>cart does not exist</h1><br>";
                    }
                    return "<h1>quote does not exist</h1><br>";
                }
                return "<h1>No items found in cart</h1><br>";
            }
            return "<h1>No visible items found in cart</h1><br>";
        }
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $url = $storeManager->getStore()->getBaseUrl();
		
		$shippingAddress = $cart->getQuote()->getShippingAddress();
	
		$shippingAddress->setCollectShippingRates(true)->collectShippingRates()->setShippingMethod('freeshipping_freeshipping');
		$methods = $shippingAddress->getGroupedAllShippingRates();
		$rates = array();
		foreach ($methods as $method){
				foreach ($method as $rate){
					array_push ($rates, $rate);
				}
		}
        foreach ($rates as $rate){
            if ($rate->getCode() == $_SESSION['shipping_code']){
				$lShippingPrice = $rate->getPrice();
				$this->shippingPrice = $lShippingPrice;
				$_SESSION['shippingPrice'] = $lShippingPrice;
				$_SESSION['shipping_code'] = $rate->getCode();
				$_SESSION['billmate_shipping_tax'] = $rate->getShippingTaxAmount();
			}
		}
        if (empty($cart->getQuote()->getReservedOrderId())){
            $cart->getQuote()->reserveOrderId()->save();
        }

        $data = array(
            'CheckoutData' => array(
                'windowmode' => 'iframe',
                'sendreciept' => 'yes',
                'terms' => $this->getTermsURL(),
				'redirectOnSuccess' => 'true'
            ),
            'PaymentData' => array(
                'method' => '93',
                'currency' => 'SEK',
                'language' => 'sv',
                'country' => 'SE',
                'orderid' => $cart->getQuote()->getReservedOrderId(),
                'callbackurl' => $url . "billmatecheckout/callback/callback",
                "accepturl" => $url . "billmatecheckout/success/success/",
                'number' => $billmate_checkout_id,
				"cancelurl" => $url . "billmatecheckout"
            ),
            'Articles' => array(),
            'Cart' => array()
        );

        $privacyPolicyUrl = $this->getPrivacyPolicyUrl();
        if ($privacyPolicyUrl != '') {
            $data['CheckoutData']['privacyPolicy'] = $privacyPolicyUrl;
        }
		
		$currentStore = $this->_storeManager->getStore();
		$currentStoreId = $currentStore->getId();
		$taxCalculation = $objectManager->get('\Magento\Tax\Model\Calculation');
		$request = $taxCalculation->getRateRequest(null, null, null, $currentStoreId);
		$productLoader = $objectManager->get('\Magento\Catalog\Model\Product');
		$taxAmount = 0;
		$discounts = array();
		$subtotal = 0;
		array_push($data['Articles'], array(
            'quantity' => '1',
            'artnr' => 'shipping_code',
            'title' => $_SESSION['shipping_code'],
            'aprice' => '0',
            'taxrate' => '0',
            'discount' => '0',
            'withouttax' => '0'
        ));
        if (isset($_SESSION['billmate_applied_discount_code'])){
             array_push($data['Articles'], array(
                'quantity' => '1',
                'artnr' => 'discount_code',
                'title' => $_SESSION['billmate_applied_discount_code'],
                'aprice' => '0',
                'taxrate' => '0',
                'discount' => '0',
                'withouttax' => '0'
             ));
        }
        $sum = 0;
		$sumex = 0;
		$productLoader2 = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Catalog\Api\ProductRepositoryInterface');
        foreach ($itemsVisible as $item){
			$product = $productLoader2->get($item->getSku());
			$taxClassId = $product->getTaxClassId();
			$percent = $taxCalculation->getRate($request->setProductClassId($taxClassId));
            $prod = array(
                'quantity' => $item->getQty(),
                'artnr' => $item->getSku(),
                'title' => $item->getName(),
                'aprice' => $item->getPrice() * 100,
                'taxrate' => $percent,
                'discount' => ($item->getDiscountPercent()),
                'withouttax' => ($item->getQty() * $item->getPrice() * 100)
            );
			$sum = $sum + ($item->getQty() * $item->getPrice())*(1+($percent/100));
			$sumex = $sumex + ($item->getQty() * $item->getPrice());
			$subtotal += ($item->getQty() * $item->getPrice() * 100);
			if (isset($discounts[$percent])){
				$discounts[$percent] += ($item->getQty() * $item->getPrice() * 100);
			}
			else {
				$discounts[$percent] = ($item->getQty() * $item->getPrice() * 100);
			}
			$taxAmount = $taxAmount + ((($item->getQty() * $item->getPrice() * 100) * (1+($percent/100))) - ($item->getQty() * $item->getPrice() * 100));
			$taxAmount = round($taxAmount,0);
            array_push($data['Articles'], $prod);
        }
		$shippingTaxClass = $this->getShippingTaxClass();
		$shippingTax = $taxCalculation->getRate($request->setProductClassId($shippingTaxClass));
		if ($shippingTax == 0){
			$shippingInclTax = $lShippingPrice;
			if (($cart->getQuote()->getSubtotal()-$cart->getQuote()->getSubtotalWithDiscount()) > 0){
				$totalDiscountAmount = ($cart->getQuote()->getSubtotal()-$cart->getQuote()->getSubtotalWithDiscount());
				foreach ($discounts as $key => $val){
					$discountPercent = $val / $subtotal;
					$discountAmount = $discountPercent * $totalDiscountAmount;
					array_push($data['Articles'], array(
						'quantity' => '1',
						'artnr' => 'discount_' . $key . '%',
						'title' => 'discount_' . $key . '%',
						'aprice' => $discountAmount*100/(1+($key/100))*(0-1),
						'taxrate' => $key,
						'discount' => '0',
						'withouttax' => $discountAmount*100/(1+($key/100))*(0-1)
					));
				}
			}
			$totalExclTax = round((($sum+$shippingInclTax-($cart->getQuote()->getSubtotal()-$cart->getQuote()->getSubtotalWithDiscount()))*100)-(($sum-$sumex+($shippingInclTax-$lShippingPrice)+(($cart->getQuote()->getSubtotal()-$cart->getQuote()->getSubtotalWithDiscount())/(1+($shippingTax/100)))-($cart->getQuote()->getSubtotal()-$cart->getQuote()->getSubtotalWithDiscount()))*100));
			$tax = round((($sum-$sumex+($shippingInclTax-$lShippingPrice)+(($cart->getQuote()->getSubtotal()-$cart->getQuote()->getSubtotalWithDiscount())/(1+($shippingTax/100)))-($cart->getQuote()->getSubtotal()-$cart->getQuote()->getSubtotalWithDiscount()))*100));
			$grandTotal = round(($sum+$shippingInclTax-($cart->getQuote()->getSubtotal()-$cart->getQuote()->getSubtotalWithDiscount()))*100);
			$round = $grandTotal - ($totalExclTax + $tax);
			$cart2 = array(
				'Shipping' => array(
					'withouttax' => $lShippingPrice*100,
					'taxrate' => $shippingTax
				),
				'Total' => array(
					'withouttax' => $totalExclTax,
					'tax' => $tax,
					'rounding' => $round,
					'withtax' => $grandTotal
				)
			);
		}
		else {
			$shippingInclTax = $lShippingPrice;
			if (($cart->getQuote()->getSubtotal()-$cart->getQuote()->getSubtotalWithDiscount()) > 0){
				$totalDiscountAmount = ($cart->getQuote()->getSubtotal()-$cart->getQuote()->getSubtotalWithDiscount());
				foreach ($discounts as $key => $val){
					$discountPercent = $val / $subtotal;
					$discountAmount = $discountPercent * $totalDiscountAmount;
					array_push($data['Articles'], array(
						'quantity' => '1',
						'artnr' => 'discount_' . $key . '%',
						'title' => 'discount_' . $key . '%',
						'aprice' => $discountAmount*100/(1+($key/100))*(0-1),
						'taxrate' => $key,
						'discount' => '0',
						'withouttax' => $discountAmount*100/(1+($key/100))*(0-1)
					));
				}
			}
			$totalExclTax = round((($sum+$shippingInclTax-($cart->getQuote()->getSubtotal()-$cart->getQuote()->getSubtotalWithDiscount()))*100)-(($sum-$sumex+($shippingInclTax-$lShippingPrice)+(($cart->getQuote()->getSubtotal()-$cart->getQuote()->getSubtotalWithDiscount())/(1+($shippingTax/100)))-($cart->getQuote()->getSubtotal()-$cart->getQuote()->getSubtotalWithDiscount()))*100));
			$tax = round((($sum-$sumex+($shippingInclTax-$lShippingPrice)+(($cart->getQuote()->getSubtotal()-$cart->getQuote()->getSubtotalWithDiscount())/(1+($shippingTax/100)))-($cart->getQuote()->getSubtotal()-$cart->getQuote()->getSubtotalWithDiscount()))*100));
			$grandTotal = round(($sum+$shippingInclTax-($cart->getQuote()->getSubtotal()-$cart->getQuote()->getSubtotalWithDiscount()))*100);
			$round = $grandTotal - ($totalExclTax + $tax);
			$cart2 = array(
				'Shipping' => array(
					'withouttax' => $lShippingPrice*100,
					'taxrate' => $shippingTax
				),
				'Total' => array(
					'withouttax' => $totalExclTax,
					'tax' => $tax,
					'rounding' => $round,
					'withtax' => $grandTotal
				)
			);
		}
        $data['Cart'] = $cart2;

        $values = array(
            "credentials" => array(
                "id" => $this->getBillmateId(),
                "hash" => hash_hmac('sha512', json_encode($data), $this->getBillmateSecret()),
                "version" => '2.1.7',
                "client" => $this->getClientVersion(),
                "serverdata" => $_SERVER,
                "time" => microtime(true),
                "test" => $this->getTestMode(),
                "language" => 'sv'
            ),
            "data" => $data,
            "function" => 'updateCheckout',
        );
        $enc = json_encode($values);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.billmate.se");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $path = __DIR__ . '/cacert.pem';
        curl_setopt($ch, CURLOPT_CAINFO, $path);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($enc))
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $enc);
        $out = curl_exec($ch);
        if (curl_errno($ch)){
            $curlerror = curl_error($ch);
        }
        else{
            curl_close($ch);
        }
        $return = json_decode($out);
        return $return->data->url;
    }

    public function getIframe(){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cart = $objectManager->get('\Magento\Checkout\Model\Cart');
        $itemsVisible = $cart->getQuote()->getAllVisibleItems();
        $allItems = $cart->getQuote()->getAllItems();

        for ($i = 0; $i < 10; $i++){
            if (empty($allItems))
                sleep(1);
            else
                break;
        }

        if (empty($itemsVisible)){
            if (empty($allItems)){
                if (!is_object($cart->getQuote())){
                    if (!is_object($cart)){
                        if (!is_object($objectManager)) {
                            return "<h1>object manager does not exist</h1><br>";
                        }
                        return "<h1>cart does not exist</h1><br>";
                    }
                    return "<h1>quote does not exist</h1><br>";
                }
                return "<h1>No items found in cart</h1><br>";
            }
            return "<h1>No visible items found in cart</h1><br>";
        }
		
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
					$this->shippingRate->setCode($rate->getCode());
					$this->shippingPrice = $lShippingPrice;
					$_SESSION['shippingPrice'] = $lShippingPrice;
					$_SESSION['shipping_code'] = $rate->getCode();
					$_SESSION['billmate_shipping_tax'] = $rate->getShippingTaxAmount();
				}
			}
		}
		
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $url = $storeManager->getStore()->getBaseUrl();

        if (empty($cart->getQuote()->getReservedOrderId())){
            $cart->getQuote()->reserveOrderId()->save();
        }

        $data = array(
            'CheckoutData' => array(
                'windowmode' => 'iframe',
                'sendreciept' => 'yes',
                'terms' => $this->getTermsURL(),
				'redirectOnSuccess' => 'true'
            ),
            'PaymentData' => array(
                'method' => '93',
                'currency' => 'SEK',
                'language' => 'sv',
                'country' => 'SE',
                'orderid' => $cart->getQuote()->getReservedOrderId(),
                'callbackurl' => $url . "billmatecheckout/callback/callback",
                "accepturl" => $url . "billmatecheckout/success/success/",
				"cancelurl" => $url . "billmatecheckout"
            ),
            'Articles' => array(),
            'Cart' => array()
        );

        $privacyPolicyUrl = $this->getPrivacyPolicyUrl();
        if ($privacyPolicyUrl != '') {
            $data['CheckoutData']['privacyPolicy'] = $privacyPolicyUrl;
        }
		
		$currentStore = $this->_storeManager->getStore();
		$currentStoreId = $currentStore->getId();
		$taxCalculation = $objectManager->get('\Magento\Tax\Model\Calculation');
		$request = $taxCalculation->getRateRequest(null, null, null, $currentStoreId);
		$productLoader = $objectManager->get('\Magento\Catalog\Model\Product');
		$taxAmount = 0;
		$discounts = array();
		$subtotal = 0;
		array_push($data['Articles'], array(
			'quantity' => '1',
			'artnr' => 'shipping_code',
			'title' => $_SESSION['shipping_code'],
			'aprice' => '0',
			'taxrate' => '0',
			'discount' => '0',
			'withouttax' => '0'
		));
		if (isset($_SESSION['billmate_applied_discount_code'])){
			 array_push($data['Articles'], array(
				'quantity' => '1',
                'artnr' => 'discount_code',
                'title' => $_SESSION['billmate_applied_discount_code'],
                'aprice' => '0',
                'taxrate' => '0',
                'discount' => '0',
                'withouttax' => '0'
			 ));
		}
		$sum = 0;
		$sumex = 0;
        foreach ($itemsVisible as $item){
			$product = $productLoader->load($item->getProduct()->getId());
			$taxClassId = $product->getTaxClassId();
			$percent = $taxCalculation->getRate($request->setProductClassId($taxClassId));
			
            $prod = array(
                'quantity' => $item->getQty(),
                'artnr' => $item->getSku(),
                'title' => $item->getName(),
                'aprice' => $item->getPrice() * 100,
                'taxrate' => $percent,
                'discount' => ($item->getDiscountPercent()),
                'withouttax' => ($item->getQty() * $item->getPrice() * 100)
            );
			$sum = $sum + ($item->getQty() * $item->getPrice())*(1+($percent/100));
			$sumex = $sumex + ($item->getQty() * $item->getPrice());
			$subtotal += ($item->getQty() * $item->getPrice() * 100);
			if (isset($discounts[$percent])){
				$discounts[$percent] += ($item->getQty() * $item->getPrice() * 100);
			}
			else {
				$discounts[$percent] = ($item->getQty() * $item->getPrice() * 100);
			}
			$taxAmount = $taxAmount + ((($item->getQty() * $item->getPrice() * 100) * (1+($percent/100))) - ($item->getQty() * $item->getPrice() * 100));
			$taxAmount = round($taxAmount,0);
            array_push($data['Articles'], $prod);
        }
		$shippingTaxClass = $this->getShippingTaxClass();
		$shippingTax = $taxCalculation->getRate($request->setProductClassId($shippingTaxClass));
		if ($shippingTax == 0){
			
			$shippingInclTax = $lShippingPrice;
			if (($cart->getQuote()->getSubtotal()-$cart->getQuote()->getSubtotalWithDiscount()) > 0){
				$totalDiscountAmount = ($cart->getQuote()->getSubtotal()-$cart->getQuote()->getSubtotalWithDiscount());
				foreach ($discounts as $key => $val){
					$discountPercent = $val / $subtotal;
					$discountAmount = $discountPercent * $totalDiscountAmount;
					array_push($data['Articles'], array(
						'quantity' => '1',
						'artnr' => 'discount_' . $key . '%',
						'title' => 'discount_' . $key . '%',
						'aprice' => $discountAmount*100/(1+($key/100))*(0-1),
						'taxrate' => $key,
						'discount' => '0',
						'withouttax' => $discountAmount*100/(1+($key/100))*(0-1)
					));
				}
			}
			$totalExclTax = round((($sum+$shippingInclTax-($cart->getQuote()->getSubtotal()-$cart->getQuote()->getSubtotalWithDiscount()))*100)-(($sum-$sumex+($shippingInclTax-$lShippingPrice)+(($cart->getQuote()->getSubtotal()-$cart->getQuote()->getSubtotalWithDiscount())/(1+($shippingTax/100)))-($cart->getQuote()->getSubtotal()-$cart->getQuote()->getSubtotalWithDiscount()))*100));
			$tax = round((($sum-$sumex+($shippingInclTax-$lShippingPrice)+(($cart->getQuote()->getSubtotal()-$cart->getQuote()->getSubtotalWithDiscount())/(1+($shippingTax/100)))-($cart->getQuote()->getSubtotal()-$cart->getQuote()->getSubtotalWithDiscount()))*100));
			$grandTotal = round(($sum+$shippingInclTax-($cart->getQuote()->getSubtotal()-$cart->getQuote()->getSubtotalWithDiscount()))*100);
			$round = $grandTotal - ($totalExclTax + $tax);
			$cart2 = array(
				'Shipping' => array(
					'withouttax' => $lShippingPrice*100,
					'taxrate' => $shippingTax
				),
				'Total' => array(
					'withouttax' => $totalExclTax,
					'tax' => $tax,
					'rounding' => $round,
					'withtax' => $grandTotal
				)
			);
		}
		else {
			$shippingInclTax = $lShippingPrice;
			if (($cart->getQuote()->getSubtotal()-$cart->getQuote()->getSubtotalWithDiscount()) > 0){
				$totalDiscountAmount = ($cart->getQuote()->getSubtotal()-$cart->getQuote()->getSubtotalWithDiscount());
				foreach ($discounts as $key => $val){
					$discountPercent = $val / $subtotal;
					$discountAmount = $discountPercent * $totalDiscountAmount;
					array_push($data['Articles'], array(
						'quantity' => '1',
						'artnr' => 'discount_' . $key . '%',
						'title' => 'discount_' . $key . '%',
						'aprice' => $discountAmount*100/(1+($key/100))*(0-1),
						'taxrate' => $key,
						'discount' => '0',
						'withouttax' => $discountAmount*100/(1+($key/100))*(0-1)
					));
				}
			}
			$totalExclTax = round((($sum+$shippingInclTax-($cart->getQuote()->getSubtotal()-$cart->getQuote()->getSubtotalWithDiscount()))*100)-(($sum-$sumex+($shippingInclTax-$lShippingPrice)+(($cart->getQuote()->getSubtotal()-$cart->getQuote()->getSubtotalWithDiscount())/(1+($shippingTax/100)))-($cart->getQuote()->getSubtotal()-$cart->getQuote()->getSubtotalWithDiscount()))*100));
			$tax = round((($sum-$sumex+($shippingInclTax-$lShippingPrice)+(($cart->getQuote()->getSubtotal()-$cart->getQuote()->getSubtotalWithDiscount())/(1+($shippingTax/100)))-($cart->getQuote()->getSubtotal()-$cart->getQuote()->getSubtotalWithDiscount()))*100));
			$grandTotal = round(($sum+$shippingInclTax-($cart->getQuote()->getSubtotal()-$cart->getQuote()->getSubtotalWithDiscount()))*100);
			$round = $grandTotal - ($totalExclTax + $tax);
			$cart2 = array(
				'Shipping' => array(
					'withouttax' => $lShippingPrice*100,
					'taxrate' => $shippingTax
				),
				'Total' => array(
					'withouttax' => $totalExclTax,
					'tax' => $tax,
					'rounding' => $round,
					'withtax' => $grandTotal
				)
			);
		}
        $data['Cart'] = $cart2;
		
        $values = array(
            "credentials" => array(
                "id" => $this->getBillmateId(),
                "hash" => hash_hmac('sha512', json_encode($data), $this->getBillmateSecret()),
                "version" => '2.1.7',
                "client" => $this->getClientVersion(),
                "serverdata" => $_SERVER,
                "time" => microtime(true),
                "test" => $this->getTestMode(),
                "language" => 'sv'
            ),
            "data" => $data,
            "function" => 'initCheckout',
        );
		
        $enc = json_encode($values);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.billmate.se");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $path = __DIR__ . '/cacert.pem';
        curl_setopt($ch, CURLOPT_CAINFO, $path);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($enc))
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $enc);
        $out = curl_exec($ch);
        if (curl_errno($ch)){
            $curlerror = curl_error($ch);
        }
        else{
            curl_close($ch);
        }
        $return = json_decode($out);
		try {
			$_SESSION['billmate_checkout_id'] = $return->data->number;
			return $return->data->url;
			return "<iframe id=\"checkout\" src=\"" . $return->data->url . "\" style=\"width: 100%; min-height: 800px; height: 800px; border:none;\" sandbox=\"allow-same-origin allow-scripts allow-modals allow-popups allow-forms allow-top-navigation\"></iframe>";
		}
		catch (\Exception $e){
			return $return->message;
		}
	}

    public function getCheckout(){
        $str = "<p>" . __("We'll email you an order confirmation with details and tracking info.") . "</p>";
        $str .= "<p>" . __('Your order # is: <span>%1</span>.', $_SESSION['bm-inc-id']) . "</p>";
        $str .= "<form action=\"//" . $_SERVER['HTTP_HOST'] . "\">
					<input type=\"submit\" value=\"" . __('Continue Shopping') . "\" />
				</form>";
		$_SESSION['bm-inc-id'] = null;
		return $str;
    }

    public function createOrder($orderData, $orderID = '', $paymentID = ''){
		try {
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			if ($orderID == ''){
				$orderID = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Checkout\Model\Cart')->getQuote()->getReservedOrderId();
			}
			$exOrder = $this->orderInterface->loadByIncrementId($orderID);
			if ($exOrder->getIncrementId()){
				return;
			}
			else{
			}
			if (isset($_SESSION['billmate_applied_discount_code'])){
				$discountCode = $_SESSION['billmate_applied_discount_code'];
			}
			$shippingCode = $_SESSION['shipping_code'];
			
			$actual_quote = $this->quoteCollectionFactory->create()->addFieldToFilter("reserved_order_id", $orderID)->getFirstItem();
			
			$actual_quote_id = $actual_quote->getId();
			
			
			//init the store id and website id @todo pass from array
			$store = $this->_storeManager->getStore();
			$websiteId = $this->_storeManager->getStore()->getWebsiteId();
			//init the customer
			$customer = $this->customerFactory->create();
			$customer->setWebsiteId($websiteId);
			$customer->loadByEmail($orderData['email']); // load customer by email address
			//check the customer
			if (!$customer->getEntityId()){
				//If not avilable then create this customer
				$fname = utf8_decode($orderData['shipping_address']['firstname']);
				$lname = utf8_decode($orderData['shipping_address']['lastname']);
				$customer->setWebsiteId($websiteId)
						->setStore($store)
						->setFirstname($fname)
						->setLastname($lname)
						->setEmail($orderData['email'])
						->setPassword($orderData['email']);
				$customer->save();
			}
			$customer->setEmail($orderData['email']);
			$customer->save();
			$actual_quote->setCustomerEmail($orderData['email']);
			
			$actual_quote->setStore($store);
			$customer = $this->customerRepository->getById($customer->getEntityId());
			$actual_quote->setCurrency();
			$actual_quote->assignCustomer($customer);
			if (isset($_SESSION['billmate_applied_discount_code'])){
				$actual_quote->setCouponCode($discountCode);
			}
			//Set Address to quote @todo add section in order data for seperate billing and handle it
			$actual_quote->getBillingAddress()->addData($_SESSION['billmate_billing_address']);
			if (isset($_SESSION['billmate_shipping_address'])){
				$actual_quote->getShippingAddress()->addData($_SESSION['billmate_shipping_address']);
                $this->logger->info("shippingaddress.street: ".print_r($actual_quote->getShippingAddress()->getStreet(), true));
			}
			else {
				$actual_quote->getShippingAddress()->addData($_SESSION['billmate_billing_address']);
			}
			// Collect Rates and Set Shipping & Payment Method
			$this->shippingRate->setCode($shippingCode)->getPrice();
			$shippingAddress = $actual_quote->getShippingAddress();
			//@todo set in order data
			$shippingAddress->setCollectShippingRates(true)
					->collectShippingRates()
					->setShippingMethod($shippingCode); //shipping method
			$actual_quote->getShippingAddress()->addShippingRate($this->shippingRate);
			$actual_quote->setPaymentMethod($_SESSION['billmate_payment_method']); //payment method
			$actual_quote->getPayment()->importData(['method' => $_SESSION['billmate_payment_method']]);
			$actual_quote->setReservedOrderId($orderID);
			// Collect total and save
			$actual_quote->collectTotals();
			// Submit the quote and create the order
			$actual_quote->save();
			$cart = $this->cartRepositoryInterface->get($actual_quote->getId());
			$cart->setCustomerEmail($orderData['email']);
			$cart->setCustomerId($customer->getId());
			$cart->getBillingAddress()->addData($_SESSION['billmate_billing_address']);
			if (isset($_SESSION['billmate_shipping_address'])){
				$cart->getShippingAddress()->addData($_SESSION['billmate_shipping_address']);
			}
			else {
				$cart->getShippingAddress()->addData($_SESSION['billmate_billing_address']);
			}
			$cart->getBillingAddress()->setCustomerId($customer->getId());
			$cart->getShippingAddress()->setCustomerId($customer->getId());
			$cart->save();
			$cart->getBillingAddress()->setCustomerId($customer->getId());
			$cart->getShippingAddress()->setCustomerId($customer->getId());
			
			$cart->setCustomerId($customer->getId());
			$cart->assignCustomer($customer);
			if (isset($_SESSION['billmate_shipping_address'])){
				$cart->getShippingAddress()->addData($_SESSION['billmate_shipping_address']);
			}
			else {
				$cart->getShippingAddress()->addData($_SESSION['billmate_billing_address']);
			}
			$cart->save();
			
			$order_id = $this->cartManagementInterface->placeOrder($cart->getId());
			$order = $objectManager->create('\Magento\Sales\Model\Order')->load($order_id);
			$emailSender = $objectManager->create('\Magento\Sales\Model\Order\Email\Sender\OrderSender');
			$emailSender->send($order);
			$_SESSION['bm-inc-id'] = $order->getIncrementId();
			
			$orderState = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
			$order->setState($orderState)->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
			$order->save();
			return $order_id;
		}
		catch (\Exception $e){
            $errorMessage = "Could not create order. Error: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
            $this->logger->info($errorMessage);
			return 0;
		}
    }
	
	public function clearSession(){
		$_SESSION['shippingPrice'] = null;
		$_SESSION['shipping_code'] = null;
		$_SESSION['billmate_shipping_tax'] = null;
		$_SESSION['billmate_shipping_address'] = null;
		$_SESSION['billmate_billing_address'] = null;
		$_SESSION['billmate_email'] = null;
		$_SESSION['billmate_applied_discount_code'] = null;
		$_SESSION['billmate_checkout_id'] = null;
		$_SESSION['billmate_payment_method'] = null;
	}

    public function getEnable(){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_GENERAL_ENABLE, $storeScope);
    }

    public function getBillmateId(){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_CREDENTIALS_ID, $storeScope);
    }

    public function getBillmateSecret(){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_CREDENTIALS_KEY, $storeScope);
    }

    public function getPushEvents(){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_GENERAL_PUSHORDEREVENTS, $storeScope);
    }

    public function getCustomCss(){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_GENERAL_CUSTOMCSS, $storeScope);
    }

    public function getTestMode(){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_GENERAL_TESTMODE, $storeScope);
    }

    public function getFetch(){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_PENDING_FETCH, $storeScope);
    }

    public function getMultiSelect(){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_PENDING_MULTISELECT, $storeScope);
    }

    public function getPendingControl(){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_PENDING_PENDING_CONTROL, $storeScope);
    }

    public function getDeny(){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_PENDING_DENY, $storeScope);
    }

    public function getActivated(){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_PENDING_ACTIVATED, $storeScope);
    }
	
	public function getShippingTaxClass(){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue('tax/classes/shipping_tax_class', $storeScope);
    }

    public function getCanceled(){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_PENDING_CANCELED, $storeScope);
    }

    public function getBmEnable(){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_PENDING_ENABLE, $storeScope);
    }
	
	public function getTermsURL(){
		$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_GENERAL_TERMS_URL, $storeScope);
	}
	
    public function getPrivacyPolicyUrl()
    {
        $url = '';
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $pageId = $this->scopeConfig->getValue(self::XML_PATH_GENERAL_PRIVACY_POLICY_PAGE, $storeScope);
        if ($pageId > 0) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $url = $objectManager->create('Magento\Cms\Helper\Page')->getPageUrl($pageId);
        }
        return $url;
    }

	public function setBmPaymentMethod($method){
		switch ($method){
			case "1":
				$_SESSION['billmate_payment_method'] = 'billmate_invoice';
			break;
			case "4":
				$_SESSION['billmate_payment_method'] = 'billmate_partpay';
			break;
			case "8":
				$_SESSION['billmate_payment_method'] = 'billmate_card';
			break;
			case "16":
				$_SESSION['billmate_payment_method'] = 'billmate_bank';
			break;
			default:
				$_SESSION['billmate_payment_method'] = 'billmate_invoice';
			break;
		}
	}
	
    public function def()
    {
        if (!defined('BILLMATE_SERVER')) {
            define("BILLMATE_SERVER", "2.1.6");
        }
        if (!defined('BILLMATE_CLIENT')) {
            define("BILLMATE_CLIENT", $this->getClientVersion());
        }
        if (!defined('BILLMATE_LANGUAGE')) {
            define("BILLMATE_LANGUAGE", "sv");
        }
    }

    public function getClientVersion() {
         return "Magento:".$this->getMagentoVersion()." PLUGIN:0.10.2b";
    }

    public function getMagentoVersion() {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
        $version = $productMetadata->getVersion();
        return $version;
    }
}
