<?php

namespace Billmate\BillmateCheckout\Helper;

use Magento\Framework\App\ProductMetadataInterface;

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

    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @var array
     */
    protected $defaultAddress = [
        'firstname' => 'Testperson',
        'lastname' => 'Approved',
        'street' => 'Teststreet',
        'city' => 'Testcity',
        'country_id' => 'SE',
        'postcode' => '12345',
        'telephone' => '0700123456'
    ];

    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $_quote = false;

    const XML_PATH_GENERAL_ENABLE = 'billmate_billmatecheckout/general/enable';
    const XML_PATH_GENERAL_PUSHORDEREVENTS = 'billmate_billmatecheckout/general/pushorderevents';
    const XML_PATH_GENERAL_CUSTOMCSS = 'billmate_billmatecheckout/general/customcss';
    const XML_PATH_GENERAL_BTN = 'billmate_billmatecheckout/general/inc_dec_btns';
	const XML_PATH_GENERAL_ATTRIBUTES = 'billmate_billmatecheckout/general/show_attributes_cart';
	const XML_PATH_GENERAL_TERMS_URL = 'billmate_billmatecheckout/general/terms_url';
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
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        ProductMetadataInterface $metaData
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
        $this->logger = $context->getLogger();
        $this->metaData = $metaData;
        $this->layoutFactory = $layoutFactory;

        parent::__construct($context);
    }


    public function prepareCheckout()
    {
        /*if (!$this->getQuote()->getShippingAddress()->getShippingMethod()) {*/
            $this->getQuote()->getBillingAddress()->addData($this->getAddress());
            $shippingAddress = $this->getQuote()->getShippingAddress()->addData($this->getAddress());
            $shippingAddress->setCollectShippingRates(true)
                ->collectShippingRates()
            ->setShippingMethod('freeshipping_freeshipping');
        /*}*/
        return $this;
    }

    /**
     * @return string
     */
    public function getCart()
    {
        $layout = $this->layoutFactory->create();
        return $layout->createBlock('Billmate\BillmateCheckout\Block\Cart\Content')
            ->setTemplate('cart/content.phtml')->toHtml();
    }
	
	public function setShippingAddress($input){

		$input['firstname'] = str_replace('Ã…','Å',$input['firstname']);
		$input['lastname'] = str_replace('Ã…','Å',$input['lastname']);
		$input['street'] = str_replace('Ã…','Å',$input['street']);
		$input['city'] = str_replace('Ã…','Å',$input['city']);

		$input['firstname'] = str_replace('Ã„','Ä',$input['firstname']);
		$input['lastname'] = str_replace('Ã„','Ä',$input['lastname']);
		$input['street'] = str_replace('Ã„','Ä',$input['street']);
		$input['city'] = str_replace('Ã„','Ä',$input['city']);

		$input['firstname'] = str_replace('Ã–','Ö',$input['firstname']);
		$input['lastname'] = str_replace('Ã–','Ö',$input['lastname']);
		$input['street'] = str_replace('Ã–','Ö',$input['street']);
		$input['city'] = str_replace('Ã–','Ö',$input['city']);

		$input['firstname'] = str_replace('Ã¥','å',$input['firstname']);
		$input['lastname'] = str_replace('Ã¥','å',$input['lastname']);
		$input['street'] = str_replace('Ã¥','å',$input['street']);
		$input['city'] = str_replace('Ã¥','å',$input['city']);

		$input['firstname'] = str_replace('Ã¤','ä',$input['firstname']);
		$input['lastname'] = str_replace('Ã¤','ä',$input['lastname']);
		$input['street'] = str_replace('Ã¤','ä',$input['street']);
		$input['city'] = str_replace('Ã¤','ä',$input['city']);

		$input['firstname'] = str_replace('Ã¶','ö',$input['firstname']);
		$input['lastname'] = str_replace('Ã¶','ö',$input['lastname']);
		$input['street'] = str_replace('Ã¶','ö',$input['street']);
		$input['city'] = str_replace('Ã¶','ö',$input['city']);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
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
	
	public function setBillingAddress($input){

		$input['firstname'] = str_replace('Ã…','Å',$input['firstname']);
		$input['lastname'] = str_replace('Ã…','Å',$input['lastname']);
		$input['street'] = str_replace('Ã…','Å',$input['street']);
		$input['city'] = str_replace('Ã…','Å',$input['city']);

		$input['firstname'] = str_replace('Ã„','Ä',$input['firstname']);
		$input['lastname'] = str_replace('Ã„','Ä',$input['lastname']);
		$input['street'] = str_replace('Ã„','Ä',$input['street']);
		$input['city'] = str_replace('Ã„','Ä',$input['city']);

		$input['firstname'] = str_replace('Ã–','Ö',$input['firstname']);
		$input['lastname'] = str_replace('Ã–','Ö',$input['lastname']);
		$input['street'] = str_replace('Ã–','Ö',$input['street']);
		$input['city'] = str_replace('Ã–','Ö',$input['city']);

		$input['firstname'] = str_replace('Ã¥','å',$input['firstname']);
		$input['lastname'] = str_replace('Ã¥','å',$input['lastname']);
		$input['street'] = str_replace('Ã¥','å',$input['street']);
		$input['city'] = str_replace('Ã¥','å',$input['city']);

		$input['firstname'] = str_replace('Ã¤','ä',$input['firstname']);
		$input['lastname'] = str_replace('Ã¤','ä',$input['lastname']);
		$input['street'] = str_replace('Ã¤','ä',$input['street']);
		$input['city'] = str_replace('Ã¤','ä',$input['city']);

		$input['firstname'] = str_replace('Ã¶','ö',$input['firstname']);
		$input['lastname'] = str_replace('Ã¶','ö',$input['lastname']);
		$input['street'] = str_replace('Ã¶','ö',$input['street']);
		$input['city'] = str_replace('Ã¶','ö',$input['city']);

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

    /**
     * @param $methodInput
     */
    public function setShippingMethod($methodInput)
    {
		$shippingAddress = $this->getQuote()->getShippingAddress();
        $shippingAddress->setShippingMethod($methodInput);
		$shippingAddress->setCollectShippingRates(true)
            ->collectShippingRates();
		$this->getQuote()->collectTotals();
    }

    /**
     * @param $code
     */
    public function setDiscountCode($code)
    {
	    $this->getQuote()
            ->setCouponCode($code)
            ->collectTotals()
            ->save();
	    $_SESSION['billmate_applied_discount_code'] = $code;
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

            $this->logger->error(print_r(array(
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'date' => date('Y-m-d H:i:s'),
                'note' => 'aaa',
                '' => ''
            ), true));

			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			if ($orderID == ''){
				$orderID = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Checkout\Model\Cart')->getQuote()->getReservedOrderId();
			}

            $this->logger->error(print_r(array(
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'date' => date('Y-m-d H:i:s'),
                'note' => 'aab',
                'orderID' => $orderID,
                '' => ''
            ), true));

			$exOrder = $this->orderInterface->loadByIncrementId($orderID);
			if ($exOrder->getIncrementId()){
				return;
			}
			else{
			}
			if (isset($_SESSION['billmate_applied_discount_code'])){
				$discountCode = $_SESSION['billmate_applied_discount_code'];
			}

            $this->logger->error(print_r(array(
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'date' => date('Y-m-d H:i:s'),
                'note' => 'aac',
                'isset.session.billmate_applied_discount_code' => (isset($_SESSION['billmate_applied_discount_code'])),
                'isset.session.shipping_code' => (isset($_SESSION['shipping_code'])),
                'session.billmate_applied_discount_code' => ((isset($_SESSION['billmate_applied_discount_code'])) ? $_SESSION['billmate_applied_discount_code'] : ''),
                'session.shipping_code' => ((isset($_SESSION['shipping_code'])) ? $_SESSION['shipping_code'] : ''),
                '' => ''
            ), true));

			$shippingCode = $_SESSION['shipping_code'];
			
			$actual_quote = $this->quoteCollectionFactory->create()->addFieldToFilter("reserved_order_id", $orderID)->getFirstItem();
			
			$actual_quote_id = $actual_quote->getId();
			
            $this->logger->error(print_r(array(
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'date' => date('Y-m-d H:i:s'),
                'note' => 'aad',
                'actual_quote_id' => $actual_quote_id,
                '' => ''
            ), true));
			
			//init the store id and website id @todo pass from array
			$store = $this->_storeManager->getStore();
			$websiteId = $this->_storeManager->getStore()->getWebsiteId();

            $this->logger->error(print_r(array(
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'date' => date('Y-m-d H:i:s'),
                'note' => 'aae',
                '' => ''
            ), true));

			//init the customer
			$customer = $this->customerFactory->create();
			$customer->setWebsiteId($websiteId);
			$customer->loadByEmail($orderData['email']); // load customet by email address
			//check the customer
            $_password = str_pad($orderData['email'], 10, rand(111,999));
			if (!$customer->getEntityId()){
				//If not avilable then create this customer
				$customer->setWebsiteId($websiteId)
						->setStore($store)
						->setFirstname($orderData['shipping_address']['firstname'])
						->setLastname($orderData['shipping_address']['lastname'])
						->setEmail($orderData['email'])
						->setPassword($_password);
				$customer->save();
			}
			$customer->setEmail($orderData['email']);
			$customer->save();
			$actual_quote->setCustomerEmail($orderData['email']);
			
			$actual_quote->setStore($store);
			$customer = $this->customerRepository->getById($customer->getEntityId());
			$actual_quote->setCurrency();
			$actual_quote->assignCustomer($customer);

            $this->logger->error(print_r(array(
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'date' => date('Y-m-d H:i:s'),
                'note' => 'aaf assignCustomer to quote',
                '' => ''
            ), true));

			if (isset($_SESSION['billmate_applied_discount_code'])){
				$actual_quote->setCouponCode($discountCode);
			}

            $this->logger->error(print_r(array(
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'date' => date('Y-m-d H:i:s'),
                'note' => 'aag',
                'isset.session.billmate_shipping_address' => (isset($_SESSION['billmate_shipping_address'])),
                'isset.session.billmate_billing_address' => (isset($_SESSION['billmate_billing_address'])),
                '' => ''
            ), true));

			//Set Address to quote @todo add section in order data for seperate billing and handle it
			$actual_quote->getBillingAddress()->addData($_SESSION['billmate_billing_address']);
			if (isset($_SESSION['billmate_shipping_address'])){
				$actual_quote->getShippingAddress()->addData($_SESSION['billmate_shipping_address']);
			}
			else {
				$actual_quote->getShippingAddress()->addData($_SESSION['billmate_billing_address']);
			}

            $this->logger->error(print_r(array(
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'date' => date('Y-m-d H:i:s'),
                'note' => 'aah',
                '' => ''
            ), true));

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

            $this->logger->error(print_r(array(
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'date' => date('Y-m-d H:i:s'),
                'note' => 'aai',
                '' => ''
            ), true));

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

            $this->logger->error(print_r(array(
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'date' => date('Y-m-d H:i:s'),
                'note' => 'aaj',
                '' => ''
            ), true));

			$cart->getBillingAddress()->setCustomerId($customer->getId());
			$cart->getShippingAddress()->setCustomerId($customer->getId());
			$cart->setCustomerId($customer->getId());
			$cart->assignCustomer($customer);
			$cart->save();

            $this->logger->error(print_r(array(
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'date' => date('Y-m-d H:i:s'),
                'note' => 'aak',
                '' => ''
            ), true));

			$order_id = $this->cartManagementInterface->placeOrder($cart->getId());

            $this->logger->error(print_r(array(
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'date' => date('Y-m-d H:i:s'),
                'note' => 'aal',
                'order_id' => $order_id,
                '' => ''
            ), true));

			$order = $objectManager->create('\Magento\Sales\Model\Order')->load($order_id);
			$emailSender = $objectManager->create('\Magento\Sales\Model\Order\Email\Sender\OrderSender');
			$emailSender->send($order);

            $this->logger->error(print_r(array(
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'date' => date('Y-m-d H:i:s'),
                'note' => 'aam',
                '' => ''
            ), true));

			$_SESSION['bm-inc-id'] = $order->getIncrementId();
			
            $this->logger->error(print_r(array(
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'date' => date('Y-m-d H:i:s'),
                'note' => 'aan',
                'session.bm-inc-id' => $_SESSION['bm-inc-id'],
                '' => ''
            ), true));

			$orderState = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
			$order->setState($orderState)->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
			$order->save();

            $this->logger->error(print_r(array(
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'date' => date('Y-m-d H:i:s'),
                'note' => 'aao',
                'order_id' => $order_id,
                '' => ''
            ), true));
			
			return $order_id;
		}
		catch (\Exception $e){
            $this->logger->error(print_r(array(
                'Could not create order',
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'exception.message' => $e->getMessage(),
                'exception.file' => $e->getFile(),
                'exception.line' => $e->getLine(),
                '' => ''
            ), true));
            return 0;
		}
    }
	
	public function clearSession(){
		$this->checkoutSession->clearStorage();
		$this->checkoutSession->clearQuote();
		$_SESSION['shippingPrice'] = null;
		$_SESSION['shipping_code'] = null;
		$_SESSION['billmate_shipping_tax'] = null;
		$_SESSION['billmate_shipping_address'] = null;
		$_SESSION['billmate_billing_address'] = null;
		$_SESSION['billmate_email'] = null;
		$_SESSION['billmate_applied_discount_code'] = null;
		$_SESSION['billmate_checkout_id'] = null;
		$_SESSION['billmate_payment_method'] = null;
		session_unset();
	}

    public function getEnable()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_GENERAL_ENABLE, $storeScope);
    }

    public function getBtnEnable()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_GENERAL_BTN, $storeScope);
    }

    public function getBillmateId()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_CREDENTIALS_ID, $storeScope);
    }

    public function getBillmateSecret()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_CREDENTIALS_KEY, $storeScope);
    }

    public function getPushEvents()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_GENERAL_PUSHORDEREVENTS, $storeScope);
    }

    public function getCustomCss()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_GENERAL_CUSTOMCSS, $storeScope);
    }

    public function getTestMode()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_GENERAL_TESTMODE, $storeScope);
    }

    public function getFetch()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_PENDING_FETCH, $storeScope);
    }

    public function getMultiSelect()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_PENDING_MULTISELECT, $storeScope);
    }

    public function getPendingControl()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_PENDING_PENDING_CONTROL, $storeScope);
    }

    public function getDeny()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_PENDING_DENY, $storeScope);
    }

    public function getActivated(){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_PENDING_ACTIVATED, $storeScope);
    }
	
	public function getShippingTaxClass()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue('tax/classes/shipping_tax_class', $storeScope);
    }

    public function getCanceled()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_PENDING_CANCELED, $storeScope);
    }

    public function getBmEnable()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_PENDING_ENABLE, $storeScope);
    }
	
	public function getShowAttribute()
    {
		$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_GENERAL_ATTRIBUTES, $storeScope);
	}

	public function setBmPaymentMethod($methodCode){

        /*$methods = [
            1 =>  'billmate_invoice',
            4 =>  'billmate_partpay',
            8 =>  'billmate_card',
            16 =>  'billmate_bank',
        ];*/
		switch ($methodCode) {
			case "1":
                $method = 'billmate_invoice';
			break;
			case "4":
                $method = 'billmate_partpay';
			break;
			case "8":
                $method = 'billmate_card';
			break;
			case "16":
                $method = 'billmate_bank';
			break;
			default:
				$method = 'billmate_invoice';
			break;
		}

        $_SESSION['billmate_payment_method'] = $method;
	}
	
	public function def(){
		define("BILLMATE_SERVER", "2.1.7");
		define("BILLMATE_CLIENT", $this->getClientVersion());
		define("BILLMATE_LANGUAGE", "sv");
	}

    /**
     * @return string
     */
    public function getClientVersion()
    {
        return "Magento:".$this->getMagentoVersion()." PLUGIN:0.9.3b";
    }

    /**
     * @return string
     */
    public function getMagentoVersion()
    {
        $version = $this->metaData->getVersion();
        return $version;
    }

    /**
     * @param $price
     *
     * @return mixed
     */
    public function priceToCents($price)
    {
        return $price * 100;
    }

    /**
     * @return \Magento\Quote\Model\Quote\Item[]
     */
    public function getItems()
    {
       return $this->getQuote()->getAllVisibleItems();
    }

    /**
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        if (!$this->_quote) {
            $this->_quote = $this->_getCheckoutSession()->getQuote();
        }

        return $this->_quote;
    }

    /**
     * @return array
     */
    public function getShippingMethodsRates()
    {
        $shippingAddress = $this->getQuote()->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true)->collectShippingRates();
        return $shippingAddress->getAllShippingRates();
    }

    /**
     * @param $key
     * @param $value
     *
     * @return \Magento\Checkout\Model\Session
     */
    public function setSessionData($key, $value)
    {
        return $this->_getCheckoutSession()->setData($key, $value);
    }

    /**
     * @param $key
     * @param $value
     *
     * @return mixed
     */
    public function getSessionData($key)
    {
        return $this->_getCheckoutSession()->getData($key);
    }

    /**
     * @return \Magento\Checkout\Model\Session
     */
    protected function _getCheckoutSession()
    {
        return $this->checkoutSession;
    }

    /**
     * @param $methodCode
     *
     * @return bool
     */
    public function isActiveShippingMethod($methodCode)
    {
        $activeMethod = $this->getQuote()
            ->getShippingAddress()
            ->getShippingMethod();
        return  $activeMethod == $methodCode;
    }

    /**
     * @return array
     */
    protected function getAddress()
    {
        return $this->defaultAddress;
    }
}
