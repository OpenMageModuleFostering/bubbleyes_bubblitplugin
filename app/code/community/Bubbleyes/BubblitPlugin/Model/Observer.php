<?php

class Bubbleyes_BubblitPlugin_Model_Observer
{
	protected $_helper;

	public function __construct()
	{
		$this->_helper = Mage::helper("Bubbleyes_BubblitPlugin");
	}
	
    public function HandleProductUpdate(Varien_Event_Observer $observer)
    {
        try{
            // Retrieve the product being updated from the event observer
            $product = $observer->getEvent()->getProduct();	

		    if($product->sku != null)
		    {
			    $categories = $product -> getCategoryCollection() -> addAttributeToSelect(array('name'));
			    $productCategory = null;
			    if(count($categories) > 0)
			    {
				    $productCategory = $categories -> getFirstItem() -> getName();
			    }

			    $productData = array(
				    'SKU'               => $product->sku,
				    'Name'				=> $product->name,
				    'ShopUrl'			=> $product-> getProductUrl(),
				    'Description'		=> $product->short_description,
				    'Currency'			=> Mage::app()->getStore()->getCurrentCurrencyCode(),
				    'Price'             => number_format($product->price, 2, '.', ''),
				    'DiscountedPrice'   => $product-> special_price == null ? null : number_format($product-> special_price, 2, '.', ''),
				    'IsActive'			=> ($product -> status) == 1 ? "true" : "false",
				    'Image'				=> $product -> getImage() != 'no_selection' ? $product -> getImageUrl() : null,
				    'Category'			=> $productCategory
			    );

			    $this->_helper->CallAPI('createOrEditProduct', array('Product' => $productData));
		    }
        }
		catch (Exception $ex) { }
    }

	public function HandleProductDelete(Varien_Event_Observer $observer)
    {
        try{
            $product = $observer->getEvent()->getProduct();	
		    $productData = array(
                'SKU' => $product->sku
            );

		    $this->_helper->CallAPI('deleteProduct', array('Product' => $productData));
        }
		catch (Exception $ex) { }
    }

	public function HandleSettingsChanged(Varien_Event_Observer $observer)
    {
        try{
		    $products = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect(array('name', 'short_description', 'price', 'special_price', 'currency', 'status', 'image'));

            //do in portions
            $itt = 0;
            $productsPortion = array();
            foreach($products as $product)
            {
                array_push($productsPortion, $product);
                $itt++;

                if($itt == $this->_helper->getProductPortionSize())
                {
                    $this->_helper->CallAPI('importProducts', array('ProductsXML' => self::BuildProductsXML($productsPortion)));
                    
                    $itt = 0;
                    $productsPortion = array();
                }
            }

            if($itt > 0)
            {
                 $this->_helper->CallAPI('importProducts', array('ProductsXML' => self::BuildProductsXML($productsPortion)));
            }
        }
		catch (Exception $ex) { }
    }

	public static function BuildProductsXML($products) {
		$productsXML = new SimpleXMLElement('<products/>');

		foreach ($products as $product)
		{
			$productXML = $productsXML -> addChild("product");
			
			$productXML -> addAttribute("sku", $product->sku);

			$productXML -> addChild("shopurl", $product-> getProductUrl());
			
			$productXML -> addChild("name", $product->name);
			$productXML -> addChild("description", $product->short_description);
			$productXML -> addChild("currency", Mage::app()->getStore()->getCurrentCurrencyCode());
			$productXML -> addChild("price", number_format($product->price, 2, '.', ''));

			$discountedPrice = $product-> special_price;
			if($discountedPrice != null) {
				$productXML -> addChild("discountedprice", number_format($discountedPrice, 2, '.', ''));
			}

			$productXML -> addChild("active", ($product -> status) == 1 ? "true" : "false");

			$categories = $product -> getCategoryCollection() -> addAttributeToSelect(array('name'));
			if(count($categories) > 0)
			{
				$productXML -> addChild("category", $categories -> getFirstItem() -> getName());
			}

			$productXML -> addChild("image", $product -> getImage() != 'no_selection' ? $product -> getImageUrl() : null);
		}

		return $productsXML->asXML();
	}
}

?>