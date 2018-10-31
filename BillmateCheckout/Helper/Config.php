<?php

namespace Billmate\BillmateCheckout\Helper;

class Config extends \Magento\Framework\App\Helper\AbstractHelper
{

    const XML_PATH_GENERAL_ENABLE = 'billmate_billmatecheckout/general/enable';
    const XML_PATH_GENERAL_PUSHORDEREVENTS = 'billmate_billmatecheckout/general/pushorderevents';
    const XML_PATH_GENERAL_CUSTOMCSS = 'billmate_billmatecheckout/general/customcss';
    const XML_PATH_GENERAL_BTN = 'billmate_billmatecheckout/general/inc_dec_btns';
    const XML_PATH_GENERAL_ATTRIBUTES = 'billmate_billmatecheckout/general/show_attributes_cart';
    const XML_PATH_GENERAL_TERMS_URL = 'billmate_billmatecheckout/general/terms_url';
    const XML_PATH_CREDENTIALS_ID = 'billmate_billmatecheckout/credentials/billmate_id';
    const XML_PATH_CREDENTIALS_KEY = 'billmate_billmatecheckout/credentials/billmate_key';
    const XML_PATH_GENERAL_TESTMODE = 'billmate_billmatecheckout/credentials/testmode';
    const XML_PATH_PENDING_FETCH = 'billmate_billmatecheckout/pending_settings/fetch';
    const XML_PATH_PENDING_MULTISELECT = 'billmate_billmatecheckout/pending_settings/multiselect';
    const XML_PATH_PENDING_PENDING_CONTROL = 'billmate_billmatecheckout/pending_settings/bm_pending_control';
    const XML_PATH_PENDING_DENY = 'billmate_billmatecheckout/pending_settings/bm_deny';
    const XML_PATH_PENDING_ACTIVATED = 'billmate_billmatecheckout/pending_settings/bm_activated';
    const XML_PATH_PENDING_CANCELED = 'billmate_billmatecheckout/pending_settings/bm_canceled';
    const XML_PATH_PENDING_ENABLE = 'billmate_billmatecheckout/pending_settings/enable';

    /**
     * @param $config_path
     *
     * @return mixed
     */
    public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return boolean
     */
    public function getEnable()
    {
        return (bool)$this->getConfig(self::XML_PATH_GENERAL_ENABLE);
    }

    /**
     * @return boolean
     */
    public function getBtnEnable()
    {
        return (bool)$this->getConfig(self::XML_PATH_GENERAL_BTN);
    }

    /**
     * @return int
     */
    public function getBillmateId()
    {
        return $this->getConfig(self::XML_PATH_CREDENTIALS_ID);
    }

    /**
     * @return string
     */
    public function getBillmateSecret()
    {
        return $this->getConfig(self::XML_PATH_CREDENTIALS_KEY);
    }

    /**
     * @return boolean
     */
    public function getTestMode()
    {
        return $this->getConfig(self::XML_PATH_GENERAL_TESTMODE);
    }

    public function getFetch()
    {
        
        return $this->getConfig(self::XML_PATH_PENDING_FETCH);
    }

    /**
     * @return mixed
     */
    public function getMultiSelect()
    {
        return $this->getConfig(self::XML_PATH_PENDING_MULTISELECT);
    }

    /**
     * @return mixed
     */
    public function getPendingControl()
    {
        return $this->getConfig(self::XML_PATH_PENDING_PENDING_CONTROL);
    }

    /**
     * @return mixed
     */
    public function getDeny()
    {
        return $this->getConfig(self::XML_PATH_PENDING_DENY);
    }

    /**
     * @return mixed
     */
    public function getActivated()
    {
        return $this->getConfig(self::XML_PATH_PENDING_ACTIVATED);
    }

    /**
     * @return int
     */
    public function getShippingTaxClass()
    {
        return $this->getConfig('tax/classes/shipping_tax_class');
    }

    /**
     * @return mixed
     */
    public function getCanceled()
    {
        return $this->getConfig(self::XML_PATH_PENDING_CANCELED);
    }

    /**
     * @return boolean
     */
    public function getBmEnable()
    {
        return $this->getConfig(self::XML_PATH_PENDING_ENABLE);
    }

    /**
     * @return mixed
     */
    public function getShowAttribute()
    {
        return $this->getConfig(self::XML_PATH_GENERAL_ATTRIBUTES);
    }

    /**
     * @return string
     */
    public function getTermsURL()
    {
        return $this->getConfig(self::XML_PATH_GENERAL_TERMS_URL);
    }
}
