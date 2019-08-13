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
            $productId = $observer->getEvent()->getProduct()->getId();	
            
            self::UpdateProduct($productId, $this->_helper);
        }
		catch (Exception $ex) { 
            $this->_helper->LoggerForException($ex);
        }
    }

	public function HandleProductDelete(Varien_Event_Observer $observer)
    {
        try{
            // Retrieve the product being deleted from the event observer
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
            $code = Mage::getSingleton('adminhtml/config_data')->getStore();
            $storeId = Mage::getModel('core/store')->load($code)->getId();            

            if($storeId != 0)
            {
		        $productIds = Mage::getModel('catalog/product')
                              ->getCollection()
                              ->addStoreFilter($storeId)
                              ->addAttributeToFilter('visibility', array('neq' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE))
                              ->addAttributeToSelect('entity_id');

                $timestamp = date('YmdHis');

                //do in portions
                $itt = 0;
                $productsPortion = array();
                foreach($productIds as $productId)
                {                   
                    $product = Mage::getModel('catalog/product')->setStoreId($storeId)->load($productId->getId());

                    array_push($productsPortion, $product);
                    $itt++;

                    if($itt == $this->_helper->getProductPortionSize())
                    {
                        try {
                            $this->_helper->CallAPI('importProducts', array('ProductsXML' => self::BuildProductsXML($productsPortion), 'Timestamp' => $timestamp), $storeId);
                        }
                        catch (Exception $exPortion) {
                            $this->_helper->LoggerForException($exPortion);
                        }
                        $itt = 0;
                        $productsPortion = array();
                    }
                }

                $this->_helper->CallAPI('importProducts', array('ProductsXML' => self::BuildProductsXML($productsPortion), 'Timestamp' => $timestamp, 'IsLastPortion' => true), $storeId);
            }
        }
		catch (Exception $ex) { 
            $this->_helper->LoggerForException($ex);
        }
    }

    public static function GetPrice($product, $baseCurrency, $currentCurrency) {

        $price = $product->getPrice();
        $discountedPrice =  $product->getSpecialPrice();
        $currency = $baseCurrency;

         if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {

               $optionCollection= $product->getTypeInstance(true)->getOptionsCollection($product);
               $selectionsCollection = $product->getTypeInstance(true)->getSelectionsCollection(
		           $product->getTypeInstance(true)->getOptionsIds($product), 
                   $product
	           );
               $optionCollection->appendSelections($selectionsCollection);

               foreach($optionCollection as $option)
               {
                   $minPrice = 0;

                   if($option->required) {
                        $selections = $option->getSelections();
                        $minPrice = min(array_map(function ($s) {
                                        return $s->price;
                                    }, $selections));
                   }

                   $price += $minPrice;
               }

               $discountedPrice = $price * $product->getSpecialPrice() / 100;
         }
         else if($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED) {
            $associatedProducts = $product->getTypeInstance(true)->getAssociatedProducts($product);
            
            foreach($associatedProducts as $associatedProduct)
            {
                   $price += $associatedProduct -> getPrice();
                   $discountedPrice += $associatedProduct -> getSpecialPrice();
            }
         }

         //convert to store currency
         $rates = Mage::getModel('directory/currency')->getCurrencyRates($baseCurrency, array($currentCurrency));
         if(count($rates) > 0)
         {
              $price = Mage::helper('directory')->currencyConvert($price, $baseCurrency, $currentCurrency);
              $discountedPrice = Mage::helper('directory')->currencyConvert($discountedPrice, $baseCurrency, $currentCurrency);
              $currency = $currentCurrency;
         }
         
        return array($price, $discountedPrice, $currency);          
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

    public static function GetCategory($product) {
        $categories = $product -> getCategoryCollection() -> addAttributeToSelect(array('name'));
			        
        $productCategory = NULL;
        $productCategoryId = NULL;
		if(count($categories) > 0)
		{
            $category = $categories -> getFirstItem();
			$productCategory = $category -> getName();
			$productCategoryId = $category -> getId();
        }

        return array($productCategory, $productCategoryId);
    }

    public static function UpdateProduct($productId, $helper) {
            //update product for each store, as some properties are global
            foreach(Mage::app()->getStores() as $store) {
                $storeId = $store->getId();

                $product = Mage::getModel('catalog/product')->setStoreId($storeId)->load($productId);

                if(in_array($store->getWebsiteId(), $product->getWebsiteIds()))
                {
                    if($product->sku != NULL)
		            {
                        // create or update
                        if($product->visibility != Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE) {
			                
                            list($price, $discountedPrice, $currency) = self::GetPrice($product, $store->getBaseCurrencyCode(), $store->getCurrentCurrencyCode());
                            list($productCategory, $productCategoryId) = self::GetCategory($product);
                            $productImage = self::GetImageUrl($product);

			                $productData = array(
				                'SKU'               => $product->sku,
				                'Name'				=> $product->name,
				                'ShopUrl'			=> $product->getProductUrl(),
				                'Description'		=> $product->short_description,
				                'Currency'			=> $currency,
				                'Price'             => number_format($price, 2, '.', ''),
				                'DiscountedPrice'   => number_format($discountedPrice, 2, '.', ''),
				                'IsActive'			=> ($product -> status) == 1 ? "true" : "false",
				                'Image'				=> $productImage,
				                'Category'			=> $productCategory,
                                'CategoryId'        => $productCategoryId
			                );

			                $helper->CallAPI('createOrEditProduct', array('Product' => $productData), $storeId);
		                }
                        //delete
                        else {
		                    self::DeleteProduct($product, $helper);
                        }
                    }
                }
                else {
		             self::DeleteProduct($product, $helper);
                }
            }
    }

    public static function DeleteProduct($product, $helper) {
        	$productData = array(
                'SKU' => $product->sku
            );

            //delete from each store view
            foreach(Mage::app()->getStores() as $store) {
                $storeId = $store->getId();

		        $helper->CallAPI('deleteProduct', array('Product' => $productData), $storeId);
            }
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

            list($price, $discountedPrice, $currency) = self::GetPrice($product, $store->getBaseCurrencyCode(), $store->getCurrentCurrencyCode());

			$productXML -> addChild("currency", $currency);
			$productXML -> addChild("price", number_format($price, 2, '.', ''));
			if($discountedPrice != null) {
				$productXML -> addChild("discountedprice", number_format($discountedPrice, 2, '.', ''));
			}

			$productXML -> addChild("active", ($product -> status) == 1 ? "true" : "false");

            list($productCategory, $productCategoryId) = self::GetCategory($product);
            if($productCategory != NULL) {
				$productXML -> addChild("category", htmlspecialchars($productCategory, ENT_QUOTES));
				$productXML -> addChild("categoryId", $productCategoryId);
            }

            $productImage = self::GetImageUrl($product);
			$productXML -> addChild("image", htmlspecialchars($productImage, ENT_QUOTES));
		}

		return $productsXML->asXML();
	}
}

?>