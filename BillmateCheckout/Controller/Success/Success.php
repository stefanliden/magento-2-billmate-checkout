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
		CheckoutSession $checkoutSession
	) {
		$this->eventManager = $eventManager;
		$this->resultPageFactory = $resultPageFactory;
		$this->checkoutSession = $checkoutSession;
		$this->helper = $_helper;
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
			$this->eventManager->dispatch(
					'checkout_onepage_controller_success_action',
					['order_ids' => [$order->getId()]]
			);
		}
		catch (\Exception $e){
				$_SESSION['bm-inc-id'] = $cart->getQuote()->getReservedOrderId();
		}
		return $resultPage;
	}
}