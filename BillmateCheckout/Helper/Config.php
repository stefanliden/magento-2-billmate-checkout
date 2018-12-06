<?php

namespace Billmate\BillmateCheckout\Helper;

class Config extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_GENERAL_ENABLE = 'billmate_billmatecheckout/general/enable';
    const XML_PATH_GENERAL_PUSHORDEREVENTS = 'billmate_billmatecheckout/general/pushorderevents';
    const XML_PATH_GENERAL_BTN = 'billmate_billmatecheckout/general/inc_dec_btns';
    const XML_PATH_GENERAL_ATTRIBUTES = 'billmate_billmatecheckout/general/show_attributes_cart';
    const XML_PATH_GENERAL_TERMS_URL = 'billmate_billmatecheckout/general/terms_url';
    const XML_PATH_GENERAL_PP_URL = 'billmate_billmatecheckout/general/privacy_policy_url';
    const XML_PATH_CREDENTIALS_ID = 'billmate_billmatecheckout/credentials/billmate_id';
    const XML_PATH_CREDENTIALS_KEY = 'billmate_billmatecheckout/credentials/billmate_key';
    const XML_PATH_GENERAL_TESTMODE = 'billmate_billmatecheckout/credentials/testmode';

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

    /**
     * @return mixed
     */
    public function getPushEvents()
    {
        return $this->getConfig(self::XML_PATH_GENERAL_PUSHORDEREVENTS);
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

    /**
     * @return string
     */
    public function getPPURL()
    {
        return $this->getConfig(self::XML_PATH_GENERAL_PP_URL);
    }
}
