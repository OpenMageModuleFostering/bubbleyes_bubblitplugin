<?php
    
class Bubbleyes_BubblitPlugin_Model_Config_Source_Helpinfo_Comment extends Mage_Core_Model_Config_Data
{
    public function getCommentText(Mage_Core_Model_Config_Element $element, $currentValue)
    {
        $supportedCurrencies = Mage::helper("Bubbleyes_BubblitPlugin")->CallAPIWithResponse('listSupportedCurrencies', array());

        $result = '
            <p>
                The Bubbl plugin configuration is based at Store View level. You need to register different client for each Store View you want to integrate with Bubbleyes.
                <br/>
                Follow the steps to setup a single Store View with Bubbleyes (you can repeat the process for all Store Views):
            </p>
            <br/>
            <ul style="font-size:11px">
                <li>1. Register to the Bubbleyes.com platform <br/>(at <a href="http://admin.bubbleyes.com/" target="_blank">http://admin.bubbleyes.com/</a>). 
                    <br/> Upon registration you will be able to log in to the Bubbleyes Client Platform (at <a href="http://admin.bubbleyes.com/" target="_blank">http://admin.bubbleyes.com/</a>). In your profile there you can access the API key needed in the plugin configuration.  
                </li>
                <li>
                    <br/>
                    2. Navigate to the configuration of the plugin in the Store View you want to setup.
                </li>
                <li>
                    <br/>
                    3. Enter the received by the Bubbleyes API Key in the configuration field.
                    <br/> Once the configuration is saved with an API Key all products data will be automatically synchronized between your Magento installation and your Bubbleyes installation.
                    <br/> This includes automatically making changes when products are created or edited or deleted.  
                </li>

                <li>
                    <br />
                    4. If you want the plugin setup to insert automatically the Bubbl button in the product details, please set "Bubbl Button Enabled In Details" = yes.
                    <br/>By default the buton is inserted at the end of you product content section.

                    <br/>To insert manually or reposition the Bubbl button in your theme, please use as reference the Bubbleyes theme, which is created with the plugin installation.
                </li>
            </ul>
            <br/><br/>
            <p>
                More technical information can be found at <a href="http://api.bubbleyes.com/integration#developedWithMagento" target="_blank">http://api.bubbleyes.com/integration#developedWithMagento</a>.
            </p>
        ';

        return $result;
    }
}
    
?>
