<?php

/**
 * Eximbay, Online Payment Module
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GNU General Public License (GPL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/GPL-3.0 or http://www.gnu.org/copyleft/gpl.html
 * 
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Eximbay module to newer
 * versions in the future. If you wish to customize Eximbay module for your
 * needs please refer to https://www.eximbay.com for more information.
 *
 * @category    Krp
 * @package     Krp_Eximbay
 * @copyright   Copyright (c) 2014 KRPartners Co.,Ltd (https://www.eximbay.com)
 * @license     http://opensource.org/licenses/GPL-3.0  GNU General Public License (GPL 3.0)
 */

class Krp_Eximbay_Block_Checkout_Onepage_Payment_Methods extends Mage_Checkout_Block_Onepage_Payment_Methods {

    public function getMethodTitle(Mage_Payment_Model_Method_Abstract $method) {
		
    	$form = $this->getChild('payment.method.' . $method->getCode());
       	
       	if (strpos($method->getCode(), 'eximbay_') !== false) {
       		$imageLogo = '';
       		
       		$imageFilename = Mage::getDesign()->getFilename('images' . DS . 'eximbay' . DS . $method->getCode(), array('_type' => 'skin'));
       		if (file_exists($imageFilename . '.jpg')) {
       			$imageLogo = '<img src="' . $this->getSkinUrl('images/eximbay/' . $method->getCode() . '.jpg') . '" > ';
       			
       			return $imageLogo.str_repeat('&nbsp;', 3).$this->escapeHtml($method->getTitle());
       		}        
       	} else {
            //$form = $this->getChild('payment.method.' . $method->getCode());
            if ($form && $form->hasMethodTitle()) {
                return $this->escapeHtml($form->getMethodTitle());
            }
        }

        return $this->escapeHtml($method->getTitle());
    }

}
