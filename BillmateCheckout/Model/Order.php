<?php
namespace Billmate\BillmateCheckout\Model;

class Order
{
    /**
     * Order constructor.
     *
     * @param \Magento\Store\Model\StoreManagerInterface                 $storeManager
     * @param \Magento\Customer\Model\CustomerFactory                    $customerFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface          $customerRepository
     * @param \Magento\Quote\Api\CartRepositoryInterface                 $cartRepositoryInterface
     * @param \Magento\Quote\Api\CartManagementInterface                 $cartManagementInterface
     * @param \Magento\Quote\Model\Quote\Address\Rate                    $shippingRate
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender        $orderSender
     * @param \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quoteCollectionFactory
     * @param \Billmate\BillmateCheckout\Helper\Data                     $dataHelper
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepositoryInterface,
        \Magento\Quote\Api\CartManagementInterface $cartManagementInterface,
        \Magento\Quote\Model\Quote\Address\Rate $shippingRate,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quoteCollectionFactory,
        \Billmate\BillmateCheckout\Helper\Data $dataHelper
    ){
        $this->_storeManager = $storeManager;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->cartRepositoryInterface = $cartRepositoryInterface;
        $this->cartManagementInterface = $cartManagementInterface;
        $this->shippingRate = $shippingRate;
        $this->orderSender = $orderSender;
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->dataHelper = $dataHelper;
    }

    /**
     * @param        $orderData
     * @param string $orderID
     * @param string $paymentID
     *
     * @return int
     */
    public function create($orderData, $orderID = '', $paymentID = '')
    {
        try {
            $this->dataHelper->addLog([
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'note' => 'aaa',
            ]);

            if ($orderID == '') {
                $orderID = $this->dataHelper->getQuote()->getReservedOrderId();
            }

            $this->dataHelper->addLog([
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'note' => 'aab',
                'orderID' => $orderID,
            ]);

            $exOrder = $this->dataHelper->getOrderByIncrementId($orderID);
            if ($exOrder->getIncrementId()){
                return;
            }

            $this->dataHelper->addLog([
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'note' => 'aac',
                'isset.session.billmate_applied_discount_code' => (bool)($this->dataHelper->getSessionData('billmate_applied_discount_code')),
                'isset.session.shipping_code' => (bool)($this->dataHelper->getSessionData('shipping_code')),
                'session.billmate_applied_discount_code' => ($this->dataHelper->getSessionData('billmate_applied_discount_code') ?
                    $this->dataHelper->getSessionData('billmate_applied_discount_code') : ''),
                'session.shipping_code' => (($this->dataHelper->getSessionData('shipping_code')) ? $this->dataHelper->getSessionData('shipping_code') : ''),
            ]);

            $shippingCode = $this->dataHelper->getSessionData('shipping_code');

            $actual_quote = $this->quoteCollectionFactory->create()
                ->addFieldToFilter("reserved_order_id", $orderID)->getFirstItem();
            $actual_quote_id = $actual_quote->getId();

            $this->dataHelper->addLog([
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'date' => date('Y-m-d H:i:s'),
                'note' => 'aad',
                'actual_quote_id' => $actual_quote_id,
            ]);

            $store = $this->_storeManager->getStore();

            $this->dataHelper->addLog([
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'date' => date('Y-m-d H:i:s'),
                'note' => 'aae',
            ]);

            $customer = $this->getCustomer($orderData);

            $actual_quote->setCustomerEmail($orderData['email']);
            $actual_quote->setStore($store);
            $actual_quote->setCurrency();
            $actual_quote->assignCustomer($customer);

            $this->dataHelper->addLog([
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'date' => date('Y-m-d H:i:s'),
                'note' => 'aaf assignCustomer to quote',

            ]);

            if ($this->dataHelper->getSessionData('billmate_applied_discount_code')) {
                $discountCode = $this->dataHelper->getSessionData('billmate_applied_discount_code');
                $actual_quote->setCouponCode($discountCode);
            }

            $billmateShippingAddress = $this->dataHelper->getSessionData('billmate_shipping_address');
            $billmateBillingAddress = $this->dataHelper->getSessionData('billmate_billing_address');

            $this->dataHelper->addLog([
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'date' => date('Y-m-d H:i:s'),
                'note' => 'aag',
                'isset.session.billmate_shipping_address' => (bool)$billmateShippingAddress,
                'isset.session.billmate_billing_address' => (bool)$billmateBillingAddress,
            ]);

            $actual_quote->getBillingAddress()->addData($billmateBillingAddress);

            if ($billmateShippingAddress){
                $actual_quote->getShippingAddress()->addData($billmateShippingAddress);
            } else {
                $actual_quote->getShippingAddress()->addData($billmateBillingAddress);
            }

            $this->dataHelper->addLog([
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'date' => date('Y-m-d H:i:s'),
                'note' => 'aah',
            ]);


            $this->shippingRate->setCode($shippingCode)->getPrice();
            $shippingAddress = $actual_quote->getShippingAddress();
            if (!$this->dataHelper->getSessionData('billmate_payment_method')) {
                $this->dataHelper->setBmPaymentMethod('default');
            }
            $billmatePaymentMethod = $this->dataHelper->getSessionData('billmate_payment_method');
            $shippingAddress->setCollectShippingRates(true)
                ->collectShippingRates()
                ->setShippingMethod($shippingCode); //shipping method
            $actual_quote->getShippingAddress()->addShippingRate($this->shippingRate);
            $actual_quote->setPaymentMethod($billmatePaymentMethod); //payment method
            $actual_quote->getPayment()->importData(['method' => $billmatePaymentMethod]);
            $actual_quote->setReservedOrderId($orderID);
            $actual_quote->collectTotals();
            $actual_quote->save();

            $this->dataHelper->addLog([
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'note' => 'aai',
            ]);

            $cart = $this->cartRepositoryInterface->get($actual_quote->getId());
            $cart->setCustomerEmail($orderData['email']);
            $cart->setCustomerId($customer->getId());
            $cart->getBillingAddress()->addData($billmateBillingAddress);
            if ($billmateShippingAddress){
                $cart->getShippingAddress()->addData($billmateShippingAddress);
            } else {
                $cart->getShippingAddress()->addData($billmateBillingAddress);
            }
            $cart->getBillingAddress()->setCustomerId($customer->getId());
            $cart->getShippingAddress()->setCustomerId($customer->getId());
            $cart->save();

            $this->dataHelper->addLog([
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'note' => 'aaj',
            ]);

            $cart->getBillingAddress()->setCustomerId($customer->getId());
            $cart->getShippingAddress()->setCustomerId($customer->getId());
            $cart->setCustomerId($customer->getId());
            $cart->assignCustomer($customer);
            $cart->save();

            $this->dataHelper->addLog([
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'note' => 'aak',
            ]);

            $orderId = $this->cartManagementInterface->placeOrder($cart->getId());

            $this->dataHelper->addLog([
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'note' => 'aal',
                'order_id' => $orderId,
            ]);

            $order = $this->dataHelper->getOrderById($orderId);

            $this->orderSender->send($order);

            $this->dataHelper->addLog([
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'note' => 'aam',
            ]);

            $this->dataHelper->setSessionData('bm-inc-id', $order->getIncrementId());

            $this->dataHelper->addLog([
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'note' => 'aan',
                'session.bm-inc-id' => $this->dataHelper->getSessionData('bm-inc-id'),
            ]);

            $orderState = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
            $order->setState($orderState)->setStatus($orderState);
            $order->save();
            $this->dataHelper->addLog([
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'note' => 'aao',
                'order_id' => $orderId,
            ]);

            return $orderId;
        }
        catch (\Exception $e){
            $this->dataHelper->addLog([
                'Could not create order',
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'exception.message' => $e->getMessage(),
                'exception.file' => $e->getFile(),
                'exception.line' => $e->getLine(),
            ]);
            return 0;
        }
    }

    /**
     * @param $orderData
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    protected function getCustomer($orderData)
    {
        $store = $this->_storeManager->getStore();
        $websiteId = $this->_storeManager->getStore()->getWebsiteId();

        $customer = $this->customerFactory->create();
        $customer->setWebsiteId($websiteId);
        $customer->loadByEmail($orderData['email']);

        $_password = str_pad($orderData['email'], 10, rand(111,999));
        if (!$customer->getEntityId()){
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

        return $this->customerRepository->getById($customer->getEntityId());
    }
}