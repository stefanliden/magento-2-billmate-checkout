<?php
namespace Billmate\BillmateCheckout\Controller\Callback;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
require_once(realpath(__DIR__."/Billmate.php"));
class Callback extends \Magento\Framework\App\Action\Action {
	
	protected $resultPageFactory;
	private $productRepository;
	protected $helper;
	protected $orderInterface;
	protected $invoiceService;
	
	public function __construct(Context $context, PageFactory $resultPageFactory, \Magento\Catalog\Api\ProductRepositoryInterface $productRepository, \Billmate\BillmateCheckout\Helper\Data $_helper, 
		\Magento\Sales\Api\Data\OrderInterface $order, \Magento\Sales\Model\Service\InvoiceService $_invoiceService){
		$this->resultPageFactory = $resultPageFactory;
	    $this->productRepository = $productRepository;
		$this->invoiceService = $_invoiceService;
		$this->helper = $_helper;
		$this->orderInterface = $order;
		parent::__construct($context);
	}
	
	public function execute(){
		$_POST = file_get_contents('php://input');
        $_POST = empty($_POST) ? $_GET : $_POST;
		$res = is_array($_POST)?$_POST:json_decode($_POST,true);
		if(is_array($_POST)) {
			$res['credentials'] = json_decode($_POST['credentials'], true);
			$res['data'] = json_decode($_POST['data'],true);
		}
		$hash = hash_hmac('sha512', json_encode($res['data']), $this->helper->getBillmateSecret());
		
		if ($hash == $res['credentials']['hash']){
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
			$order = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Sales\Api\Data\OrderInterface')->loadByIncrementId($paymentInfo['PaymentData']['orderid']);
			if (!is_string($order->getIncrementId())){
				if (array_key_exists('Shipping', $paymentInfo['Customer'])){
					if (array_key_exists('firstname', $paymentInfo['Customer']['Shipping'])){
						$shipping_address = array(
							'firstname' => $paymentInfo['Customer']['Shipping']['firstname'],
							'lastname' => $paymentInfo['Customer']['Shipping']['lastname'],
							'street' => $paymentInfo['Customer']['Shipping']['street'],
							'city' => $paymentInfo['Customer']['Shipping']['city'],
							'country_id' => $paymentInfo['Customer']['Shipping']['country'],
							'postcode' => $paymentInfo['Customer']['Shipping']['zip'],
							'telephone' => $paymentInfo['Customer']['Shipping']['phone']
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
								'telephone' => $paymentInfo['Customer']['Shipping']['phone'],
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
			}
			$order->setData('billmate_invoice_id', $res['data']['number']);
			$order->save();
			if ($paymentInfo['PaymentData']['status'] == 'Created' || ($paymentInfo['PaymentData']['status'] == 'Paid' && !$this->helper->getBmEnable())){
				$orderState = \Magento\Sales\Model\Order::STATE_PROCESSING;
				$order->setState($orderState)->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
				$order->save();
			}
			else if ($paymentInfo['PaymentData']['status'] == 'Paid' && $this->helper->getBmEnable()){
				if ($res['data']['status']=='Paid'){
					$orderState = \Magento\Sales\Model\Order::STATE_PROCESSING;
					$order->setState($orderState)->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
					$order->save();
					$invoice = $this->invoiceService->prepareInvoice($order);
					$invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
					$invoice->register();
					$transactionSave = \Magento\Framework\App\ObjectManager::getInstance()->create('Magento\Framework\DB\Transaction')->addObject($invoice)->addObject($invoice->getOrder());
					$transactionSave->save();
				}
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
	}
}