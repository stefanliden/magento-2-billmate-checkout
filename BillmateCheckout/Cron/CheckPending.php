<?php

/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Billmate\BillmateCheckout\Cron;
require_once(realpath(__DIR__."/Billmate.php"));

class CheckPending {
	
	protected $clientFactory;
	protected $helper;
	protected $creditmemoRepositoryInterface;
	protected $searchCriteriaBuilder;
	protected $orderCollectionFactory;
	
	public function __construct(
		\Billmate\BillmateCheckout\Helper\Data $_helper,
		\Magento\Framework\Webapi\Soap\ClientFactory $_clientFactory,
		\Magento\Sales\Api\CreditmemoRepositoryInterface $_creditmemoRepositoryInterface,
		\Magento\Framework\Api\SearchCriteriaBuilder $_searchCriteriaBuilder,
		\Magento\Sales\Model\ResourceModel\Order\CollectionFactory $_orderCollectionFactory
	){
		$this->helper = $_helper;
		$this->clientFactory = $_clientFactory;
        $this->orderCollectionFactory = $_orderCollectionFactory;
		$this->searchCriteriaBuilder = $_searchCriteriaBuilder;
		$this->creditmemoRepositoryInterface = $_creditmemoRepositoryInterface;
	}
	
	
	
	private function getOrders($options){
		$orders = $this->orderCollectionFactory->create()->addAttributeToSelect('*')->addAttributeToFilter('status',array('in' => $options))->setOrder("entity_id", "desc")->load();
		return $orders;
	}
	
	public function execute(){
		if ($this->helper->getFetch()){
			$options = $this->helper->getMultiSelect();
			$options = explode(',', $options);
			$orders = $this->getOrders($options);
			$test = $this->helper->getTestMode();
			$ssl = true;
			$debug = false;

			$id = $this->helper->getBillmateId();
			$key = $this->helper->getBillmateSecret();
			$bm = new BillMate($id, $key, $this->helper, $ssl, $test, $debug);
			
			
			foreach ($orders as $order){
				if (!empty($order->getData('billmate_invoice_id'))){
					$values["PaymentData"] = array(
						"number" => $order->getData('billmate_invoice_id')
					);
					$paymentInfo = $bm->getPaymentinfo($values);
					if ($paymentInfo['PaymentData']['status'] == 'Created' || ($paymentInfo['PaymentData']['status'] == 'Paid' && !$this->helper->getBmEnable())){
						$orderState = $this->helper->getActivated();
						$order->setState($orderState)->setStatus($orderState);
						$order->save();
					}
					else if ($paymentInfo['PaymentData']['status'] == 'Paid' && $this->helper->getBmEnable()){
						if ($res['data']['status']=='Paid'){
							$orderState = $this->helper->getActivated();
							$order->setState($orderState)->setStatus($orderState);
							$order->save();
							$invoice = $this->invoiceService->prepareInvoice($order);
							$invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
							$invoice->register();
							$transactionSave = \Magento\Framework\App\ObjectManager::getInstance()->create('Magento\Framework\DB\Transaction')->addObject($invoice)->addObject($invoice->getOrder());
							$transactionSave->save();
						}
					}
					else if ($paymentInfo['PaymentData']['status'] == 'Pending'){}
					else {
						$orderState = $this->helper->getCanceled();
						$order->setState($orderState)->setStatus($orderState);
						$order->save();
					}
				}
			}
		}
	}
}