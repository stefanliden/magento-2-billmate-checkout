<?php
namespace Billmate\BillmateCheckout\Controller\Success;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use \Magento\Checkout\Model\Session as CheckoutSession;
class Success extends \Magento\Framework\App\Action\Action
{

    /**
     * @var PageFactory
     */
	protected $resultPageFactory;

    /**
     * @var \Billmate\BillmateCheckout\Helper\Data
     */
	protected $helper;

    /**
     * @var CheckoutSession
     */
	protected $checkoutSession;

    /**
     * @var \Magento\Framework\Event\Manager
     */
	protected $eventManager;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;
	
	public function __construct(
		Context $context,
		PageFactory $resultPageFactory,
		\Magento\Framework\Event\Manager $eventManager,
		\Billmate\BillmateCheckout\Helper\Data $_helper, 
		CheckoutSession $checkoutSession,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Registry $registry,
        \Magento\Checkout\Model\Session\SuccessValidator $successValidator
	) {
		$this->eventManager = $eventManager;
		$this->resultPageFactory = $resultPageFactory;
		$this->checkoutSession = $checkoutSession;
		$this->helper = $_helper;
        $this->logger = $logger;
        $this->registry = $registry;
        $this->successValidator = $successValidator;
		parent::__construct($context);
	}
	
	public function execute()
    {
        if (!$this->successValidator->isValid()) {
            //return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }
        $this->logger->error(print_r(array(
            '__FILE__' => __FILE__,
            '__CLASS__' => __CLASS__,
            '__FUNCTION__' => __FUNCTION__,
            '__LINE__' => __LINE__,
            'date' => date('Y-m-d H:i:s'),
            'note' => 'aaa'
        ), true));

		$resultPage = $this->resultPageFactory->create();
		try{

            $this->logger->error(print_r(array(
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'date' => date('Y-m-d H:i:s'),
                'note' => 'aba',
                'isset.session.bm-inc-id' => (bool)$this->helper->getSessionData('bm-inc-id'),
            ), true));

			if (!$this->helper->getSessionData('bm-inc-id')) {
				$orderData = array(
					'email' => $this->helper->getSessionData('billmate_email'),
					'shipping_address' => $this->helper->getSessionData('billmate_billing_address')
				);
				$orderId = $this->helper->createOrder($orderData);

                $this->helper->setSessionData('bm_order_id', $orderId);

			}
			$order = $this->helper->getOrderByIncrementId($this->helper->getSessionData('bm-inc-id'));
            $this->registry->register('bm-inc-id', $this->helper->getSessionData('bm-inc-id'));
			$orderId = $order->getId();

            $this->logger->error(print_r(array(
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'date' => date('Y-m-d H:i:s'),
                'note' => 'abb',
                'orderId' => $orderId,
            ), true));

			$this->eventManager->dispatch(
				'checkout_onepage_controller_success_action',
				['order_ids' => [$order->getId()]]
			);

			$this->checkoutSession->setLastSuccessQuoteId($this->helper->getQuote()->getId());
			$this->checkoutSession->setLastQuoteId($this->helper->getQuote()->getId());
			$this->checkoutSession->setLastOrderId($orderId);

            $this->logger->error(print_r(array(
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'date' => date('Y-m-d H:i:s'),
                'note' => 'abc',
            ), true));

            $url = $this->_url->getUrl('checkout/onepage/success');

            $this->logger->error(print_r(array(
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'date' => date('Y-m-d H:i:s'),
                'note' => 'abd',
                'url' => $url,
                'headers_sent' => (headers_sent()),
            ), true));

            $this->helper->clearSession();
		}
		catch (\Exception $e){
            $this->helper->setSessionData('bm-inc-id',$this->helper->getQuote()->getReservedOrderId());
            $this->logger->error(print_r(array(
                'note' => 'could not redirect customer to store order confirmation page',
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'exception.message' => $e->getMessage(),
                'exception.file' => $e->getFile(),
                'exception.line' => $e->getLine(),
            ), true));
		}

        $this->logger->error(print_r(array(
            'note' => 'could not redirect customer to store order confirmation page',
            '__FILE__' => __FILE__,
            '__CLASS__' => __CLASS__,
            '__FUNCTION__' => __FUNCTION__,
            '__LINE__' => __LINE__,
            'date' => date('Y-m-d H:i:s'),
            'note' => 'done Return content of resultPage',
        ), true));

		return $resultPage;
	}
}