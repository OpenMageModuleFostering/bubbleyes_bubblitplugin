<?php 
  class Bubbleyes_BubblitPlugin_Helper_Data extends Mage_Core_Helper_Abstract
  {
    static $APIAddress = "http://api.bubbleyes.com/client/";
    static $Version = 'magento 1.3.0';
    static $ProductsPortionSize = 25;
  
    public static function getProductPortionSize() {
      return self::$ProductsPortionSize;
    }

    public static function getAPIMethod($method) {
      return self::$APIAddress . $method;
    }
  
    public static function getAPIKey($storeId = null) {
		  return Mage::getStoreConfig('BubbleyesBubblitPluginOptions/api_access/api_key', $storeId);
    }	

    public static function isBubblEnabledInDetails($storeId = null) {
		  return Mage::getStoreConfig('BubbleyesBubblitPluginOptions/bubbl_layout/bubblenabled', $storeId);
    }

    public static function getBubblLayout($storeId = null) {
		  return Mage::getStoreConfig('BubbleyesBubblitPluginOptions/bubbl_layout/bubbllayout', $storeId);
    }

   public static function CallAPI($method, $params) {
		$data = json_encode($params);

		$response = null;

		if ($curl = curl_init()) {		
			curl_setopt($curl, CURLOPT_URL, self::getAPIMethod($method));
			curl_setopt($curl, CURLOPT_HTTPHEADER, array( 
						'Authorization: Basic ' . self::getAPIKey(),
						'Content-Type: application/json',
						'Content-Length: ' . strlen($data)));
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
            curl_setopt($curl, CURLOPT_NOSIGNAL, 1);
            curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 1);

			curl_exec($curl);

			curl_close ($curl);
		}
	}

   public static function CallAPIWithResponse($method, $params) {
		$data = json_encode($params);

		$response = null;

		if ($curl = curl_init()) {		
			curl_setopt($curl, CURLOPT_URL, self::getAPIMethod($method));
			curl_setopt($curl, CURLOPT_HTTPHEADER, array( 
						'Authorization: Basic ' . self::getAPIKey(),
						'Content-Type: application/json',
						'Content-Length: ' . strlen($data)));
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_NOSIGNAL, 1);

			$response = curl_exec($curl);

			curl_close ($curl);
		}

        return (array)json_decode($response, true);
	}

   public static function LoggerForException($ex) {
       try {
                self::CallAPI('logException', array(
                    'ExceptionMessage' => $ex-> getMessage(),
                    'ExceptionStackTrace' => $ex -> getTraceAsString(),
                    'Platform' => self::$Version
                ));
       }
       catch (Exception $logEx) { }

      return null;
   }

  } 
?>