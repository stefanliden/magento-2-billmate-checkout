<?php
namespace Billmate\BillmateCheckout\Plugin;

class Url {

    /**
     * @var \Billmate\BillmateCheckout\Helper\Data
     */
    protected $helper;

    public function __construct(\Billmate\BillmateCheckout\Helper\Data $helper){
        $this->helper = $helper;
    }

    public function afterGetCheckoutUrl($subject,$result){
        if ($this->helper->getEnable()) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
            return $storeManager->getStore()->getBaseUrl() . "billmatecheckout";
        }
        return $result;
    }
}