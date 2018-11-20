<?php

namespace Billmate\BillmateCheckout\Helper;

class Iframe extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_storeManager;
    protected $shippingRate;
    protected $checkoutSession;
    protected $shippingMethodManagementInterface;
    protected $quoteManagement;
    protected $quote;
	protected $shippingPrice;
	protected $_cart;

    /**
     * @var \Billmate\BillmateCheckout\Helper\Data
     */
	protected $dataHelper;

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
     * Iframe constructor.
     *
     * @param \Magento\Framework\App\Helper\Context      $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Quote\Model\Quote\Address\Rate    $shippingRate
     * @param \Magento\Checkout\Model\Session            $_checkoutSession
     * @param Config                                     $configHelper
     * @param Data                                       $dataHelper
     * @param \Billmate\Billmate\Model\Billmate          $billmateProvider
     * @param \Magento\Tax\Model\CalculationFactory      $taxCalculation
     */
    public function __construct(
		\Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Quote\Model\Quote\Address\Rate $shippingRate,
		\Magento\Checkout\Model\Session $_checkoutSession,
        \Billmate\BillmateCheckout\Helper\Config $configHelper,
        \Billmate\BillmateCheckout\Helper\Data $dataHelper,
        \Billmate\Billmate\Model\Billmate $billmateProvider,
        \Magento\Tax\Model\CalculationFactory $taxCalculation
	){
        $this->_storeManager = $storeManager;
        $this->shippingRate = $shippingRate;
        $this->checkoutSession = $_checkoutSession;

        $this->logger = $context->getLogger();

        $this->billmateProvider = $billmateProvider;
        $this->configHelper = $configHelper;
        $this->dataHelper = $dataHelper;
        $this->taxCalculation = $taxCalculation;

        parent::__construct($context);
    }

    public function getIframeData($method='initCheckout')
    {

        $this->dataHelper->prepareCheckout();
        $quoteAddress = $this->dataHelper->getQuote()->getShippingAddress();
        $lShippingPrice = $quoteAddress->getShippingAmount();

        $this->shippingRate->setCode($quoteAddress->getShippingMethod());
        $this->shippingPrice = $lShippingPrice;

        $this->setSessionData('shippingPrice', $lShippingPrice);
        $this->setSessionData('shipping_code', $quoteAddress->getShippingMethod());
        $this->setSessionData('billmate_shipping_tax', $quoteAddress->getShippingTaxAmount());



        if (empty($this->getQuote()->getReservedOrderId())){
            $this->getQuote()->reserveOrderId()->save();
        }

        $data = $this->getRequestData();

		$currentStore = $this->_storeManager->getStore();
		$currentStoreId = $currentStore->getId();
        $taxCalculation = $this->getTaxCalculation();
        $request = $taxCalculation->getRateRequest(null, null, null, $currentStoreId);

		$taxAmount = 0;
		$discounts = array();
        $itemsVisible = $this->getQuote()->getAllVisibleItems();
        foreach ($itemsVisible as $item) {

			$product = $item->getProduct();
			$taxClassId = $product->getTaxClassId();
			$percent = $taxCalculation->getRate($request->setProductClassId($taxClassId));

            $prod = array(
                'quantity' => $item->getQty(),
                'artnr' => $item->getSku(),
                'title' => $item->getName(),
                'aprice' => $this->toCents($item->getPriceInclTax()),
                'taxrate' => $item->getTaxPercent(),
                'discount' => ($item->getDiscountPercent()),
                'withouttax' => $this->toCents($item->getRowTotal())
            );

			if (isset($discounts[$percent])) {
                $discounts[$percent] += $this->toCents($item->getRowTotal());
			} else {
				$discounts[$percent] = $this->toCents($item->getRowTotal());
			}
			$taxAmount = $taxAmount + ((($this->toCents($item->getRowTotal())) * (1+($percent/100))) - ($this->toCents($item->getRowTotal())));
			$taxAmount = round($taxAmount,0);
            $data['Articles'][] = $prod;
        }

        $subtotal = $this->toCents($this->getQuote()->getSubtotal());

        if (($this->getQuote()->getSubtotal()-$this->getQuote()->getSubtotalWithDiscount()) > 0){
            $totalDiscountAmount = ($this->getQuote()->getSubtotal()-$this->getQuote()->getSubtotalWithDiscount());
            foreach ($discounts as $key => $val){
                $discountPercent = ($val / $subtotal) * 100;
                $discountAmount = $discountPercent * $totalDiscountAmount;
                array_push($data['Articles'], array(
                    'quantity' => '1',
                    'artnr' => 'discount_' . $key . '%',
                    'title' => 'discount_' . $key . '%',
                    'aprice' => $discountAmount/(1+($key/100))*(0-1),
                    'taxrate' => $key,
                    'discount' => '0',
                    'withouttax' => $discountAmount/(1+($key/100))*(0-1)
                ));
            }
        }

        $shippingAddressTotal = $this->getQuote()->getShippingAddress();
        $shippingTaxRate = $this->getShippingTaxRate();
        $round = $shippingAddressTotal->getGrandTotal() -
            (
                $shippingAddressTotal->getSubtotal() + $shippingAddressTotal->getShippingAmount() +
                $shippingAddressTotal->getTaxAmount()
            );
        $data['Cart'] = [
            'Shipping' => [
                'withouttax' => $this->toCents($shippingAddressTotal->getShippingAmount()),
                'taxrate' => $shippingTaxRate,
                'withtax' => $this->toCents($shippingAddressTotal->getShippingInclTax()),
            ],
            'Total' => [
                'withouttax' => $this->toCents($shippingAddressTotal->getSubtotal() + $shippingAddressTotal->getShippingAmount()),
                'tax' => $this->toCents($shippingAddressTotal->getTaxAmount()),
                'rounding' => $round,
                'withtax' => $this->toCents($shippingAddressTotal->getGrandTotal()),
            ]
        ];

        $response = $this->billmateProvider->call(
            $method,
            $data
        );

        if (isset ($response['number'])) {
            $this->setSessionData('billmate_checkout_id', $response['number']);
        }

        return $response;
	}

    /**
     * @return string
     */
    public function updateIframe()
    {
        $response = $this->getIframeData('updateCheckout');

        if(isset($response['url'])) {
            return $response['url'];
        }
        return '';
    }

    /**
     * @return \Magento\Quote\Model\Quote
     */
    protected function getQuote()
    {
        return $this->dataHelper->getQuote();
    }

    /**
     * @return array
     */
    protected function getRequestData()
    {
        $data = [];
        $data['CheckoutData'] = [
            'windowmode' => 'iframe',
            'sendreciept' => 'yes',
            'terms' => $this->configHelper->getTermsURL(),
            'redirectOnSuccess'=>'true'
        ];
        $data['PaymentData'] = [
            'method' => '93',
            'currency' => 'SEK',
            'language' => 'sv',
            'country' => 'SE',
            'orderid' => $this->getQuote()->getReservedOrderId(),
            'callbackurl' => $this->_getUrl('billmatecheckout/callback/callback'),
            'accepturl' =>  $this->_getUrl('billmatecheckout/success/success/'),
            'cancelurl' =>  $this->_getUrl('billmatecheckout')
        ];

        if ($this->getSessionData('billmate_checkout_id')) {
            $data['PaymentData']['number'] = $this->getSessionData('billmate_checkout_id');
        }

       /* need to check this data */
       /**  $shippingAddressTotal = $this->getQuote()->getShippingAddress();
        $data['Articles'] = [
            [
                'quantity' => '1',
                'artnr' => 'shipping_code',
                'title' => $shippingAddressTotal->getShippingMethod(),
                'aprice' => $this->toCents($shippingAddressTotal->getShippingInclTax()),
                'taxrate' => $this->getShippingTaxRate(),
                'discount' => $this->toCents($shippingAddressTotal->getShippingDiscountAmount()),
                'withouttax' => $this->toCents($shippingAddressTotal->getShippingAmount())
            ]
        ];*/
        $data['Cart'] = [];

        if ($this->getSessionData('billmate_applied_discount_code')) {
            $data['Articles'][] = [
                'quantity' => '1',
                'artnr' => 'discount_code',
                'title' => $this->getSessionData('billmate_applied_discount_code'),
                'aprice' => '0',
                'taxrate' => '0',
                'discount' => '0',
                'withouttax' => '0'
            ];
        }

        return $data;
    }

    /**
     * @param $key
     * @param $value
     *
     * @return \Magento\Checkout\Model\Session
     */
    protected function setSessionData($key, $value)
    {
        return $this->dataHelper->setSessionData($key, $value);
    }

    /**
     * @param $key
     * @param $value
     *
     * @return mixed
     */
    protected function getSessionData($key)
    {
        return $this->dataHelper->getSessionData($key);
    }

    /**
     * @param $price
     *
     * @return int
     */
    protected function toCents($price)
    {
        return $this->dataHelper->priceToCents($price);
    }

    /**
     * @return  \Magento\Tax\Model\Calculation
     */
    protected function getTaxCalculation()
    {
        return $this->taxCalculation->create();
    }

    /**
     * @return float
     */
    protected function getShippingTaxRate()
    {
        $currentStore = $this->_storeManager->getStore();
        $currentStoreId = $currentStore->getId();
        $taxCalculation = $this->getTaxCalculation();
        $request = $taxCalculation->getRateRequest(null, null, null, $currentStoreId);
        $shippingTaxClass = $this->configHelper->getShippingTaxClass();
        return $taxCalculation->getRate($request->setProductClassId($shippingTaxClass));
    }

}
