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
                // create or update
                if($product->visibility != Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE) {
			        $categories = $product -> getCategoryCollection() -> addAttributeToSelect(array('name'));
			        
                    $productCategory = NULL;
                    $productCategoryId = NULL;
			        if(count($categories) > 0)
			        {
                        $category = $categories -> getFirstItem();
				        $productCategory = $category -> getName();
				        $productCategoryId = $category -> getId();
                    }

                    $productImage = $this->GetImageUrl($product);

			        $productData = array(
				        'SKU'               => $product->sku,
				        'Name'				=> $product->name,
				        'ShopUrl'			=> $product-> getProductUrl(),
				        'Description'		=> $product->short_description,
				        'Currency'			=> Mage::app()->getStore()->getCurrentCurrencyCode(),
				        'Price'             => number_format($product->price, 2, '.', ''),
				        'DiscountedPrice'   => $product-> special_price == null ? null : number_format($product-> special_price, 2, '.', ''),
				        'IsActive'			=> ($product -> status) == 1 ? "true" : "false",
				        'Image'				=> $productImage,
				        'Category'			=> $productCategory,
                        'CategoryId'        => $productCategoryId
			        );

			        $this->_helper->CallAPI('createOrEditProduct', array('Product' => $productData));
		        }
                //delete
                else {
		            self::DeleteProduct($product, $this->_helper);
                }
            }
        }
		catch (Exception $ex) { 
            $this->_helper->LoggerForException($ex);
        }
    }

	public function HandleProductDelete(Varien_Event_Observer $observer)
    {
        try{
            $product = $observer->getEvent()->getProduct();	
		    self::DeleteProduct($product, $this->_helper);
        }
		catch (Exception $ex) { 
            $this->_helper->LoggerForException($ex);
        }
    }

	public function HandleSettingsChanged(Varien_Event_Observer $observer)
    {
        try{
		    $products = Mage::getModel('catalog/product')->getCollection()->addAttributeToFilter('visibility', array('neq' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE))->addAttributeToSelect(array('name', 'short_description', 'price', 'special_price', 'currency', 'status', 'image'));

            $timestamp = date('YmdHis');

            //do in portions
            $itt = 0;
            $productsPortion = array();
            foreach($products as $product)
            {
                array_push($productsPortion, $product);
                $itt++;

                if($itt == $this->_helper->getProductPortionSize())
                {
                    try {
                        $this->_helper->CallAPI('importProducts', array('ProductsXML' => self::BuildProductsXML($productsPortion), 'Timestamp' => $timestamp));
                    }
                    catch (Exception $exPortion) {
                        $this->_helper->LoggerForException($exPortion);
                    }
                    $itt = 0;
                    $productsPortion = array();
                }
            }

            $this->_helper->CallAPI('importProducts', array('ProductsXML' => self::BuildProductsXML($productsPortion), 'Timestamp' => $timestamp, 'IsLastPortion' => true));
        }
		catch (Exception $ex) { 
            $this->_helper->LoggerForException($ex);
        }
    }

    public static function GetImageUrl($product) {
        $productMediaConfig = Mage::getModel('catalog/product_media_config');

        $productImage = $product->getImage();
        
        if($productImage == 'no_selection') {
            $productImage = NULL;
        }
        else {
            $productImage = $productMediaConfig->getMediaUrl($productImage);
        }

        return $productImage;
    }

    public static function DeleteProduct($product, $helper) {
        	$productData = array(
                'SKU' => $product->sku
            );

		    $helper->CallAPI('deleteProduct', array('Product' => $productData));
    }

	public static function BuildProductsXML($products) {
		$productsXML = new SimpleXMLElement('<products/>');

		foreach ($products as $product)
		{
			$productXML = $productsXML -> addChild("product");
			
			$productXML -> addAttribute("sku", htmlspecialchars($product->sku, ENT_QUOTES));

			$productXML -> addChild("shopurl", htmlspecialchars($product-> getProductUrl(), ENT_QUOTES));
			
			$productXML -> addChild("name", htmlspecialchars($product->name, ENT_QUOTES));
			$productXML -> addChild("description", htmlspecialchars($product->short_description, ENT_QUOTES));
			$productXML -> addChild("currency", Mage::app()->getStore()->getCurrentCurrencyCode());
			$productXML -> addChild("price", number_format($product->price, 2, '.', ''));

			$discountedPrice = $product-> special_price;
			if($discountedPrice != null) {
				$productXML -> addChild("discountedprice", number_format($discountedPrice, 2, '.', ''));
			}

			$productXML -> addChild("active", ($product -> status) == 1 ? "true" : "false");

			$categories = $product -> getCategoryCollection() -> addAttributeToSelect(array('name','id'));
			if(count($categories) > 0)
			{
                $category = $categories -> getFirstItem();
				$productXML -> addChild("category", htmlspecialchars($category -> getName(), ENT_QUOTES));
				$productXML -> addChild("categoryId", $category -> getId());
            }

            $productImage = self::GetImageUrl($product);
			$productXML -> addChild("image", htmlspecialchars($productImage, ENT_QUOTES));
		}

		return $productsXML->asXML();
	}
}

?>