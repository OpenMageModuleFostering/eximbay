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
class Krp_Eximbay_Block_Redirect extends Mage_Core_Block_Template
{
    /**
     * Constructor. Set template.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('eximbay/redirect.phtml');
    }
    
    /**
     * Return checkout session instance
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
    	return Mage::getSingleton('checkout/session');
    }
    
    /**
     * Return order instance
     *
     * @return Mage_Sales_Model_Order|null
     */
    protected function _getOrder()
    {
    	if ($this->getOrder()) {
    		return $this->getOrder();
    	} elseif ($orderIncrementId = $this->_getCheckout()->getLastRealOrderId()) {
    		return Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
    	} else {
    		return null;
    	}
    }
    
    /**
     * Retrieve payment method code
     *
     * @return string
     */
    public function getPaymentMethodCode()
    {
    	return $this->_getOrder()->getPayment()->getMethodInstance()->getCode();
    }
    
    
    /**
     * Get Display Type
     *
     * @return string
     */
    public function getEximbayDisplayType()
    {
    	$displayType = Mage::getStoreConfig('payment/'.$this->getPaymentMethodCode().'/dtype');
    	return $displayType;
    }
}
