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

    /**
     * @return string
     */
	public function getSuccess()
    {
		$str = $this->helper->getCheckout();
		$this->helper->clearSession();
		return $str;
	}
}