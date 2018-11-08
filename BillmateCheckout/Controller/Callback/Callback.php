<?php
namespace Billmate\BillmateCheckout\Controller\Callback;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\DB\TransactionFactory;

class Callback extends \Magento\Framework\App\Action\Action
{
    const COUNTRY_ID = 'se';

    /**
     * @var PageFactory
     */
	protected $resultPageFactory;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
	private $productRepository;

    /**
     * @var \Billmate\BillmateCheckout\Helper\Data
     */
	protected $helper;

    /**
     * @var \Billmate\BillmateCheckout\Helper\Config
     */
	protected $configHelper;

    /**
     * @var \Magento\Sales\Api\Data\OrderInterface
     */
	protected $orderInterface;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
	protected $invoiceService;

    /**
     * @var \Billmate\Billmate\Model\Billmate
     */
    protected $billmateProvider;

    /**
     * @var TransactionFactory
     */
    protected $_transactionFactory;
	
	public function __construct(
	    Context $context,
        PageFactory $resultPageFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Billmate\BillmateCheckout\Helper\Data $_helper,
        \Billmate\BillmateCheckout\Helper\Config $configHelper,
		\Magento\Sales\Api\Data\OrderInterface $order,
        \Magento\Sales\Model\Service\InvoiceService $_invoiceService,
        \Billmate\Billmate\Model\Billmate $billmateProvider,
        TransactionFactory $transactionFactory
    ){
		$this->resultPageFactory = $resultPageFactory;
	    $this->productRepository = $productRepository;
		$this->invoiceService = $_invoiceService;
		$this->helper = $_helper;
		$this->configHelper = $configHelper;
		$this->orderInterface = $order;
        $this->billmateProvider = $billmateProvider;
        $this->_transactionFactory = $transactionFactory;
		parent::__construct($context);
	}
	
	public function execute()
    {
        /** @var return json row $paramsRow */
        $paramsRow = file_get_contents('php://input');

        $params = $this->getRequest()->getParams();
        $requestData = empty($paramsRow) ? $params : json_decode($paramsRow,true);;

		if (is_array($requestData) && $params) {
		    if (!is_array($params['credentials']) && !is_array($params['data'])) {
                $requestData['credentials'] = json_decode($params['credentials'], true);
                $requestData['data'] = json_decode($params['data'],true);
            }
		}

		$hash = $this->getHashCode($requestData);
		
		if ($hash == $requestData['credentials']['hash']) {
			$values = array(
				"number" => $requestData['data']['number']
			);
			$paymentInfo = $this->billmateProvider->getPaymentinfo($values);
			$this->helper->setBmPaymentMethod($paymentInfo['PaymentData']['method']);

			$order = $this->helper->getOrderByIncrementId($paymentInfo['PaymentData']['orderid']);
			if (!is_string($order->getIncrementId())) {
                $orderInfo = $this->getOrderInfo($paymentInfo);
				$order_id = $this->helper->createOrder($orderInfo, $paymentInfo['PaymentData']['orderid']);
				$order = $this->helper->getOrderById($order_id);
			}
			$order->setData('billmate_invoice_id', $requestData['data']['number']);
            $orderStateActivated = $this->configHelper->getActivated();
			if (
			    $paymentInfo['PaymentData']['status'] == 'Created'||
                ($paymentInfo['PaymentData']['status'] == 'Paid' && !$this->configHelper->getBmEnable())
            ) {
				$order->setState($orderStateActivated)->setStatus($orderStateActivated);
			} elseif ($paymentInfo['PaymentData']['status'] == 'Paid' && $this->configHelper->getBmEnable()) {
				if ($requestData['data']['status']=='Paid') {
					$order->setState($orderStateActivated)->setStatus($orderStateActivated);
					$order->save();
					$invoice = $this->invoiceService->prepareInvoice($order);
					$invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
					$invoice->register();
                    $transaction = $this->_transactionFactory->create();
					$transactionSave = $transaction->addObject($invoice)->addObject($invoice->getOrder());
					$transactionSave->save();
				}
			} elseif ($paymentInfo['PaymentData']['status'] == 'Pending') {
				$orderState = $this->configHelper->getPendingControl();
				$order->setState($orderState)->setStatus($orderState);
			} else {
				$orderState = $this->configHelper->getDeny();
				$order->setState($orderState)->setStatus($orderState);
			}
            $order->save();
		}
	}

    /**
     * @param $customerAddress
     *
     * @return array
     */
	protected function processShippingAddress($customerAddress)
    {
        $billingAddressReq = $customerAddress['Billing'];
        $billingAddress = array(
            'firstname' => $billingAddressReq['firstname'],
            'lastname' => $billingAddressReq['lastname'],
            'street' => $billingAddressReq['street'],
            'city' => $billingAddressReq['city'],
            'country_id' => $billingAddressReq['country'],
            'postcode' => $billingAddressReq['zip'],
            'telephone' => $billingAddressReq['phone'],
            'email' =>$billingAddressReq['email']
        );

        if (
            isset($customerAddress['Shipping']) &&
            isset( $customerAddress['Shipping']['firstname'])
        ) {
            $shippingAddressReq = $customerAddress['Shipping'];
            $customerAddressData = array(
                'firstname' => $shippingAddressReq['firstname'],
                'lastname' => $shippingAddressReq['lastname'],
                'street' => $shippingAddressReq['street'],
                'city' => $shippingAddressReq['city'],
                'country_id' => $shippingAddressReq['country'],
                'postcode' => $shippingAddressReq['zip'],
                'telephone' => $shippingAddressReq['phone']
            );
        } else {
            $customerAddressData = $billingAddress ;
        }

        $this->helper->setBillingAddress($billingAddress);
        $this->helper->setShippingAddress($customerAddressData);

        return $customerAddressData;
    }

    /**
     * @param $paymentInfo
     *
     * @return array
     */
    protected function getOrderInfo($paymentInfo)
    {
        $customerAddressData = $this->processShippingAddress($paymentInfo['Customer']);
        $orderInfo = array(
            'currency_id'  => $paymentInfo['PaymentData']['currency'],
            'email'        => $customerAddressData['email'],
            'shipping_address' => $customerAddressData,
            'items' => array()
        );

        $articles = $paymentInfo['Articles'];
        foreach($articles as $article) {
            if ($article['artnr'] == 'discount_code') {
                $this->helper->setSessionData('billmate_applied_discount_code', $article['title']);
            } elseif ($article['artnr'] == 'shipping_code') {
                $this->helper->setShippingMethod($article['title']);
            } else {
                if (strpos($article['artnr'], "discount") === false) {
                    $orderInfo['items'][] = [
                        'product_id' => $article['artnr'],
                        'qty' => $article['quantity'],
                        'price' => (($article['withouttax']/$article['quantity'])/100)
                    ];
                }
            }
        }

        return $orderInfo;
    }

    /**
     * @param $requestData
     *
     * @return string
     */
    protected function getHashCode($requestData)
    {
        $hash = hash_hmac('sha512', json_encode($requestData['data']), $this->configHelper->getBillmateSecret());
        return $hash;
    }
}