<?php
 
class Bubbleyes_BubblitPlugin_Model_Config_Source_Bubbllayout
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'long', 'label' => 'extended'),
            array('value' => 'short', 'label' => 'compact'),
        );
    }
 
    public function toArray()
    {
        return array(
            'long'   => 'extended',
            'short' => 'compact'
        );
    }
}

?>
