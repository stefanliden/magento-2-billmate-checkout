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

    public function getIframe($method='initCheckout')
    {
		$this->getQuote()->getBillingAddress()->addData($this->getAddress());
        $shippingAddress = $this->getQuote()->getShippingAddress()->addData($this->getAddress());

		$shippingAddress->setCollectShippingRates(true)
            ->collectShippingRates()
            ->setShippingMethod('freeshipping_freeshipping');


		$methods = $shippingAddress->getGroupedAllShippingRates();
        $rate = current(current($methods));

        $lShippingPrice = $rate->getPrice();

        $this->shippingRate->setCode($rate->getCode());
        $this->shippingPrice = $lShippingPrice;

        $this->setSessionData('shippingPrice', $lShippingPrice);
        $this->setSessionData('shipping_code', $rate->getCode());
        $this->setSessionData('billmate_shipping_tax', $rate->getShippingTaxAmount());



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
                'aprice' => $this->toCents($item->getPrice()),
                'taxrate' => $percent,
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
        $sumex = $this->getQuote()->getSubtotal();
        $sum = $this->getQuote()->getSubtotal();

		$shippingTaxClass = $this->configHelper->getShippingTaxClass();
		$shippingTax = $taxCalculation->getRate($request->setProductClassId($shippingTaxClass));
        $shippingInclTax = $lShippingPrice*(1+($shippingTax/100));


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

        $totalExclTax = round((($sum+$shippingInclTax-($this->getQuote()->getSubtotal()-$this->getQuote()->getSubtotalWithDiscount()))*100)-(($sum-$sumex+($shippingInclTax-$lShippingPrice)+(($this->getQuote()->getSubtotal()-$this->getQuote()->getSubtotalWithDiscount())/(1+($shippingTax/100)))-($this->getQuote()->getSubtotal()-$this->getQuote()->getSubtotalWithDiscount()))*100));
        $tax = round((($sum-$sumex+($shippingInclTax-$lShippingPrice)+(($this->getQuote()->getSubtotal()-$this->getQuote()->getSubtotalWithDiscount())/(1+($shippingTax/100)))-($this->getQuote()->getSubtotal()-$this->getQuote()->getSubtotalWithDiscount()))*100));
        $grandTotal = round(($sum+$shippingInclTax-($this->getQuote()->getSubtotal()-$this->getQuote()->getSubtotalWithDiscount()))*100);
        $round = $grandTotal - ($totalExclTax + $tax);


        $cart2 = array(
            'Shipping' => array(
                'withouttax' => $this->toCents($lShippingPrice),
                'taxrate' => $shippingTax
            ),
            'Total' => array(
                'withouttax' => $totalExclTax,
                'tax' => $tax,
                'rounding' => $round,
                'withtax' => $grandTotal
            )
        );

        $data['Cart'] = $cart2;

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
        $response = $this->getIframe('updateCheckout');

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
        if (!$this->_quote) {
            $this->_quote = $this->_getCheckoutSession()->getQuote();
        }

        return $this->_quote;
    }

    /**
     * @return array
     */
    protected function getAddress()
    {
        return $this->defaultAddress;
    }

    /**
     * @return \Magento\Checkout\Model\Session
     */
    protected function _getCheckoutSession()
    {
        return $this->checkoutSession;
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

        $data['Articles'] = [
            [
                'quantity' => '1',
                'artnr' => 'shipping_code',
                'title' => $this->getSessionData('shipping_code'),
                'aprice' => '0',
                'taxrate' => '0',
                'discount' => '0',
                'withouttax' => '0'
            ]
        ];
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
        return $this->_getCheckoutSession()->setData($key, $value);
    }

    /**
     * @param $key
     * @param $value
     *
     * @return mixed
     */
    protected function getSessionData($key)
    {
        return $this->_getCheckoutSession()->getData($key);
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
}
