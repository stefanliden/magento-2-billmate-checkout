<?php

namespace Billmate\BillmateCheckout\Helper;

use Magento\Framework\App\ProductMetadataInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    const PLUGIN_VERSION = '0.11.0b';
    const BM_PENDING_STATUS = 'pending';
    const BM_DENY_STATUS = 'canceled';
    const BM_APPROVE_STATUS = 'processing';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $cartRepositoryInterface;

    /**
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    protected $cartManagementInterface;

    /**
     * @var \Magento\Quote\Model\Quote\Address\Rate
     */
    protected $shippingRate;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory
     */
    protected $quoteCollectionFactory;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resource;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quote;


    /**
     * @var \Magento\Quote\Model\Quote\TotalsCollector
     */
    protected $totalsCollector;

    protected $orderSender;

    protected $orderInterface;

	protected $shippingPrice;

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

    /**
     * Data constructor.
     *
     * @param \Magento\Framework\App\Helper\Context                      $context
     * @param \Magento\Store\Model\StoreManagerInterface                 $storeManager
     * @param \Magento\Customer\Model\CustomerFactory                    $customerFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface          $customerRepository
     * @param \Magento\Quote\Api\CartRepositoryInterface                 $cartRepositoryInterface
     * @param \Magento\Quote\Api\CartManagementInterface                 $cartManagementInterface
     * @param \Magento\Quote\Model\Quote\Address\Rate                    $shippingRate
     * @param \Magento\Sales\Api\Data\OrderInterface                     $order
     * @param \Magento\Framework\App\ResourceConnection                  $resource
     * @param \Magento\Checkout\Model\Session                            $_checkoutSession
     * @param \Magento\Quote\Model\QuoteFactory                          $quote
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender        $orderSender
     * @param \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quoteCollectionFactory
     * @param \Magento\Framework\View\LayoutFactory                      $layoutFactory
     * @param ProductMetadataInterface                                   $metaData
     * @param \Magento\Quote\Model\Quote\TotalsCollector                 $totalsCollector
     */
    public function __construct(
		\Magento\Framework\App\Helper\Context $context, 
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Customer\Model\CustomerFactory $customerFactory, 
		\Magento\Customer\Api\CustomerRepositoryInterface $customerRepository, 
		\Magento\Quote\Api\CartRepositoryInterface $cartRepositoryInterface, 
		\Magento\Quote\Api\CartManagementInterface $cartManagementInterface, 
		\Magento\Quote\Model\Quote\Address\Rate $shippingRate,
		\Magento\Sales\Api\Data\OrderInterface $order, 
		\Magento\Framework\App\ResourceConnection $resource, 
		\Magento\Checkout\Model\Session $_checkoutSession,
		\Magento\Quote\Model\QuoteFactory $quote,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
		\Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quoteCollectionFactory,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        ProductMetadataInterface $metaData,
        \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector
	){
        $this->orderInterface = $order;
        $this->_storeManager = $storeManager;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->cartRepositoryInterface = $cartRepositoryInterface;
        $this->cartManagementInterface = $cartManagementInterface;
        $this->shippingRate = $shippingRate;
        $this->resource = $resource;
        $this->checkoutSession = $_checkoutSession;
        $this->quote = $quote;
        $this->orderSender = $orderSender;
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->logger = $context->getLogger();
        $this->metaData = $metaData;
        $this->layoutFactory = $layoutFactory;
        $this->totalsCollector = $totalsCollector;

        parent::__construct($context);
    }


    public function prepareCheckout()
    {
        if (!$this->getQuote()->getShippingAddress()->getShippingMethod()) {
            $this->getQuote()->getBillingAddress()->addData($this->getAddress());
            $shippingAddress = $this->getQuote()->getShippingAddress()->addData($this->getAddress());
            $this->getQuote()->setShippingAddress($shippingAddress);
            $shippingAddress->save();
            $shippingAddress->setCollectShippingRates(true)->collectShippingRates();
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getCartContent()
    {
        $layout = $this->layoutFactory->create();
        return $layout->createBlock('Billmate\BillmateCheckout\Block\Cart\Content')
            ->setTemplate('cart/content.phtml')->toHtml();
    }

    /**
     * @param $row
     *
     * @return mixed
     */
    public function correctSymbols($row)
    {
        $replaceFrom = ['Ã…','Ã„','Ã–','Ã¥','Ã¤','Ã¶'];
        $replaceTo = ['Å','Ä','Ö','å','ä','ö'];
        return str_replace($replaceFrom,$replaceTo,$row);
    }

    /**
     * @param $input
     */
	public function setShippingAddress($shippingData)
    {
        $shippingData = $this->prepareShippingData($shippingData);
        $quote = $this->getQuote();
        $billmateTelephone = $this->getSessionData('billmate_telephone');
        $shippingAddress = [
            'firstname' => $shippingData['firstname'],
            'lastname' => $shippingData['lastname'],
            'street' => $shippingData['street'],
            'city' => $shippingData['city'],
            'country_id' => $shippingData['country_id'],
            'postcode' => $shippingData['postcode'],
        ];
        if (isset($shippingData['telephone']) || $billmateTelephone) {
            $shippingAddress['telephone'] = $billmateTelephone?$billmateTelephone:$shippingData['telephone'];
        }

        $quote->getShippingAddress()->addData($shippingAddress);
        $this->setSessionData('billmate_shipping_address',$shippingAddress);
	}

    /**
     * @param $shippingData
     */
	public function setBillingAddress($shippingData)
    {
        $shippingData = $this->prepareShippingData($shippingData);
        $billingAddress = [
            'firstname' => $shippingData['firstname'],
            'lastname' => $shippingData['lastname'],
            'street' => $shippingData['street'],
            'city' => $shippingData['city'],
            'country_id' => $shippingData['country_id'],
            'postcode' => $shippingData['postcode'],
            'telephone' => $shippingData['telephone']
        ];

        $this->getQuote()->getBillingAddress()->addData($billingAddress);
        $this->setSessionData('billmate_billing_address', $billingAddress);
        $this->setSessionData('billmate_telephone', $shippingData['telephone']);
        $this->setSessionData('billmate_email', $shippingData['email']);

		if ($this->getSessionData('billmate_shipping_address')) {
			$this->setShippingAddress($shippingData);
		}
	}

    /**
     * @param $shippingData
     *
     * @return array
     */
	protected function prepareShippingData($shippingData)
    {
        $shippingData['firstname'] = $this->correctSymbols($shippingData['firstname']);
        $shippingData['lastname'] = $this->correctSymbols($shippingData['lastname']);
        $shippingData['street'] = $this->correctSymbols($shippingData['street']);
        $shippingData['city'] = $this->correctSymbols($shippingData['city']);

        return $shippingData;
    }

    /**
     * @param $methodInput
     */
    public function setShippingMethod($methodInput)
    {
        $this->prepareCheckout();
		$shippingAddress = $this->getQuote()->getShippingAddress();
        $shippingAddress->setShippingMethod($methodInput);
		$shippingAddress->collectShippingRates();
        $shippingAddress->save();
		$this->getQuote()->collectTotals();
        $this->getQuote()->save();
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
        $this->setSessionData('billmate_applied_discount_code', $code);
    }

    public function createOrder($orderData, $orderID = '', $paymentID = '')
    {
		try {
            $this->addLog([
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'note' => 'aaa',
            ]);

			if ($orderID == '') {
				$orderID = $this->getQuote()->getReservedOrderId();
			}

            $this->addLog([
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'note' => 'aab',
                'orderID' => $orderID,
            ]);

            $exOrder = $this->getOrderByIncrementId($orderID);
			if ($exOrder->getIncrementId()){
				return;
			}

            $this->addLog([
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'note' => 'aac',
                'isset.session.billmate_applied_discount_code' => (bool)($this->getSessionData('billmate_applied_discount_code')),
                'isset.session.shipping_code' => (bool)($this->getSessionData('shipping_code')),
                'session.billmate_applied_discount_code' => ($this->getSessionData('billmate_applied_discount_code') ?
                    $this->getSessionData('billmate_applied_discount_code') : ''),
                'session.shipping_code' => (($this->getSessionData('shipping_code')) ? $this->getSessionData('shipping_code') : ''),
            ]);

			$shippingCode = $this->getSessionData('shipping_code');
			
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

            ), true));

			$store = $this->_storeManager->getStore();
			$websiteId = $this->_storeManager->getStore()->getWebsiteId();

            $this->logger->error(print_r(array(
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'date' => date('Y-m-d H:i:s'),
                'note' => 'aae',

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

            $this->addLog([
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'date' => date('Y-m-d H:i:s'),
                'note' => 'aaf assignCustomer to quote',

            ]);

            if ($this->getSessionData('billmate_applied_discount_code')) {
                $discountCode = $this->getSessionData('billmate_applied_discount_code');
                $actual_quote->setCouponCode($discountCode);
            }

           $billmateShippingAddress = $this->getSessionData('billmate_shipping_address');
           $billmateBillingAddress = $this->getSessionData('billmate_billing_address');

            $this->logger->error(print_r(array(
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'date' => date('Y-m-d H:i:s'),
                'note' => 'aag',
                'isset.session.billmate_shipping_address' => (bool)$billmateShippingAddress,
                'isset.session.billmate_billing_address' => (bool)$billmateBillingAddress,

            ), true));

			//Set Address to quote @todo add section in order data for seperate billing and handle it
			$actual_quote->getBillingAddress()->addData($billmateBillingAddress);
			if ($billmateShippingAddress){
				$actual_quote->getShippingAddress()->addData($billmateShippingAddress);
			} else {
				$actual_quote->getShippingAddress()->addData($billmateBillingAddress);
			}

            $this->logger->error(print_r(array(
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'date' => date('Y-m-d H:i:s'),
                'note' => 'aah',

            ), true));

			// Collect Rates and Set Shipping & Payment Method
			$this->shippingRate->setCode($shippingCode)->getPrice();
			$shippingAddress = $actual_quote->getShippingAddress();
            if (!$this->getSessionData('billmate_payment_method')) {
                $this->setBmPaymentMethod('default');
            }
            $billmatePaymentMethod = $this->getSessionData('billmate_payment_method') ;
			$shippingAddress->setCollectShippingRates(true)
					->collectShippingRates()
					->setShippingMethod($shippingCode); //shipping method
			$actual_quote->getShippingAddress()->addShippingRate($this->shippingRate);
			$actual_quote->setPaymentMethod($billmatePaymentMethod); //payment method
			$actual_quote->getPayment()->importData(['method' => $billmatePaymentMethod]);
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

            ), true));

			$cart = $this->cartRepositoryInterface->get($actual_quote->getId());
			$cart->setCustomerEmail($orderData['email']);
			$cart->setCustomerId($customer->getId());
			$cart->getBillingAddress()->addData($billmateBillingAddress);
			if ($billmateShippingAddress){
				$cart->getShippingAddress()->addData($billmateShippingAddress);
			}
			else {
				$cart->getShippingAddress()->addData($billmateBillingAddress);
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

            ), true));

			$orderId = $this->cartManagementInterface->placeOrder($cart->getId());

            $this->logger->error(print_r(array(
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'date' => date('Y-m-d H:i:s'),
                'note' => 'aal',
                'order_id' => $orderId,

            ), true));

            $order = $this->getOrderById($orderId);

			$this->orderSender->send($order);

            $this->addLog([
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'note' => 'aam',
            ]);

            $this->setSessionData('bm-inc-id', $order->getIncrementId());
			
            $this->addLog([
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'note' => 'aan',
                'session.bm-inc-id' => $this->getSessionData('bm-inc-id'),
            ]);

			$orderState = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
			$order->setState($orderState)->setStatus($orderState);
			$order->save();

            $this->logger->error(print_r(array(
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'date' => date('Y-m-d H:i:s'),
                'note' => 'aao',
                'order_id' => $orderId,
            ), true));
			
			return $orderId;
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
            ), true));
            return 0;
		}
    }

	public function clearSession()
    {
		$this->checkoutSession->clearStorage();
		$this->checkoutSession->clearQuote();
        $this->setSessionData('shippingPrice', null);
        $this->setSessionData('shipping_code', null);
        $this->setSessionData('billmate_shipping_tax', null);
        $this->setSessionData('billmate_shipping_address', null);
        $this->setSessionData('billmate_billing_address', null);
        $this->setSessionData('billmate_email', null);
        $this->setSessionData('billmate_applied_discount_code', null);
        $this->setSessionData('billmate_checkout_id', null);
        $this->setSessionData('billmate_payment_method', null);
        $this->setSessionData('bm-inc-id',null);

		session_unset();
	}

    /**
     * @param $methodCode int
     */
	public function setBmPaymentMethod($methodCode)
    {
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

        $this->setSessionData('billmate_payment_method', $method);
	}

    /**
     * @return string
     */
    public function getClientVersion()
    {
        return "Magento:".$this->getMagentoVersion()." PLUGIN:" . $this->getPluginVersion();
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
     * @return string
     */
    public function getPluginVersion()
    {
        return self::PLUGIN_VERSION;
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
        $this->prepareCheckout();
        $methods = [];
        $quote = $this->getQuote();
        $shippingAddress = $this->getQuote()->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true);

        $this->totalsCollector->collectAddressTotals($quote, $shippingAddress);
        $shippingRates = $shippingAddress->getGroupedAllShippingRates();
        foreach ($shippingRates as $carrierRates) {
            foreach ($carrierRates as $rate) {
                $methods[] = $rate;
            }
        }

        if (!$shippingAddress->getShippingMethod() && $methods) {
            $rate = current($methods);
            $this->setShippingMethod($rate->getCode());
        }

        return $methods;

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

    /**
     * @param $data
     */
    public function addLog($data)
    {
        if (!is_array($data)) {
            $data = ['data' => $data];
        }

        $logData = [
            'date' => date('Y-m-d H:i:s'),
        ];
        $logData = $logData + $data;
        $this->logger->error(print_r($logData, true));
    }

    /**
     * @param $type
     *
     * @return \Magento\Quote\Model\Quote\Address\Total
     */
    public function getTotalRow($type)
    {
        $this->getQuote()->getShippingAddress()->getData();
        $totals = $this->getQuote()->getTotals();
        if(isset($totals[$type])) {
            return $totals[$type];
        }
        return current($totals);
    }

    /**
     * @param $orderId
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrderByIncrementId($orderIncId)
    {
        return $this->orderInterface->loadByIncrementId($orderIncId);
    }

    /**
     * @param $orderId
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrderById($orderId)
    {
        return $this->orderInterface->load($orderId);
    }


    /**
     * @return mixed
     */
    public function getPendingStatus()
    {
        return self::BM_PENDING_STATUS;
    }

    /**
     * @return mixed
     */
    public function getDenyStatus()
    {
        return self::BM_DENY_STATUS;
    }

    /**
     * @return mixed
     */
    public function getApproveStatus()
    {
        return self::BM_APPROVE_STATUS;
    }
}
