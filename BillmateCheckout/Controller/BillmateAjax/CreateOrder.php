<?php
namespace Billmate\BillmateCheckout\Controller\BillmateAjax;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;

class CreateOrder extends \Magento\Framework\App\Action\Action
{
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
     * @var \Magento\Sales\Api\Data\OrderInterface
     */
	protected $orderInterface;

    /**
     * @var \Magento\Framework\Event\Manager
     */
	protected $eventManager;

    /**
     * @var \Magento\Checkout\Model\Session
     */
	protected $checkoutSession;

    /**
     * CreateOrder constructor.
     *
     * @param Context                                          $context
     * @param PageFactory                                      $resultPageFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface  $productRepository
     * @param \Billmate\BillmateCheckout\Helper\Data           $_helper
     * @param \Magento\Framework\Event\Manager                 $eventManager
     * @param \Magento\Sales\Api\Data\OrderInterface           $order
     * @param \Magento\Checkout\Model\Session                  $_session
     */
	public function __construct(Context $context, 
		PageFactory $resultPageFactory,
		\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
		\Magento\Catalog\Api\ProductRepositoryInterface $productRepository, 
		\Billmate\BillmateCheckout\Helper\Data $_helper, 
		\Magento\Framework\Event\Manager $eventManager,
		\Magento\Sales\Api\Data\OrderInterface $order,
		\Magento\Checkout\Model\Session $_session
	) {
		$this->eventManager = $eventManager;
		$this->resultJsonFactory = $resultJsonFactory;
		$this->resultPageFactory = $resultPageFactory;
	    $this->productRepository = $productRepository;
		$this->helper = $_helper;
		$this->orderInterface = $order;
		$this->checkoutSession = $_session;
		parent::__construct($context);
	}

    /**
     * @return $this
     */
	public function execute()
    {
		if ($this->getRequest()->getParam('status') == 'Step2Loaded'){
			if ($this->helper->getSessionData('billmate_email')){
                $orderData = array(
					'email' => $this->helper->getSessionData('billmate_email'),
					'shipping_address' => $this->helper->getSessionData('billmate_billing_address'),
					'items'=>array()
				);
				$quote = $this->helper->getQuote();
				$allItems = $this->helper->getItems();

				foreach ($allItems as $item) {
					$orderData['items'][] = [
						'product_id' => $item->getSku(),
						'qty' => $item->getQty(),
						'price' => $item->getPrice()
					];
				}
				$orderId = $this->helper->createOrder($orderData);
                $this->helper->setSessionData('bm_order_id', $orderId);
				$this->helper->setSessionData('last_success_quote_id', $quote->getId());
				$this->helper->setSessionData('last_quote_id', $quote->getId());
				$this->helper->setSessionData('last_order_id', $orderId);

                $result = $this->resultJsonFactory->create();
				return $result->setData('checkout/onepage/success');
			}
		}
	}
}