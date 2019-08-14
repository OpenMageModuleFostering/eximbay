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

/**
 * Eximbay's notification processor model
 */
class Krp_Eximbay_Model_Event
{

    /*
     * @param Mage_Sales_Model_Order
     */
    protected $_order = null;

    /**
     * Event request data
     * @var array
     */
    protected $_eventData = array();

    /**
     * Enent request data setter
     * @param array $data
     * @return Krp_Eximbay_Model_Event
     */
    public function setEventData(array $data)
    {
        $this->_eventData = $data;
        return $this;
    }

    /**
     * Event request data getter
     * @param string $key
     * @return array|string
     */
    public function getEventData($key = null)
    {
        if (null === $key) {
            return $this->_eventData;
        }
        return isset($this->_eventData[$key]) ? $this->_eventData[$key] : null;
    }

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
     * Process status notification from  server
     *
     * @return String
     */
    public function processStatusEvent()
    {
        try {
            $params = $this->_validateEventData();
            $msg = '';
            if($params['rescode'] == '0000') {   //ok 
            	$msg = Mage::helper('eximbay')->__('The amount has been authorized and captured by EXIMBAY.');
                $this->_processSale($msg);
            }else{    							 //fail
            	$msg = Mage::helper('eximbay')->__('Payment was not successful. Response Code :'.$params['rescode'].' Response Message :'.$params['resmsg']);
            	//$this->_processFail($msg); 
            	$this->_processCancel($msg); 
            }
            return $msg;
        } catch (Mage_Core_Exception $e) {
            return $e->getMessage();
        } catch(Exception $e) {
            Mage::logException($e);
        }
        return;
    }

    /**
     * Process cancelation
     */
    public function cancelEvent() {
        try {
            $this->_validateEventData(false);
            $this->_processCancel('Payment was cancelled by Customer.');
            return Mage::helper('eximbay')->__('The order has been cancelled.');
        } catch (Mage_Core_Exception $e) {
            return $e->getMessage();
        } catch(Exception $e) {
            Mage::logException($e);
        }
        return '';
    }

    /**
     * Validate request and return QuoteId
     * Can throw Mage_Core_Exception and Exception
     *
     * @return int
     */
    public function successEvent(){
        $this->_validateEventData(false);
        return $this->_order->getQuoteId();
    }

    /**
     * Processed order cancelation or fail
     * @param string $msg Order history message
     */
    protected function _processCancel($msg)
    {
        $this->_order->cancel();
        $this->_order->addStatusToHistory(Mage_Sales_Model_Order::STATE_CANCELED, $msg);
        $this->_order->save();
    }

    /**
     * Processes payment confirmation, creates invoice if necessary, updates order status,
     * sends order confirmation to customer
     * @param string $msg Order history message
     */
    protected function _processSale($msg)
    {
    	$this->_createInvoice();
        $this->_order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, $msg);
        // save transaction ID
        $transid = $this->getEventData('transid');
        if (empty($transid)){
        	$transid = $this->getEventData('requestid');
        }
        $this->_order->getPayment()->setLastTransId($transid);
        // send new order email
        $this->_order->sendNewOrderEmail();
        $this->_order->setEmailSent(true);
        $this->_order->save();
    }
    
    /**
     * Processes payment fail
     * @param string $msg Order history message
     */
    protected function _processFail($msg)
    {
    	$this->_order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true, $msg)->save();
    }

    /**
     * Builds invoice for order
     */
    protected function _createInvoice()
    {
		try{
    		if (!$this->_order->canInvoice()) {
	            Mage::throwException(Mage::helper('eximbay')->__('Cannot create an invoice.'));
	        }
	        $invoice = $this->_order->prepareInvoice();
	        
	        if (!$invoice->getTotalQty()) {
	        	Mage::throwException(Mage::helper('eximbay')->__('Cannot create an invoice without products.'));
	        }
	        $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
	        $invoice->register();
	        $transactionSave = Mage::getModel('core/resource_transaction')->addObject($invoice)->addObject($invoice->getOrder());
	        $transactionSave->save();
        } catch (Mage_Core_Exception $e) {
        	return $e->getMessage();
        } catch(Exception $e) {
        	Mage::logException($e);
        }
    }

    /**
     * Checking returned parameters
     * Thorws Mage_Core_Exception if error
     * @param bool $fullCheck Whether to make additional validations such as payment status etc.
     *
     * @return array  $params request params
     */
    protected function _validateEventData($fullCheck = true)
    {
    	
        // get request variables
        $params = $this->_eventData;
        
        Mage::log($params, null, 'eximbay.log');
        
        if (empty($params)) {
            Mage::throwException('Request does not contain any elements.');
        }

        // check order ID
        if (empty($params['ref'])) {		// || ($fullCheck == false && $this->_getCheckout()->getEximbayRealOrderId() != $params['ref'])
            Mage::throwException('Missing or invalid order ID.');
        }
        // load order for further validation
        $this->_order = Mage::getModel('sales/order')->loadByIncrementId($params['ref']);
        if (!$this->_order->getId()) {
            Mage::throwException('Order not found.');
        }

        if (0 !== strpos($this->_order->getPayment()->getMethodInstance()->getCode(), 'eximbay_')) {
            Mage::throwException('Unknown payment method.');
        }

        // make additional validation
        if ($fullCheck) {
            // check payment status
            if (empty($params['rescode'])) {
                Mage::throwException('Unknown payment status.');
            }
            
            // check transaction signature
            if (empty($params['fgkey'])) {
            	Mage::throwException('Invalid transaction signature.');
            }
            
            if($params['rescode'] == '0000'){
            	$enc_secretKey = Mage::getStoreConfig('payment/'.$this->_order->getPayment()->getMethodInstance()->getCode().'/secret_key');	
				$secretKey = Mage::helper('core')->decrypt($enc_secretKey);
				if (empty($secretKey)) {
					Mage::throwException('Secretkey is empty.');
				}
				
				$suffix = "&transid=" .$params['transid'];
				if (empty($params['transid'])){
					$suffix = "&requestid=" .$params['requestid'];
				}
				
            	$linkBuf = $secretKey. "?mid=" .$params['mid'] ."&ref=" .$params['ref'] ."&cur=" .$params['cur'] ."&amt=" .$params['amt'] ."&rescode=" .$params['rescode'] .$suffix;
            	//$newFgkey = md5($linkBuf);
            	$newFgkey = hash("sha256", $linkBuf);
            	
            	Mage::log($linkBuf.'/'.$newFgkey, null, 'eximbay.log');
            	
            	if(strtolower($params['fgkey']) != $newFgkey){
            		Mage::throwException('Hash is not valid. ');
            	}
            }
            
            // check transaction amount if currency matches
            if ($this->_order->getOrderCurrencyCode() == $params['cur']) {
            	if($this->_order->getOrderCurrencyCode() == 'KRW' || $this->_order->getOrderCurrencyCode() == 'JPY' || $this->_order->getOrderCurrencyCode() == 'VND')
            	{
            		if(round($this->_order->getGrandTotal(), 0, PHP_ROUND_HALF_UP) != $params['amt']){
            			Mage::throwException('Transaction amount does not match.');
            		}
            	}else{
	                if(round($this->_order->getGrandTotal(), 2) != $params['amt']) {
	                    Mage::throwException('Transaction amount does not match.');
	                }
            	}
            }
        }
        return $params;
    }
}