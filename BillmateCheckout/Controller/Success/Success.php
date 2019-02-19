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

    /**
     * @var \Billmate\BillmateCheckout\Model\Order
     */
    protected $orderModel;

    /**
     * @var \Billmate\BillmateCheckout\Model\Api\Billmate
     */
    protected $billmateProvider;
	
	public function __construct(
		Context $context,
		PageFactory $resultPageFactory,
		\Magento\Framework\Event\Manager $eventManager,
		\Billmate\BillmateCheckout\Helper\Data $_helper, 
		CheckoutSession $checkoutSession,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Registry $registry,
        \Magento\Checkout\Model\Session\SuccessValidator $successValidator,
        \Billmate\BillmateCheckout\Model\Order $orderModel,
        \Billmate\BillmateCheckout\Model\Api\Billmate $billmateProvider
	) {
		$this->eventManager = $eventManager;
		$this->resultPageFactory = $resultPageFactory;
		$this->checkoutSession = $checkoutSession;
		$this->helper = $_helper;
        $this->logger = $logger;
        $this->registry = $registry;
        $this->successValidator = $successValidator;
        $this->orderModel = $orderModel;
        $this->billmateProvider = $billmateProvider;
		parent::__construct($context);
	}
	
	public function execute()
    {
        $this->helper->addLog([
            '__FILE__' => __FILE__,
            '__CLASS__' => __CLASS__,
            '__FUNCTION__' => __FUNCTION__,
            '__LINE__' => __LINE__,
            'note' => 'aaa'
        ]);

		$resultPage = $this->resultPageFactory->create();
		try{

            $this->helper->addLog([
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'note' => 'aba',
                'isset.session.bm-inc-id' => (bool)$this->helper->getSessionData('bm-inc-id'),
            ]);

            $requestData = $this->getBmRequestData();
            $values = array(
                "number" => $requestData['data']['number']
            );

            $paymentInfo = $this->billmateProvider->getPaymentinfo($values);

			if (!$this->helper->getSessionData('bm-inc-id')) {
				$orderData = array(
					'email' => $this->helper->getSessionData('billmate_email'),
					'shipping_address' => $this->helper->getSessionData('billmate_billing_address'),
                    'payment_method_name' => $paymentInfo['PaymentData']['method_name']
				);
				$orderId = $this->orderModel->create($orderData);
                if (!$orderId) {
                    throw new \Exception(
                        __('An error occurred on the server. Please try to place the order again.')
                    );
                }

                $this->helper->setSessionData('bm_order_id', $orderId);

			}

			$order = $this->helper->getOrderByIncrementId($this->helper->getSessionData('bm-inc-id'));
            $this->registry->register('bm-inc-id', $this->helper->getSessionData('bm-inc-id'));
			$orderId = $order->getId();

            $this->helper->addLog([
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'note' => 'abb',
                'orderId' => $orderId,
            ]);

			$this->eventManager->dispatch(
				'checkout_onepage_controller_success_action',
				['order_ids' => [$order->getId()]]
			);

			$this->checkoutSession->setLastSuccessQuoteId($this->helper->getQuote()->getId());
			$this->checkoutSession->setLastQuoteId($this->helper->getQuote()->getId());
			$this->checkoutSession->setLastOrderId($orderId);

            if (!$this->successValidator->isValid()) {
                return $this->resultRedirectFactory->create()->setPath('checkout/cart');
            }

            $this->helper->addLog([
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'note' => 'abc',
            ]);

            $url = $this->_url->getUrl('checkout/onepage/success');

            $this->helper->addLog([
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'note' => 'abd',
                'url' => $url,
                'headers_sent' => (headers_sent()),
            ]);

            $this->helper->clearSession();
		}
		catch (\Exception $e){
            $this->helper->setSessionData('bm-inc-id',$this->helper->getQuote()->getReservedOrderId());
            $this->helper->addLog([
                'note' => 'Could not redirect customer to store order confirmation page',
                '__FILE__' => __FILE__,
                '__CLASS__' => __CLASS__,
                '__FUNCTION__' => __FUNCTION__,
                '__LINE__' => __LINE__,
                'exception.message' => $e->getMessage(),
                'exception.file' => $e->getFile(),
                'exception.line' => $e->getLine(),
            ]);
            return $this->resultRedirectFactory->create()->setPath('billmatecheckout/success/error');
		}

        $this->helper->addLog([
            'note' => 'Customer redirected to the success page',
            '__FILE__' => __FILE__,
            '__CLASS__' => __CLASS__,
            '__FUNCTION__' => __FUNCTION__,
            '__LINE__' => __LINE__,
            'note' => 'done Return content of resultPage',
        ]);

		return $resultPage;
	}

    /**
     * @return mixed
     */
    protected function getBmRequestData()
    {
        $bmRequestData = $this->getRequest()->getParam('data');
        $bmRequestCredentials = $this->getRequest()->getParam('credentials');

        if ($bmRequestData && $bmRequestCredentials) {
            $postData['data'] = json_decode($bmRequestData, true);
            $postData['credentials'] = json_decode($bmRequestCredentials, true);
            return $postData;
        }

        $jsonBodyRequest = file_get_contents('php://input');
        if ($jsonBodyRequest) {
            return json_decode($jsonBodyRequest, true);
        }
        throw new Exception('The request does not contain information');
    }
}