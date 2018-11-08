<?php

/**
 * Copyright � 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Billmate\BillmateCheckout\Cron;

class CheckPending
{

    /**
     * @var \Magento\Framework\Webapi\Soap\ClientFactory
     */
	protected $clientFactory;

    /**
     * @var \Billmate\BillmateCheckout\Helper\Data
     */
	protected $helper;

    /**
     * @var \Billmate\BillmateCheckout\Helper\Config
     */
    protected $configHelper;

    /**
     * @var \Magento\Sales\Api\CreditmemoRepositoryInterface
     */
	protected $creditmemoRepositoryInterface;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
	protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
	protected $orderCollectionFactory;

    /**
     * @var \Billmate\Billmate\Model\Billmate
     */
    protected $billmateProvider;

    /**
     * CheckPending constructor.
     *
     * @param \Billmate\BillmateCheckout\Helper\Data                     $_helper
     * @param \Billmate\BillmateCheckout\Helper\Config                   $configHelper
     * @param \Magento\Framework\Webapi\Soap\ClientFactory               $_clientFactory
     * @param \Magento\Sales\Api\CreditmemoRepositoryInterface           $_creditmemoRepositoryInterface
     * @param \Magento\Framework\Api\SearchCriteriaBuilder               $_searchCriteriaBuilder
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $_orderCollectionFactory
     * @param \Billmate\Billmate\Model\Billmate                          $billmateProvider
     */
	public function __construct(
		\Billmate\BillmateCheckout\Helper\Data $_helper,
        \Billmate\BillmateCheckout\Helper\Config $configHelper,
		\Magento\Framework\Webapi\Soap\ClientFactory $_clientFactory,
		\Magento\Sales\Api\CreditmemoRepositoryInterface $_creditmemoRepositoryInterface,
		\Magento\Framework\Api\SearchCriteriaBuilder $_searchCriteriaBuilder,
		\Magento\Sales\Model\ResourceModel\Order\CollectionFactory $_orderCollectionFactory,
        \Billmate\Billmate\Model\Billmate $billmateProvider
	) {
		$this->helper = $_helper;
        $this->configHelper = $configHelper;
		$this->clientFactory = $_clientFactory;
        $this->orderCollectionFactory = $_orderCollectionFactory;
		$this->searchCriteriaBuilder = $_searchCriteriaBuilder;
		$this->creditmemoRepositoryInterface = $_creditmemoRepositoryInterface;
        $this->billmateProvider = $billmateProvider;
	}
	
	private function getOrders($options)
    {
		$orders = $this->orderCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('status',array('in' => $options))
            ->setOrder("entity_id", "desc")
            ->load();
		return $orders;
	}
	
	public function execute()
    {
		if ($this->configHelper->getFetch()) {
			$options = $this->configHelper->getMultiSelect();
			$options = explode(',', $options);
			$orders = $this->getOrders($options);
			
			foreach ($orders as $order) {
				if (!empty($order->getData('billmate_invoice_id'))) {
					$values["PaymentData"] = array(
						"number" => $order->getData('billmate_invoice_id')
					);
					$paymentInfo = $this->billmateProvider->getPaymentinfo($values);
					if ($paymentInfo['PaymentData']['status'] == 'Created' || ($paymentInfo['PaymentData']['status'] == 'Paid' && !$this->configHelper->getBmEnable())){
						$orderState = $this->configHelper->getActivated();
						$order->setState($orderState)->setStatus($orderState);
						$order->save();
					} elseif ($paymentInfo['PaymentData']['status'] == 'Paid' && $this->configHelper->getBmEnable()){
						if ($paymentInfo['PaymentData']['status']=='Paid') {
							$orderState = $this->configHelper->getActivated();
							$order->setState($orderState)->setStatus($orderState);
							$order->save();
							$invoice = $this->invoiceService->prepareInvoice($order);
							$invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
							$invoice->register();
							$transactionSave = \Magento\Framework\App\ObjectManager::getInstance()->create('Magento\Framework\DB\Transaction')->addObject($invoice)->addObject($invoice->getOrder());
							$transactionSave->save();
						}
					} else {
						$orderState = $this->configHelper->getCanceled();
						$order->setState($orderState)->setStatus($orderState);
						$order->save();
					}
				}
			}
		}
	}
}