<?php

namespace SYNAPTECH\Smsalert\Model\Config\Source;

use Magento\Framework\App\ObjectManager;

class Channel implements \Magento\Framework\Option\ArrayInterface
{
	
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
						
						array ( 'label' => 'Own devices', 'value' => 'owndevices') , 
						array ( 'label' => 'Smsadvert network', 'value' => 'smsadvert')
					);
	}
}