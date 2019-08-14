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
    //protected $_supportedLocales = array('cn', 'cz', 'da', 'en', 'es', 'fi', 'de', 'fr', 'gr', 'it', 'nl', 'ro', 'ru', 'pl', 'sv', 'tr');
	protected $_supportedLocales = array('zh', 'en', 'ja', 'ko');
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
     * Capture payment through Eximbay api
     *
     * @param Varien_Object $payment
     * @param decimal $amount
     * @return Mage_Eximbay_Model_Abstract
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
     * @return Mage_Eximbay_Model_Abstract
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
		$test_mode = Mage::getStoreConfig('payment/eximbay_acc/test');
		if($test_mode){
			return 'https://www.test.eximbay.com/web/payment2.0/payment_real.do';
		}else{
			return 'https://www.eximbay.com/web/payment2.0/payment_real.do';
		}
    }

    /**
     * Return locale of the payment method
     *
     * @return string
     */
    public function getLocale()
    {
        $locale = explode('_', Mage::app()->getLocale()->getLocaleCode());
        if (is_array($locale) && !empty($locale) && in_array($locale[0], $this->_supportedLocales)) {
			if($locale[0] == 'ko'){
				return 'kr';
			}
			else if($locale[0] == 'zh'){
				return 'cn';
			}
			else if($locale[0] == 'ja'){
				return 'jp';
			}
			else if($locale[0] == 'sv'){
				return 'se';
			}
			else{
				return $locale[0];
			}
        }
        return $this->getDefaultLocale();
    }

	/**
     * Return default locale
     *
     * @return string
     */
    public function getDefaultLocale()
    {
        return $this->_defaultLocale;
    }

    /**
     * Return display type of payment method
     *
     * @return string
     */
    public function getDisplayType()
    {
    	$diplay_mode = Mage::getStoreConfig('payment/eximbay_acc/dtype');
    	if($diplay_mode){
    		return 'I';
    	}else{
    		return 'P';
    	}
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
		$enc_secretKey = Mage::getStoreConfig('payment/eximbay_acc/secret_key');
        $enc_mid = Mage::getStoreConfig('payment/eximbay_acc/mid');		
		$secretKey = Mage::helper('core')->decrypt($enc_secretKey);
        $mid = Mage::helper('core')->decrypt($enc_mid);
		$ref = $order_id;
		//$cur = Mage::getStoreConfig('payment/eximbay_acc/currency');
		$displayType = $this->getDisplayType();
		$cur = $this->getOrder()->getOrderCurrencyCode();
		if($cur == 'KRW' || $cur == 'JPY' || $cur == 'VND')
		{
			$amt = round($this->getOrder()->getGrandTotal(), 0, PHP_ROUND_HALF_UP);
		}
		$linkBuf = $secretKey. "?mid=" . $mid ."&ref=" . $ref ."&cur=" .$cur ."&amt=" .$amt;
		
		//$fgkey = md5($linkBuf);
		$fgkey = hash("sha256", $linkBuf);

		$total = 0;
		
		$params = array(
			'ver'      				=> '170',
            'mid'      				=> $mid,
			'txntype'      			=> 'SALE',
			'displaytype'      		=> $displayType,
			'charset'	      		=> 'UTF-8',
            'ref'             		=> $ref,
            'email'          		=> $email,
            'returnurl'             => Mage::getUrl('eximbay/processing/success'),  //, array('transaction_id' => $order_id)
			'statusurl'             => Mage::getUrl('eximbay/processing/status'),
            'fgkey'            		=> $fgkey,
            'lang'              	=> $this->getLocale(),
            'amt'                	=> $amt,
            'cur'              		=> $cur,
            'shop'					=> Mage::app()->getStore()->getName(),//Mage::app()->getWebsite()->getName(),//Mage::app()->getStore()->getGroup()->getName(),//Mage::getStoreConfig('payment/eximbay_acc/title'),
            'buyer'             	=> $billing->getFirstname() . ' ' . $billing->getLastname(),  
            'tel'          			=> $billing->getTelephone(),
			'param1'          		=> '',
			'param2'          		=> '',
			'param3'          		=> '',
			'visitorid'				=> '',
			'directToReturn'		=> 'N',
			'dm_shipTo_city'				=> $shipping->getCity(),
			'dm_shipTo_country'				=> $shipping->getCountry_id(),
			'dm_shipTo_firstName'			=> $shipping->getFirstname(),
			'dm_shipTo_lastName'			=> $shipping->getLastname(),
			'dm_shipTo_phoneNumber'			=> $shipping->getTelephone(),
			'dm_shipTo_postalCode'			=> $shipping->getPostcode(),
			'dm_shipTo_state'				=> $shipping->getRegionCode(),
			'dm_shipTo_street1'				=> $shipping->getStreet(1),
			'dm_shipTo_street2'				=> $shipping->getStreet(2),
        );
		
		//get item units
		$order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
		$items = $order->getAllVisibleItems();
		$itemname = '';
		$itemcount=count($items);
		$item_loop = 0;
		if ( $itemcount > 0 ) {
			foreach ($items as $itemId => $item)
			{
				$params['dm_item_'.$item_loop.'_product'] = $item->getName();
				$params['dm_item_'.$item_loop.'_unitPrice'] = $item->getPrice();
				$params['dm_item_'.$item_loop.'_quantity'] = $item->getQtyToInvoice();
				
				//if($item_loop > 0)
				//	$params['product']  .= '&#13;&#10;';
				//$params['product']  .= $item->getName();
				
				if($item_loop == 0)
					$params['product'] = $item->getName();
					
				$item_loop++;
			}
			
			if($itemcount > 1){
				$itemcnt = $itemcount - 1;
				$params['product'] .= " ... and " . $itemcnt . " more";
			}
		}
		
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
