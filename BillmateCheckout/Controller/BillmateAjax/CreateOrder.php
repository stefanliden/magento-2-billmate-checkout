<?php
namespace Billmate\BillmateCheckout\Controller\BillmateAjax;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;

class CreateOrder extends \Magento\Framework\App\Action\Action {
	
	protected $resultPageFactory;
	private $productRepository;
	protected $helper;
	protected $orderInterface;
	protected $eventManager;
	
	public function __construct(Context $context, 
		PageFactory $resultPageFactory,
		\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
		\Magento\Catalog\Api\ProductRepositoryInterface $productRepository, 
		\Billmate\BillmateCheckout\Helper\Data $_helper, 
		\Magento\Framework\Event\Manager $eventManager,
		\Magento\Sales\Api\Data\OrderInterface $order
		){
		$this->eventManager = $eventManager;
		$this->resultJsonFactory = $resultJsonFactory;
		$this->resultPageFactory = $resultPageFactory;
	    $this->productRepository = $productRepository;
		$this->helper = $_helper;
		$this->orderInterface = $order;
		parent::__construct($context);
	}
	
	public function execute(){
		$result = $this->resultJsonFactory->create();
		if ($_POST['status'] == 'Step2Loaded'){
			if (isset($_SESSION['billmate_email'])){
				$input = array(
					'email'=>$_SESSION['billmate_email'],
					'shipping_address'=>$_SESSION['billmate_billing_address'],
					'items'=>array()
				);
				$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
				$cart = $objectManager->get('\Magento\Checkout\Model\Cart');
				$allItems = $cart->getQuote()->getAllVisibleItems();
				foreach($allItems as $item){
					array_push($input['items'], array(
						'product_id'=>$item->getSku(),
						'qty'=>$item->getQty(),
						'price'=>$item->getPrice()
					));
				}
				$orderId = $this->helper->createOrder($input);
				$_SESSION['bm_order_id'] = $orderId;
				return $result->setData('billmatecheckout/success/success');
			}
		}
	}
}