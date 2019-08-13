<?php
class Bubbleyes_BubblitPlugin_Block_Button
    extends Mage_Catalog_Block_Product_View
{	
	protected $_helper;

	public function __construct()
	{
		$this->_helper = Mage::helper("Bubbleyes_BubblitPlugin");
	}

    public function getProductScript()
    {
        try{
            if($this->_helper->isBubblEnabledInDetails())
            {
		        $productSKU = $this -> getProduct() -> sku;
		
		        $productData = array(
                    'SKU' => $productSKU
                );

                $settings = array(
                    'Type' => $this->_helper->getBubblLayout()
                );

		        $tmp = $this->_helper->CallAPIWithResponse('getProductScript', array('Product' => $productData, 'Settings' => $settings));
                return $tmp["Script"];
            }
        }
		catch (Exception $ex) { }
    }
}
?>