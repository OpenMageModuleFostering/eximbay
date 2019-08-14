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
abstract class Mage_Eximbay_Model_Abstract extends Mage_Payment_Model_Method_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code = 'eximbay_abstract';

    protected $_formBlockType = 'eximbay/form';
    protected $_infoBlockType = 'eximbay/info';

    /**
     * Availability options
     */
    protected $_isGateway              = true;
    protected $_canAuthorize           = true;
    protected $_canCapture             = true;
    protected $_canCapturePartial      = false;
    protected $_canRefund              = false;
    protected $_canVoid                = false;
    protected $_canUseInternal         = false;
    protected $_canUseCheckout         = true;
    protected $_canUseForMultishipping = false;

    protected $_paymentMethod    = 'abstract';
    protected $_defaultLocale    = 'en';
    protected $_supportedLocales = array('cn', 'cz', 'da', 'en', 'es', 'fi', 'de', 'fr', 'gr', 'it', 'nl', 'ro', 'ru', 'pl', 'sv', 'tr');
    protected $_hidelogin        = '1';

    protected $_order;

    /**
     * Get order model
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if (!$this->_order) {
            $this->_order = $this->getInfoInstance()->getOrder();
        }
        return $this->_order;
    }

    /**
     * Return url for redirection after order placed
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('eximbay/processing/payment');
    }

    /**
     * Capture payment through Moneybookers api
     *
     * @param Varien_Object $payment
     * @param decimal $amount
     * @return Phoenix_Moneybookers_Model_Abstract
     */
    public function capture(Varien_Object $payment, $amount)
    {
        $payment->setStatus(self::STATUS_APPROVED)
            ->setTransactionId($this->getTransactionId())
            ->setIsTransactionClosed(0);

        return $this;
    }

    /**
     * Cancel payment
     *
     * @param Varien_Object $payment
     * @return Phoenix_Moneybookers_Model_Abstract
     */
    public function cancel(Varien_Object $payment)
    {
        $payment->setStatus(self::STATUS_DECLINED)
            ->setTransactionId($this->getTransactionId())
            ->setIsTransactionClosed(1);

        return $this;
    }

    /**
     * Return url of payment method
     *
     * @return string
     */
    public function getUrl()
    {
         return 'http://www.test2.eximbay.com/web/payment2.0/payment_real.do';
        // return 'https://www.eximbay.com/web/payment2.0/payment_real.do';
    }

    /**
     * Return url of payment method
     *
     * @return string
     */
    public function getLocale()
    {
        $locale = explode('_', Mage::app()->getLocale()->getLocaleCode());
        if (is_array($locale) && !empty($locale) && in_array($locale[0], $this->_supportedLocales)) {
            return $locale[0];
        }
        return $this->getDefaultLocale();
    }

    /**
     * prepare params array to send it to gateway page via POST
     *
     * @return array
     */
    public function getFormFields()
    {
        $order_id = $this->getOrder()->getRealOrderId();
        $billing  = $this->getOrder()->getBillingAddress();
		$shipping  = $this->getOrder()->getShippingAddress();
        if ($this->getOrder()->getBillingAddress()->getEmail()) {
            $email = $this->getOrder()->getBillingAddress()->getEmail();
        } else {
            $email = $this->getOrder()->getCustomerEmail();
        }
        $amt = round($this->getOrder()->getGrandTotal(), 2);
        $secretKey = "289F40E6640124B2628640168C3C5464"; // test secret key
        $mid = "1849705C64";							 // test mid
		$ref = "abcd1234567890";
		$linkBuf = $secretKey. "?mid=" . $mid ."&ref=" . $ref ."&cur=" ."USD" ."&amt=" .$amt;
		
		//$fgkey = md5($linkBuf);
		$fgkey = hash("sha256", $linkBuf);

		$quantity = $this->getOrder()->getTotalItemCount(); 
		$total = 0;
				
        $params = array(
			'ver'      				=> '170',
            'mid'      				=> $mid,
			'txntype'      				=> 'SALE',
			'displaytype'      				=> 'I',
            'secretkey'             => $secretKey,
            'ref'             		=> $ref,
            'email'          		=> $email,
            'transid'        		=> $order_id,
            'returnurl'             => Mage::getUrl('eximbay/processing/success', array('transid' => $order_id)),
            'fgkey'            		=> $fgkey,
            'lang'              	=> 'KR', //$this->getLocale(),
            'amt'                	=> round($this->getOrder()->getGrandTotal(), 2),
            'cur'              		=> 'USD', //$this->getOrder()->getOrderCurrencyCode(),
            'shop'					=> $this->getOrder()->getStore(),
            'buyer'             	=> $billing->getFirstname() . $billing->getLastname(),
            'product'				=> $order_id,
            'tel'          			=> $billing->getTelephone(),
            'payment_methods'       => $this->_paymentMethod,
            'hide_login'            => $this->_hidelogin,
            'new_window_redirect'   => '1',
            'rescode'				=> '',
            'resmsg'				=> '',
            'authcode'				=> '',
			'visitorid'				=> '',
			'dm_item_0_product'				=> $order_id,
			'dm_item_0_quantity'				=> $quantity,
			'dm_item_0_unitPrice'				=> round($this->getOrder()->getGrandTotal(), 2),
			'dm_shipTo_city'				=> $shipping->getData("city"),
			'dm_shipTo_country'				=> $shipping->getCountry(),
			'dm_shipTo_firstName'				=> $shipping->getData("firstname"),
			'dm_shipTo_lastName'				=> $shipping->getData("lastname"),
			'dm_shipTo_phoneNumber'				=> $shipping->getTelephone(),
			'dm_shipTo_postalCode'				=> $shipping->getData("postcode"),
			'dm_shipTo_state'				=> $shipping->getRegionCode(),
			'dm_shipTo_street1'				=> $shipping->getData("street")
        );

        return $params;
    }
    /**
     * Get initialized flag status
     * @return true
     */
    public function isInitializeNeeded()
    {
        return true;
    }

    /**
     * Instantiate state and set it to state onject
     * //@param
     * //@param
     */
    public function initialize($paymentAction, $stateObject)
    {
        $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        $stateObject->setState($state);
        $stateObject->setStatus(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
        $stateObject->setIsNotified(false);
    }

    /**
     * Get config action to process initialization
     *
     * @return string
     */
    public function getConfigPaymentAction()
    {
        $paymentAction = $this->getConfigData('payment_action');
        return empty($paymentAction) ? true : $paymentAction;
    }
}
