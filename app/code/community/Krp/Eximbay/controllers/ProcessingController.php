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
class Krp_Eximbay_ProcessingController extends Mage_Core_Controller_Front_Action
{
    /**
     * Get singleton of Checkout Session Model
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Iframe page which submits the payment data to eximbay.
     */
    public function placeformAction()
    {
       $this->loadLayout();
       $this->renderLayout();
    }

    /**
     * Show orderPlaceRedirect page which contains the eximbay iframe.
     */
    public function paymentAction()
    {
        try {
            $session = $this->_getCheckout();

            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($session->getLastRealOrderId());
            if (!$order->getId()) {
                Mage::throwException('No order for processing found');
            }

            $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
                Mage::helper('eximbay')->__('The customer was redirected to Eximbay.')
            );
            $order->save();

            $session->setEximbayQuoteId($session->getQuoteId());
            $session->setEximbayRealOrderId($session->getLastRealOrderId());
            $session->getQuote()->setIsActive(false)->save();
            $session->clear();

            $this->loadLayout();
            $this->renderLayout();
        } catch (Exception $e){
            Mage::logException($e);
            parent::_redirect('checkout/cart');
        }
    }

    /**
     * Action to which the customer will be returned when the payment is successful or not successful.
     */
    public function successAction()
    {
        $event = Mage::getModel('eximbay/event')
                 ->setEventData($this->getRequest()->getParams());
     
        try {
        	
        	$rescode = $this->getRequest()->get('rescode');
        	
        	if($rescode == '0000'){
            	$quoteId = $event->successEvent();
            	$this->_getCheckout()->setLastSuccessQuoteId($quoteId);
            	$this->_redirect('checkout/onepage/success');
            	return;
        	}else{
        		$this->_redirect('eximbay/processing/cancel');
        		return;
        	}
        	
        } catch (Mage_Core_Exception $e) {
            $this->_getCheckout()->addError($e->getMessage());
        } catch(Exception $e) {
            Mage::logException($e);
        }
        $this->_redirect('checkout/cart');
    }
    
    /**
     * When a customer cancel payment from eximbay.
     */
    public function cancelAction()
    {
    	$data['ref'] = $this->_getCheckout()->getLastRealOrderId();
    	$event = Mage::getModel('eximbay/event')
    			->setEventData($data);
    	
    	$message = $event->cancelEvent();
    	
    	// set quote to active
    	$session = $this->_getCheckout();
    	if ($quoteId = $session->getEximbayQuoteId()) {
    		$quote = Mage::getModel('sales/quote')->load($quoteId);
    		if ($quote->getId()) {
    			$quote->setIsActive(true)->save();
    			$session->setQuoteId($quoteId);
    		}
    	}
    	
    	$session->addError($message);
    	parent::_redirect('checkout/cart');
    }

    /**
     * Action to which the transaction details will be posted after the payment
     * process is complete.
     */
    public function statusAction()
    {
    	$event = Mage::getModel('eximbay/event')
    			->setEventData($this->getRequest()->getParams());
    	$message = $event->processStatusEvent();
    	$this->getResponse()->setBody($message);
    }
    
    /**
     * Set redirect into responce. This has to be encapsulated in an JavaScript
     * call to jump out of the iframe.
     *
     * @param string $path
     * @param array $arguments
     */
    protected function _redirect($path, $arguments=array())
    {
        $this->getResponse()->setBody(
            $this->getLayout()
                ->createBlock('eximbay/redirect')
                ->setRedirectUrl(Mage::getUrl($path, $arguments))
                ->toHtml()
        );
        return $this;
    }
}
