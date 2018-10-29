<?php
namespace Billmate\BillmateCheckout\Plugin;

class Url {

    /**
     * @var \Billmate\BillmateCheckout\Helper\Data
     */
    protected $helper;

    /**
     * Url constructor.
     *
     * @param \Billmate\BillmateCheckout\Helper\Data $helper
     */
    public function __construct(\Billmate\BillmateCheckout\Helper\Data $helper)
    {
        $this->helper = $helper;
    }

    /**
     * @param $subject
     * @param $result
     *
     * @return string
     */
    public function afterGetCheckoutUrl($subject,$result)
    {
        if ($this->helper->getEnable()) {
            return $subject->getUrl('billmatecheckout');
        }
        return $result;
    }
}