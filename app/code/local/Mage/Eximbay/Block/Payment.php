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
 * @category    Mage
 * @package     Mage_Eximbay
 * @copyright   Copyright (c) 2013 KRPartners Co.,Ltd (https://www.eximbay.com)
 * @license     http://opensource.org/licenses/GPL-3.0  GNU General Public License (GPL 3.0)
 */
class Mage_Eximbay_Block_Payment extends Mage_Core_Block_Template
{
	
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
     * Return Payment logo src
     *
     * @return string
     */
    public function getEximbayLogoSrc()
    {
        $locale = Mage::getModel('eximbay/acc')->getLocale();
        $logoFilename = Mage::getDesign()
            ->getFilename('images' . DS . 'eximbay' . DS . 'banner_120_' . $locale . '.gif', array('_type' => 'skin'));

        return $this->getSkinUrl('images/eximbay/banner_120_int.gif');
    }
    
    /**
     * Return Payment Order ID
     *
     * @return string
     */
   	public function getEximbayTransId()
    {
    	return $this->_getOrder()->getRealOrderId();
    }
    
    
    /**
     * Return Payment Date
     *
     * @return string
     */
    public function getEximbayDate()
    {
    	return $this->_getOrder()->getCreatedAtStoreDate();
    }
    

    
    /**
     * Return Payment Amt
     *
     * @return string
     */
   	public function getEximbayAmt()
   	{
   		$amt = round($this->_getOrder()->getGrandTotal(), 2);
   		$cur = $this->_getOrder()->getOrderCurrencyCode();
   		if($cur == 'KRW' || $cur == 'JPY' || $cur == 'VND')
   		{
   			$amt = round($this->_getOrder()->getGrandTotal(), 0, PHP_ROUND_HALF_UP);	
   		}
   		
   		$cur_symbol = Mage::app()->getLocale()->currency( $cur )->getSymbol();
   		
    	return $cur_symbol.$amt;
    }
    
    
    /**
     * Get Display Type
     *
     * @return string
     */
    public function getEximbayDisplayType()
    {
   		$displayType = Mage::getStoreConfig('payment/eximbay_acc/dtype');
    	return $displayType;
    }

    /**
     * Get Eximbay Title
     *
     * @return string
     */
    public function getEximbayTitle()
    {
    	$title = Mage::getStoreConfig('payment/eximbay_acc/title');
    	return $title;
    }
    
   
}
