<?php
namespace Billmate\BillmateCheckout\Block;
 
class Success extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Billmate\BillmateCheckout\Helper\Data
     */
    protected $helper;

    /**
     * Success constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Billmate\BillmateCheckout\Helper\Data           $_helper
     * @param array                                            $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Billmate\BillmateCheckout\Helper\Data $_helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
		$this->helper = $_helper;
	}

	public function getLastOrderIncId()
    {
        return $this->helper->getSessionData('bm-inc-id');
        $this->helper->setSessionData('bm-inc-id',null);
    }
}