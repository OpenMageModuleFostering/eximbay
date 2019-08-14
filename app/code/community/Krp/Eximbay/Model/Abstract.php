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
abstract class Krp_Eximbay_Model_Abstract extends Mage_Payment_Model_Method_Abstract
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
    protected $_canRefund              = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid                = false;
    protected $_canUseInternal         = true;
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
     * @return Krp_Eximbay_Model_Abstract
     */
    public function capture(Varien_Object $payment, $amount)
    {
        $payment->setStatus(self::STATUS_APPROVED)
            ->setTransactionId($payment->getLastTransId())
            ->setIsTransactionClosed(0);

        return $this;
    }

    /**
     * Cancel payment
     *
     * @param Varien_Object $payment
     * @return Krp_Eximbay_Model_Abstract
     */
    public function cancel(Varien_Object $payment)
    {
        $payment->setStatus(self::STATUS_DECLINED)
            ->setTransactionId($this->getTransactionId())
            ->setIsTransactionClosed(1);

        return $this;
    }
	
    
    /**
     * Refund a capture transaction
     *
     * @param Varien_Object $payment
     * @param float $amount
     */
    public function refund(Varien_Object $payment, $amount)
    {
   		Mage::log("Amount : ".$amount, null, 'eximbay'.Mage::getModel('core/date')->date('Y-m-d').'.log');
    	if ($amount <= 0) {	
			Mage::throwException(Mage::helper('eximbay')->__('Invalid amount for refund.'));
		}
			
		Mage::log("TransID : ".$this->_getParentTransactionId($payment), null, 'eximbay'.Mage::getModel('core/date')->date('Y-m-d').'.log');
		if (!$this->_getParentTransactionId($payment)) {
			Mage::throwException(Mage::helper('eximbay')->__('Invalid transaction ID.'));
		}	

		$order = $payment->getOrder();

		$canRefundMore = $payment->getCreditmemo()->getInvoice()->canRefund();
		$isFullRefund = !$canRefundMore && (0 == ((float)$order->getBaseTotalOnlineRefunded() + (float)$order->getBaseTotalOfflineRefunded()));
		$payment->setRefundType($isFullRefund ? 'F' : 'P');
		$payment->setRefundAmt($amount,2);
		$payment->setEximbayTransId($this->_getParentTransactionId($payment));

		$request = $this->_buildRequest($payment);
		$result = $this->_postRequest($request);
		
		if ($result['rescode'] == '0000') {
			$shouldCloseCaptureTransaction = $payment->getOrder()->canCreditmemo() ? 0 : 1;
			$payment
				->setParentTransactionId($this->_getParentTransactionId($payment))
				->setTransactionId($result['refundtransid'])
				->setIsTransactionClosed(1)
				->setShouldCloseParentTransaction($shouldCloseCaptureTransaction);
				//->setTransactionAdditionalInfo("eximbayRefundTransId", $result['refundtransid']);

			return $this;
		}else{
			Mage::throwException(Mage::helper('eximbay')->__($result['resmsg']));
		}		
    }
    
    /**
     * Parent transaction id getter
     *
     * @param Varien_Object $payment
     * @return string
     */
    protected function _getParentTransactionId(Varien_Object $payment)
    {
    	return $payment->getParentTransactionId() ? $payment->getParentTransactionId() : $payment->getLastTransId();
    }
    
    /**
     * Prepare request to gateway
     *
     * @param Mage_Payment_Model_Info $payment
     * @return Krp_Eximbay_Model_Request
     */
    protected function _buildRequest(Varien_Object $payment)
    {
    	$order = $payment->getOrder();
    
    	$order_id = $order->getRealOrderId();
    	$secretKey = Mage::getStoreConfig('payment/'.$this->getPaymentMethodCode().'/secret_key');
    	$mid = Mage::getStoreConfig('payment/'.$this->getPaymentMethodCode().'/mid');
    	$ref = $order_id;
    	$cur = $order->getBaseCurrencyCode();
    	$amt = round($this->getOrder()->getGrandTotal(), 2);
    	if($cur == 'KRW' || $cur == 'JPY' || $cur == 'VND')
    	{
    		$amt = round($this->getOrder()->getGrandTotal(), 0, PHP_ROUND_HALF_UP);
    	}
    	
    	$linkBuf = $secretKey. "?mid=" . $mid ."&ref=" . $ref ."&cur=" .$cur ."&amt=" .$amt;
    	
    	//$fgkey = md5($linkBuf);
    	$fgkey = hash("sha256", $linkBuf);
    	
    	$txntype = 'REFUND';
    	
    	$params = array(
    			'ver'      				=> '210',
    			'mid'      				=> $mid,
    			'txntype'      			=> $txntype,
    			'refundtype'      		=> $payment->getRefundType(),
    			'charset'	      		=> 'UTF-8',
    			'ref'             		=> $ref,
    			'fgkey'            		=> $fgkey,
    			'lang'              	=> $this->getLocale(),
    			'amt'                	=> $amt,
    			'cur'              		=> $cur,
    			'refundamt'				=> $payment->getRefundAmt(),
    			'transid'				=> $payment->getEximbayTransId(),
    			'reason'				=> 'Merchant Request' 			
    	);
    	    	
    	Mage::log($params, null, 'eximbay'.Mage::getModel('core/date')->date('Y-m-d').'.log');
    	
    	return $params;
    }
    
    /**
     * Post request to gateway and return responce
     *
     * @param Krp_Eximbay_Model_Request $request)
     * @return Krp_Eximbay_Model_Result
     */
    protected function _postRequest(array $request)
    {
    	$client = new Varien_Http_Client();
    	$client->setUri($this->getRefundUrl());
    	
    	Mage::log("RequestURL : ".$this->getRefundUrl(), null, 'eximbay'.Mage::getModel('core/date')->date('Y-m-d').'.log');
    	
    	$client->setConfig(array(
    			'maxredirects'=>0,
    			'timeout'=>30,
    			//'ssltransport' => 'tcp',
    	));

    	$client->setParameterPost($request);
    	$client->setMethod(Zend_Http_Client::POST);
    	
    	$result = array();
    	try {
    		$response = $client->request();
    	} catch (Exception $e) {
    		$result['rescode'] = $e->getCode();
    		$result['resmsg'] = $e->getMessage();

    		Mage::throwException(Mage::helper('eximbay')->__('Gateway error: %s', ($e->getMessage())));
    	}
    
    	$responseBody = trim($response->getBody());
    	Mage::log("Raw Response Data : ".$responseBody, null, 'eximbay'.Mage::getModel('core/date')->date('Y-m-d').'.log');

    	Mage::log("------------- Mapped Response -------------", null, 'eximbay'.Mage::getModel('core/date')->date('Y-m-d').'.log');
    	parse_str($responseBody, $data);
    	foreach ($data as $key => $value) {
    		$result[$key] = $value;
    		Mage::log($key." : ".$result[$key], null, 'eximbay'.Mage::getModel('core/date')->date('Y-m-d').'.log');
    	}
    	
    	if (empty($result['rescode'])) {
    		Mage::throwException(Mage::helper('eximbay')->__('Error in payment gateway.'));
    	}
    
    	return $result;
    }
    
    
    /**
     * Detect Mobile Device
     *
     * @return boolean
     */
    public function isMobile()
    {    	
    	
    	Mage::log("ISMOBILE : ".$_SERVER['HTTP_USER_AGENT'], null, 'eximbay'.Mage::getModel('core/date')->date('Y-m-d').'.log');
    	
    	$regex_match = "/(nokia|iphone|android|motorola|^mot\-|softbank|foma|docomo|kddi|up\.browser|up\.link|"  
	                 . "htc|dopod|blazer|netfront|helio|hosin|huawei|novarra|CoolPad|webos|techfaith|palmsource|"  
	                 . "blackberry|alcatel|amoi|ktouch|nexian|samsung|^sam\-|s[cg]h|^lge|ericsson|philips|sagem|wellcom|bunjalloo|maui|"  
	                 . "symbian|smartphone|mmp|midp|wap|phone|windows ce|iemobile|^spice|^bird|^zte\-|longcos|pantech|gionee|^sie\-|portalmmm|"  
	                 . "jig\s browser|hiptop|^ucweb|^benq|haier|^lct|opera\s*mobi|opera\*mini|320x320|240x320|176x220"  
	                 . ")/i";  
	
	    if (preg_match($regex_match, strtolower($_SERVER['HTTP_USER_AGENT']))) {  
	        return TRUE;  
	    }  
	
	    if ((strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml') > 0) or ((isset($_SERVER['HTTP_X_WAP_PROFILE']) or isset($_SERVER['HTTP_PROFILE'])))) {  
	        return TRUE;  
	    }      
	
	    $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'], 0, 4));  
	    $mobile_agents = array(  
	        'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',  
	        'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',  
	        'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',  
	        'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',  
	        'newt','noki','oper','palm','pana','pant','phil','play','port','prox',  
	        'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',  
	        'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',  
	        'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',  
	        'wapr','webc','winw','winw','xda ','xda-');  
	
	    if (in_array($mobile_ua,$mobile_agents)) {  
	        return TRUE;  
	    }  
	
	    if (isset($_SERVER['ALL_HTTP']) && strpos(strtolower($_SERVER['ALL_HTTP']),'OperaMini') > 0) {  
	        return TRUE;  
	    }  
	
	    return FALSE;
    }
    
    /**
     * Return url of payment method
     *
     * @return string
     */
    public function getUrl()
    {
		if($this->isTestMode()){
			return 'https://secureapi.test.eximbay.com/Gateway/BasicProcessor.krp';
		}else{
			return 'https://secureapi.eximbay.com/Gateway/BasicProcessor.krp';
		}
    }
    
    /**
     * Return url of refund method
     *
     * @return string
     */
    public function getRefundUrl()
    {
    	if($this->isTestMode()){
    		return 'https://secureapi.test.eximbay.com/Gateway/DirectProcessor.krp';
   		}else{
   			return 'https://secureapi.eximbay.com/Gateway/DirectProcessor.krp';
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
     * Retrieve payment method code
     *
     * @return string
     */
    public function getPaymentMethodCode()
    {
    	return $this->getOrder()->getPayment()->getMethodInstance()->getCode();
    }
    
    /**
     * Return display type of payment method
     *
     * @return string
     */
    public function getDisplayType()
    {
    	$diplay_mode = Mage::getStoreConfig('payment/'.$this->getPaymentMethodCode().'/dtype');
    	return $diplay_mode;
    }
    
    
    /**
     * checks if Korean Local Payment is chosen. 
     *
     * @return string
     */
    public function isKoreanLocalPayment()
    {
    	$localpayment = Mage::getStoreConfig('payment/'.$this->getPaymentMethodCode().'/localpayment');
    	return $localpayment;
    }
    
    /**
     * Return working mode (test or production)
     *
     * @return string
     */
    public function isTestMode()
    {
    	$mode = Mage::getStoreConfig('payment/'.$this->getPaymentMethodCode().'/test');
    	return $mode;
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
        $secretKey = Mage::getStoreConfig('payment/'.$this->getPaymentMethodCode().'/secret_key');
        $mid = Mage::getStoreConfig('payment/'.$this->getPaymentMethodCode().'/mid');
		$ref = $order_id;
		$displayType = $this->getDisplayType();
		$cur = $this->getOrder()->getOrderCurrencyCode();
		if($cur == 'KRW' || $cur == 'JPY' || $cur == 'VND')
		{
			$amt = round($this->getOrder()->getGrandTotal(), 0, PHP_ROUND_HALF_UP);
		}
		$linkBuf = $secretKey. "?mid=" . $mid ."&ref=" . $ref ."&cur=" .$cur ."&amt=" .$amt;
		
		$fgkey = hash("sha256", $linkBuf);

		$ostype = 'P';
		$issuercountry = '';
		if($this->isMobile()){
			$ostype = 'M';
		}
		if($this->isKoreanLocalPayment()){
			$issuercountry = 'KR';
		}

		
		$params = array(
			'ver'      				=> '210',
            'mid'      				=> $mid,
			'txntype'      			=> 'PAYMENT',
			'displaytype'      		=> $displayType,
			'charset'	      		=> 'UTF-8',
            'ref'             		=> $ref,
            'email'          		=> $email,
            'returnurl'             => Mage::getUrl('eximbay/processing/success'), 
			'statusurl'             => Mage::getUrl('eximbay/processing/status'),
            'fgkey'            		=> $fgkey,
            'lang'              	=> $this->getLocale(),
            'amt'                	=> $amt,
            'cur'              		=> $cur,
            'shop'					=> Mage::app()->getStore()->getName(),
            'buyer'             	=> $billing->getFirstname() . ' ' . $billing->getLastname(),  
            'tel'          			=> $billing->getTelephone(),
			//'param1'          		=> '',
			//'param2'          		=> '',
			//'param3'          		=> '',
			//'title1'          		=> '',
			//'title2'          		=> '',
			//'title3'          		=> '',
			//'title4'          		=> '',
			//'visitorid'				=> '',
			//'partnercode'				=> '',
			'autoclose'				=> 'Y',
			//'directToReturn'		=> 'N',
			'ostype'				=> $ostype,
			'issuercountry'    		=> $issuercountry,
			'paymethod'       			=> $this->_paymentMethod,
			'billTo_city'				=> $billing->getCity(),
			'billTo_country'			=> $billing->getCountry_id(),
			'billTo_firstName'			=> $billing->getFirstname(),
			'billTo_lastName'			=> $billing->getLastname(),
			'billTo_phoneNumber'		=> $billing->getTelephone(),
			'billTo_postalCode'			=> $billing->getPostcode(),
			'billTo_state'				=> $billing->getRegionCode(),
			'billTo_street1'			=> $billing->getStreet(1),
			'billTo_street2'			=> $billing->getStreet(2),
			'shipTo_city'				=> $shipping->getCity(),
			'shipTo_country'			=> $shipping->getCountry_id(),
			'shipTo_firstName'			=> $shipping->getFirstname(),
			'shipTo_lastName'			=> $shipping->getLastname(),
			'shipTo_phoneNumber'		=> $shipping->getTelephone(),
			'shipTo_postalCode'			=> $shipping->getPostcode(),
			'shipTo_state'				=> $shipping->getRegionCode(),
			'shipTo_street1'			=> $shipping->getStreet(1),
			'shipTo_street2'			=> $shipping->getStreet(2),
        );
		
		//get item units
		$order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
		$items = $order->getAllVisibleItems();
		$itemcount=count($items);
		$item_loop = 0;
		if ( $itemcount > 0 ) {
			foreach ($items as $itemId => $item)
			{
				$params['item_'.$item_loop.'_product'] = $item->getName();
				$params['item_'.$item_loop.'_unitPrice'] = number_format($item->getPrice(), 2, '.', '');
				$params['item_'.$item_loop.'_quantity'] = $item->getQtyToInvoice();
				
				$item_loop++;
			}
		}
		
		Mage::log($params, null, 'eximbay'.Mage::getModel('core/date')->date('Y-m-d').'.log');
		
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
