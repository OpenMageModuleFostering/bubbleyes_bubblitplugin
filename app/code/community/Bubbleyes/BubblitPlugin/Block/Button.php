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
            $product = $this -> getProduct();
            $storeId = $product -> getStoreId();

            if($this->_helper->isBubblEnabledInDetails($storeId))
            {		
		        $productData = array(
                    'SKU' => $product -> sku
                );

                $settings = NULL;

		        $tmp = $this->_helper->CallAPIWithResponse('getProductScript', array('Product' => $productData, 'Settings' => $settings), $storeId);
                return $tmp["Script"];
            }
        }
		catch (Exception $ex) { }
    }
}
?>