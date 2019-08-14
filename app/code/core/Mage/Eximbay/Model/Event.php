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

/**
 * Eximbay's notification processor model
 */
class Mage_Eximbay_Model_Event
{
    const EXIMBAY_STATUS_NOT_SUCCESS = -2;
    const EXIMBAY_STATUS_SUCCESS = 2;

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
     * @return Mage_Eximbay_Model_Event
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
            switch($params['status']) {
                case self::EXIMBAY_STATUS_NOT_SUCCESS: //fail or cancel
                    $msg = Mage::helper('eximbay')->__('Payment was not successful.');
                    $this->_processCancel($msg);
                    break;
                case self::EXIMBAY_STATUS_SUCCESS: //ok
                    $msg = Mage::helper('eximbay')->__('The amount has been authorized and captured by EXIMBAY.');
                    $this->_processSale($params['status'], $msg);
                    break;
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
     * Process cancelation or fail
     */
    public function cancelEvent() {
        try {
            $this->_validateEventData(false);
            $this->_processCancel('Payment was not successful.');
            return Mage::helper('eximbay')->__('The order was not completed.');
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
        $this->_order->addStatusToHistory(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, $msg);
        $this->_order->save();
    }

    /**
     * Processes payment confirmation, creates invoice if necessary, updates order status,
     * sends order confirmation to customer
     * @param string $msg Order history message
     */
    protected function _processSale($status, $msg)
    {
        switch ($status) {
            case self::EXIMBAY_STATUS_SUCCESS:
                $this->_createInvoice();
                $this->_order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, $msg);
                $this->_order->setStatus(Mage_Sales_Model_Order::STATE_COMPLETE);
        		$this->_order->addStatusToHistory(Mage_Sales_Model_Order::STATE_COMPLETE, 'Payment Completed', true);
                // save transaction ID
                $this->_order->getPayment()->setLastTransId($this->getEventData('transid'));
                // send new order email
                $this->_order->sendNewOrderEmail();
                $this->_order->setEmailSent(true);
                break;
        }
        $this->_order->save();
    }

    /**
     * Builds invoice for order
     */
    protected function _createInvoice()
    {
        if (!$this->_order->canInvoice()) {
            return;
        }
        $invoice = $this->_order->prepareInvoice();
        $invoice->register()->capture();
        $this->_order->addRelatedObject($invoice);
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
        
        if (empty($params)) {
            Mage::throwException('Request does not contain any elements.');
        }

        // check order ID
        if (empty($params['transid'])
            || ($fullCheck == false && $this->_getCheckout()->getEximbayRealOrderId() != $params['transid'])
        ) {
            Mage::throwException('Missing or invalid order ID.');
        }
        // load order for further validation
        $this->_order = Mage::getModel('sales/order')->loadByIncrementId($params['transid']);
        if (!$this->_order->getId()) {
            Mage::throwException('Order not found.');
        }

        if (0 !== strpos($this->_order->getPayment()->getMethodInstance()->getCode(), 'eximbay_')) {
            Mage::throwException('Unknown payment method.');
        }

        // make additional validation
        if ($fullCheck) {
            // check payment status
            if (empty($params['status'])) {
                Mage::throwException('Unknown payment status.');
            }
            
            // check transaction amount if currency matches
            if ($this->_order->getOrderCurrencyCode() == $params['cur']) {
                if (round($this->_order->getGrandTotal(), 2) != $params['amt']) {
                    Mage::throwException('Transaction amount does not match.');
                }
            }
        }
        return $params;
    }
}
