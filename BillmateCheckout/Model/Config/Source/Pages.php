<?php

namespace Billmate\BillmateCheckout\Model\Config\Source;

class Pages implements \Magento\Framework\Option\ArrayInterface {
    /**
    * Get status options
    *
    * @return array
    */  
    public function toOptionArray()
    {

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $collection = $objectManager->get('\Magento\Cms\Model\ResourceModel\Page\CollectionFactory')->create();
        $collection->addFieldToFilter('is_active' , \Magento\Cms\Model\Page::STATUS_ENABLED);
        $pages = array();
        foreach($collection as $page){
           $pages[$page->getId()] = $page->getTitle();
        }
        return $pages;
    }
}
