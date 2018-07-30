<?php
namespace Billmate\BillmateCheckout\Controller\Success;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use \Magento\Checkout\Model\Session as CheckoutSession;
require_once(realpath(__DIR__."/Billmate.php"));
class Success extends \Magento\Framework\App\Action\Action {
	
	protected $resultPageFactory;
	protected $helper;
	protected $checkoutSession;
	protected $eventManager;
	
	public function __construct(
		Context $context,
		PageFactory $resultPageFactory,
		\Magento\Framework\Event\Manager $eventManager,
		\Billmate\BillmateCheckout\Helper\Data $_helper, 
        CheckoutSession $checkoutSession,
        \Billmate\Billmate\Logger\Logger $logger
	) {
		$this->eventManager = $eventManager;
		$this->resultPageFactory = $resultPageFactory;
		$this->checkoutSession = $checkoutSession;
		$this->helper = $_helper;
        $this->logger = $logger;
		parent::__construct($context);
	}
	
	public function execute(){
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$cart = $objectManager->get('\Magento\Checkout\Model\Cart');
		$resultPage = $this->resultPageFactory->create();
		$res = array();
		try{
			if(is_array($_POST)) {
				$res['credentials'] = json_decode($_POST['credentials'], true);
				$res['data'] = json_decode($_POST['data'],true);
			}
			$test = $this->helper->getTestMode();
			$ssl = true;
			$debug = false;

			$id = $this->helper->getBillmateId();
			$key = $this->helper->getBillmateSecret();
			$bm = new BillMate($id, $key, $this->helper, $ssl, $test, $debug);
			$values = array(
				"number" => $res['data']['number']
			);
			$paymentInfo = $bm->getPaymentinfo($values);
			$this->helper->setBmPaymentMethod($paymentInfo['PaymentData']['method']);
			$shipping_address = array();
			$billing_address = array();
			$useShipping = false;
			if (array_key_exists('Shipping', $paymentInfo['Customer'])){
				if (array_key_exists('firstname', $paymentInfo['Customer']['Shipping'])){
					$shipping_address = array(
						'firstname' => $paymentInfo['Customer']['Shipping']['firstname'],
						'lastname' => $paymentInfo['Customer']['Shipping']['lastname'],
						'street' => $paymentInfo['Customer']['Shipping']['street'],
						'city' => $paymentInfo['Customer']['Shipping']['city'],
						'country_id' => $paymentInfo['Customer']['Shipping']['country'],
						'postcode' => $paymentInfo['Customer']['Shipping']['zip'],
						'telephone' => $paymentInfo['Customer']['Billing']['phone']
					);
					$this->helper->setShippingAddress($shipping_address);
					$useShipping = true;
					$tempOrder = array(
						'currency_id'  => $paymentInfo['PaymentData']['currency'],
						'email'        => $paymentInfo['Customer']['Billing']['email'],
						'shipping_address' => array(
							'firstname'    => $paymentInfo['Customer']['Shipping']['firstname'],
							'lastname'     => $paymentInfo['Customer']['Shipping']['lastname'],
							'street' => $paymentInfo['Customer']['Shipping']['street'],
							'city' => $paymentInfo['Customer']['Shipping']['city'],
							'country_id' => 'SE',//$paymentInfo['Customer']['Shipping']['country'],
							'postcode' => $paymentInfo['Customer']['Shipping']['zip'],
							'telephone' => $paymentInfo['Customer']['Billing']['phone'],
						),
						'items' => array()
					);
				}
			}
			if (!$useShipping){
				$shipping_address = array(
					'firstname' => $paymentInfo['Customer']['Billing']['firstname'],
					'lastname' => $paymentInfo['Customer']['Billing']['lastname'],
					'street' => $paymentInfo['Customer']['Billing']['street'],
					'city' => $paymentInfo['Customer']['Billing']['city'],
					'country_id' => $paymentInfo['Customer']['Billing']['country'],
					'postcode' => $paymentInfo['Customer']['Billing']['zip'],
					'telephone' => $paymentInfo['Customer']['Billing']['phone']
				);
				$this->helper->setShippingAddress($shipping_address);
				$tempOrder = array(
					'currency_id'  => $paymentInfo['PaymentData']['currency'],
					'email'        => $paymentInfo['Customer']['Billing']['email'],
					'shipping_address' => array(
						'firstname'    => $paymentInfo['Customer']['Billing']['firstname'],
						'lastname'     => $paymentInfo['Customer']['Billing']['lastname'],
						'street' => $paymentInfo['Customer']['Billing']['street'],
						'city' => $paymentInfo['Customer']['Billing']['city'],
						'country_id' => $paymentInfo['Customer']['Billing']['country'],
						'postcode' => $paymentInfo['Customer']['Billing']['zip'],
						'telephone' => $paymentInfo['Customer']['Billing']['phone'],
					),
					'items' => array()
				);
			}
			$billing_address = array(
				'firstname' => $paymentInfo['Customer']['Billing']['firstname'],
				'lastname' => $paymentInfo['Customer']['Billing']['lastname'],
				'street' => $paymentInfo['Customer']['Billing']['street'],
				'city' => $paymentInfo['Customer']['Billing']['city'],
				'country_id' => $paymentInfo['Customer']['Billing']['country'],
				'postcode' => $paymentInfo['Customer']['Billing']['zip'],
				'telephone' => $paymentInfo['Customer']['Billing']['phone'],
				'email' =>$paymentInfo['Customer']['Billing']['email']
			);
			$this->helper->setBillingAddress($billing_address);
			$this->helper->setShippingAddress($shipping_address);
			$articles = $paymentInfo['Articles'];
			foreach($articles as $article){
				if ($article['artnr'] == 'discount_code'){
					$_SESSION['billmate_applied_discount_code'] = $article['title'];
				}
				else if ($article['artnr'] == 'shipping_code'){
					$this->helper->setShippingMethod($article['title']);
				}
				else {
					if (strpos($article['artnr'], "discount") === false){
						$productLoader = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Catalog\Api\ProductRepositoryInterface');
						$product = $productLoader->get($article['artnr']);
						$taxClassId = $product->getTaxClassId();
						$taxCalculation = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Tax\Model\Calculation');
						$request = $taxCalculation->getRateRequest(null, null, null, 0);
						$percent = $taxCalculation->getRate($request->setProductClassId($taxClassId));
						$tmp = array(
							'product_id' => $article['artnr'],
							'qty' => $article['quantity'],
							'price' => (($article['withouttax']/$article['quantity'])/100)//*(1+($percent/100))
						);
						array_push($tempOrder['items'], $tmp);
					}
				}
			}
			$order_id = $this->helper->createOrder($tempOrder, $paymentInfo['PaymentData']['orderid']);
			$order = \Magento\Framework\App\ObjectManager::getInstance()->create('\Magento\Sales\Model\Order')->load($order_id);
			$order->setData('billmate_invoice_id', $_SESSION['billmate_checkout_id']);
			$order->setData('billmate_method_name',$paymentInfo['PaymentData']['method_name']);
			$order->save();
			if ($paymentInfo['PaymentData']['status'] == 'Created' || ($paymentInfo['PaymentData']['status'] == 'Paid' && !$this->helper->getBmEnable())){
				$orderState = \Magento\Sales\Model\Order::STATE_PROCESSING;
				$order->setState($orderState)->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
				$order->save();
			}
			else if ($paymentInfo['PaymentData']['status'] == 'Paid' && $this->helper->getBmEnable()){
				$orderState = \Magento\Sales\Model\Order::STATE_PROCESSING;
				$order->setState($orderState)->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
				$order->save();
				$invoice = $this->invoiceService->prepareInvoice($order);
				$invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
				$invoice->register();
				$transactionSave = \Magento\Framework\App\ObjectManager::getInstance()->create('Magento\Framework\DB\Transaction')->addObject($invoice)->addObject($invoice->getOrder());
				$transactionSave->save();
			}
			else if ($paymentInfo['PaymentData']['status'] == 'Pending'){
				$orderState = $this->helper->getPendingControl();
				$order->setState($orderState)->setStatus($orderState);
				$order->save();
			}
			else {
				$orderState = $this->helper->getDeny();
				$order->setState($orderState)->setStatus($orderState);
				$order->save();
			}
		}
		catch (\Exception $e){
            $this->logger->info("Could not create order. Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
			$_SESSION['bm-inc-id'] = $cart->getQuote()->getReservedOrderId();
		}
		try {
			if (!isset($_SESSION['bm-inc-id'])){
				$orderData = array(
					'email'=>$_SESSION['billmate_email'],
					'shipping_address'=>$_SESSION['billmate_billing_address']
				);
				$orderId = $this->helper->createOrder($orderData);
				$_SESSION['bm_order_id'] = $orderId;
				$order = $objectManager->get('\Magento\Sales\Model\Order')->loadByIncrementId($_SESSION['bm-inc-id']);
			}
			$this->checkoutSession->setLastSuccessQuoteId($cart->getQuote()->getId());
			$this->checkoutSession->setLastQuoteId($cart->getQuote()->getId());
			$this->checkoutSession->setLastOrderId($order->getId());
			$this->eventManager->dispatch(
					'checkout_onepage_controller_success_action',
					['order_ids' => [$order->getId()]]
			);
			$this->helper->clearSession();
			$storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
			$url = $storeManager->getStore()->getBaseUrl() . "checkout/onepage/success";
			if (headers_sent()){
				die('<script type="text/javascript">window.location.href="' . $url . '";</script>');
			}
			else{
				header('Location: ' . $url);
				die();
			}
		}
		catch (\Exception $e){
            $this->logger->info("Could not redirect to store success page. Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
			$_SESSION['bm-inc-id'] = $cart->getQuote()->getReservedOrderId();
		}
		return $resultPage;
	}
}