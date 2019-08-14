<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category    Mage
 * @package     Mage_Eximbay
 */
class Mage_Eximbay_ProcessingController extends Mage_Core_Controller_Front_Action
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
                Mage::helper('eximbay')->__('The customer was redirected to eximbay.')
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
        
        $data = $event->getEventData();
        
        try {
        	if ($data['rescode'] == '0000') // payment successful
        	{
        		$data["status"] = 2;
        		$event->setEventData($data);
        		$message = $event->processStatusEvent();
	            $quoteId = $event->successEvent();
	            $this->_getCheckout()->setLastSuccessQuoteId($quoteId);
	            $this->_redirect('checkout/onepage/success');
	            return;
	        }
	        else // payment not successful
	        {
	        	/*
	        	$data['status'] = -2;
	        	$event->setEventData($data);
        		$message = $event->processStatusEvent();
	        	*/
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
		        $this->_redirect('checkout/cart');
	        }
	        
        } catch (Mage_Core_Exception $e) {
            $this->_getCheckout()->addError($e->getMessage());
        } catch(Exception $e) {
            Mage::logException($e);
        }
        
        $this->getResponse()->setBody($message);
       $this->_redirect('checkout/cart');
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
