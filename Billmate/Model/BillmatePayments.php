<?php

namespace Billmate\Billmate\Model;

/**
 * Pay In Store payment method model
 */

abstract class BillmatePayments extends \Magento\Payment\Model\Method\AbstractMethod {

    protected $_isGateway                   = true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = false;
    protected $_canRefund                   = true;
    protected $_canRefundInvoicePartial     = false;
    protected $_isOffline					= false;

    /**
     * @var \Billmate\BillmateCheckout\Helper\Data
     */
    protected $helper;

    /**
     * @var Billmate
     */
    protected $billmateProvider;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Billmate\BillmateCheckout\Helper\Data $_helper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        \Billmate\Billmate\Model\Billmate $billmateProvider,
        array $data = []
    ) {
        $this->helper = $_helper;
        $this->billmateProvider = $billmateProvider;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
    }

    public function canRefund()
    {
        return true;
    }

    public function canCapture()
    {
        return true;
    }

    public function canVoid()
    {
        return true;
    }

    public function isOffline()
    {
        return false;
    }

    protected function getBillMateProvider()
    {
        return $this->billmateProvider;
    }


    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float                                $amount
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $bm = $this->getBillMateProvider();
        $values = array();

        $order = $payment->getOrder();

        if (!empty($order->getData('billmate_invoice_id'))){
            $values["PaymentData"] = array(
                "number" => $order->getData('billmate_invoice_id')
            );
            $bm->activatePayment($values);
            $payment->setTransactionId($order->getData('billmate_invoice_id'));
            $payment->setParentTransactionId($payment->getTransactionId());
            $transaction = $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH, null, true, "");
            $transaction->setIsClosed(true);
        }
        else {
            throw new \Magento\Framework\Exception\LocalizedException(__('ID has not been recived from Billmate'));
        }
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        $bm = $this->getBillMateProvider();
        $values = array();

        $order = $payment->getOrder();
        if (!empty($order->getData('billmate_invoice_id'))) {
            $values["PaymentData"] = array(
                "number" => $order->getData('billmate_invoice_id')
            );
            $bm->cancelPayment($values);
        }
        else {
            throw new \Magento\Framework\Exception\LocalizedException(__('ID has not been recived from Billmate'));
        }
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        $bm = $this->getBillMateProvider();
        $values = array();

        $order = $payment->getOrder();
        if (!empty($order->getData('billmate_invoice_id'))){
            $values["PaymentData"] = array(
                "number" => $order->getData('billmate_invoice_id')
            );
            $bm->cancelPayment($values);
        }
        else {
            throw new \Magento\Framework\Exception\LocalizedException(__('ID has not been recived from Billmate'));
        }
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float                                $amount
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $bm = $this->getBillMateProvider();
        $values = array();

        $order = $payment->getOrder();
        if (!empty($order->getData('billmate_invoice_id'))){
            $values["PaymentData"] = array(
                "number" => $order->getData('billmate_invoice_id'),
                "partcredit" => "false"
            );
            $bm->creditPayment($values);
        }
        else {
            throw new \Magento\Framework\Exception\LocalizedException(__('ID has not been recived from Billmate'));
        }
    }
}