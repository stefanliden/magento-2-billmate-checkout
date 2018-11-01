<?php
namespace Billmate\BillmateCheckout\Controller\BillmateAjax;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;

class UpdateAddress extends \Magento\Framework\App\Action\Action {
	
	protected $resultPageFactory;
	private $productRepository;
	protected $helper;
	protected $orderInterface;
	
	public function __construct(Context $context, 
		PageFactory $resultPageFactory,
		\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
		\Magento\Catalog\Api\ProductRepositoryInterface $productRepository, 
		\Billmate\BillmateCheckout\Helper\Data $_helper, 
		\Magento\Sales\Api\Data\OrderInterface $order,
        \Billmate\BillmateCheckout\Helper\Iframe $iframeHelper
		){
		$this->resultJsonFactory = $resultJsonFactory;
		$this->resultPageFactory = $resultPageFactory;
	    $this->productRepository = $productRepository;
		$this->helper = $_helper;
        $this->iframeHelper = $iframeHelper;
		$this->orderInterface = $order;
		parent::__construct($context);
	}
	
	public function execute()
    {
		if ($this->getRequest()->getParam('status') == 'Step2Loaded') {
			if (array_key_exists("Billing",$this->getRequest()->getParam('Customer'))) {
				$input = array(
					'email'=>$_POST['Customer']['Billing']['email'],
					'firstname'=>$_POST['Customer']['Billing']['firstname'],
					'lastname'=>$_POST['Customer']['Billing']['lastname'],
					'street'=>$_POST['Customer']['Billing']['street'],
					'city'=>$_POST['Customer']['Billing']['city'],
					'country_id'=>$_POST['Customer']['Billing']['country'],
					'postcode'=>$_POST['Customer']['Billing']['zip'],
					'telephone'=>$_POST['Customer']['Billing']['phone']
				);
				$_SESSION['billmate_country'] = $_POST['Customer']['Billing']['country'];
				$this->helper->setBillingAddress($input);
			}else if (array_key_exists("billingAddress",$_POST)) {
                $_email = $this->getRequest()->getParam('email','');
                $billingAddress = $this->getRequest()->getParam('billingAddress',[]);
                $_email = ($_email == '' && isset($billingAddress['email'])) ? $billingAddress['email'] : '';
				$input = array(
					'email'=> $_email,
					'firstname'=>$_POST['billingAddress']['firstname'],
					'lastname'=>$_POST['billingAddress']['lastname'],
					'street'=>$_POST['billingAddress']['street'],
					'city'=>$_POST['billingAddress']['city'],
					'country_id'=>$_POST['billingAddress']['country'],
					'postcode'=>$_POST['billingAddress']['zip'],
					'telephone'=>$_POST['billingAddress']['phone']
				);
				$_SESSION['billmate_country'] = $_POST['billingAddress']['country'];
				$this->helper->setBillingAddress($input);
			}
			if (array_key_exists("shippingAddress",$_POST)) {
				if (array_key_exists('country',$_POST['shippingAddress'])) {
					$country = $_POST['shippingAddress']['country'];
				} else {
					$country = $_SESSION['billmate_country'];
				}
				$input = array(
					'firstname'=>$_POST['shippingAddress']['firstname'],
					'lastname'=>$_POST['shippingAddress']['lastname'],
					'street'=>$_POST['shippingAddress']['street'],
					'city'=>$_POST['shippingAddress']['city'],
					'country_id'=>$country,
					'postcode'=>$_POST['shippingAddress']['zip']
				);
				$this->helper->setShippingAddress($input);
			} else if (array_key_exists("Shipping",$_POST['Customer'])) {
				if (array_key_exists("street",$_POST['Customer']['Shipping'])){
					$input = array(
						'firstname'=>$_POST['Customer']['Shipping']['firstname'],
						'lastname'=>$_POST['Customer']['Shipping']['lastname'],
						'street'=>$_POST['Customer']['Shipping']['street'],
						'city'=>$_POST['Customer']['Shipping']['city'],
						'country_id'=>$_POST['Customer']['Shipping']['country'],
						'postcode'=>$_POST['Customer']['Shipping']['zip']
					);
					$this->helper->setShippingAddress($input);
				}
			}
            $result = $this->resultJsonFactory->create();
			$iframe = $this->iframeHelper->updateIframe();
			$cart = $this->helper->getCart();
			$return = array(
				'iframe'=>$iframe,
				'cart'=>$cart
			);
			return $result->setData($return);
		}
	}
}