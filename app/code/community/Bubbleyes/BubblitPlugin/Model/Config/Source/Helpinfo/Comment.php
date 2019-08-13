<?php
    
class Bubbleyes_BubblitPlugin_Model_Config_Source_Helpinfo_Comment extends Mage_Core_Model_Config_Data
{
    public function getCommentText(Mage_Core_Model_Config_Element $element, $currentValue)
    {
        $supportedCurrencies = Mage::helper("Bubbleyes_BubblitPlugin")->CallAPIWithResponse('listSupportedCurrencies', array());

        $result = '
            <ul style="font-size:11px">
                <li>1. Register to the Bubbleyes.com platform <br/>(at <a href="http://bubbleyes.com/" target="_blank">http://bubbleyes.com/</a>). 
                    <br/> Upon registration you will be able to log in to the Bubbleyes Client Platform (at <a href="http://admin.bubbleyes.com/" target="_blank">http://admin.bubbleyes.com/</a>). In your profile there you can access the API key needed in the plugin configuration.  
                </li>
    
                <li>
                    <br/>
                    2. Once the plugin is configured all products data will be automatically synchronized between your Magento installation and your Bubbleyes installation.
                    <br/> This includes automatically making changes when products are created or edited or deleted.  
                </li>

                <li>
                    <br/>
                    3. The supoorted currencies are: '
                    . implode(", ", $supportedCurrencies)
                    . '<br/> If you currency is not in the supported list, your data will not be synchonized. 
                </li>

                <li>
                    <br />
                    4. If you want the plugin setup to insert automatically the Bubbl button in the product details, please set "Bubbl Button Enabled In Details" = yes.
                    To insert manually the Bubbl button in your theme, please use as reference the Bubbleyes theme, which is created with the plugin installation.
                </li>
            </ul>
        ';

        return $result;
    }
}
    
?>
