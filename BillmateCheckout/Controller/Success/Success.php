<?php
namespace Billmate\BillmateCheckout\Controller\Success;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use \Magento\Checkout\Model\Session as CheckoutSession;
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
        \Psr\Log\LoggerInterface $logger
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
		try{
			if (!isset($_SESSION['bm-inc-id'])){
				$orderData = array(
					'email'=>$_SESSION['billmate_email'],
					'shipping_address'=>$_SESSION['billmate_billing_address']
				);
				$orderId = $this->helper->createOrder($orderData);
				$_SESSION['bm_order_id'] = $orderId;
			}
			$order = $objectManager->get('\Magento\Sales\Model\Order')->loadByIncrementId($_SESSION['bm-inc-id']);
			$orderId = $order->getId();
			$this->eventManager->dispatch(
				'checkout_onepage_controller_success_action',
				['order_ids' => [$order->getId()]]
			);
			
			$this->checkoutSession->setLastSuccessQuoteId($cart->getQuote()->getId());
			$this->checkoutSession->setLastQuoteId($cart->getQuote()->getId());
			$this->checkoutSession->setLastOrderId($orderId);
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
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
			$_SESSION['bm-inc-id'] = $cart->getQuote()->getReservedOrderId();

            $this->logger->error(print_r(array(
                'note' => 'could not redirect customer to store order confirmation page',
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'exception.message' => $e->getMessage(),
                'exception.file' => $e->getFile(),
                'exception.line' => $e->getLine(),
                '' => ''
            ), true));
		}
		return $resultPage;
	}
}