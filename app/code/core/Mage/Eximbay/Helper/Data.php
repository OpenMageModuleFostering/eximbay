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
class Mage_Eximbay_Helper_Data extends Mage_Payment_Helper_Data
{
    const XML_PATH_EMAIL        = '';
    const XML_PATH_MERCHANT_ID  = '';
    const XML_PATH_SECRET_KEY   = '';


	// Include your code to send activation email 
	
    /**
     * Send activation Email
     */
     
     /*
    public function activateEmail()
    {
        $storeId = Mage::app()->getStore()->getId();

        $translate = Mage::getSingleton('core/translate');
        // @var $translate Mage_Core_Model_Translate 
        $translate->setTranslateInline(false);

        Mage::getModel('core/email_template')
            ->setDesignConfig(array('area' => 'frontend', 'store' => $storeId))
            ->sendTransactional(
                'eximbay_activateemail',
                Mage::getStoreConfig(Mage_Sales_Model_Order::XML_PATH_EMAIL_IDENTITY, $storeId),
                $this->_activationEmailTo,
                null,
                array(
                    'subject'     => $this->_activationEmailSubject,
                    'email_addr'  => Mage::getStoreConfig(self::XML_PATH_EMAIL),
                    'url'         => Mage::getBaseUrl(),
                    'merchant_id' => Mage::getStoreConfig(self::XML_PATH_MERCHANT_ID),
                    'language'    => Mage::getModel('core/locale')->getDefaultLocale()
                )
            );

        $translate->setTranslateInline(true);
    }*/

    /**
     * Check if email is registered at eximbay
     *
     * @param array $params
     * @return array
     */
     
     /*
    public function checkEmailRequest(Array $params) {
        $response = null;
        try {
            $response = $this->_getHttpsPage($this->_eximbayServer . $this->_checkEmailUrl, array(
                'email'    => $params['email'],
                'cust_id'  => $this->_checkEmailCustId,
                'password' => $this->_checkEmailPassword)
            );
        } catch (Exception $e) {
            Mage::log($e->getMessage());
            return null;
        }
        return $response;
    }*/

    /**
     * Check if entered secret is valid
     * @param array $params
     * @return array
     */
     /*
    public function checkSecretRequest(Array $params)
    {
        $response = null;
        try {
            $response = $this->_getHttpsPage($this->_eximbayServer . $this->_checkSecretUrl, array(
                'email'   => $params['email'],
				'secret'  => hash("sha256", hash("sha256", $params['secret']) . $this->_eximbayMasterSecretHash)),
                'cust_id' => $this->_eximbayMasterCustId)
            );
        } catch (Exception $e) {
            Mage::log($e->getMessage());
            return null;
        }
        return $response;
    }*/

    /**
     * Reading a page via HTTPS and returning its content.
     */
     /*
    protected function _getHttpsPage($host, $parameter)
    {
        $client = new Varien_Http_Client();
        $client->setUri($host)
            ->setConfig(array('timeout' => 30))
            ->setHeaders('accept-encoding', '')
            ->setParameterGet($parameter)
            ->setMethod(Zend_Http_Client::GET);
        $request = $client->request();
        // Workaround for pseudo chunked messages which are yet too short, so
        // only an exception is is thrown instead of returning raw body
        if (!preg_match("/^([\da-fA-F]+)[^\r\n]*\r\n/sm", $request->getRawBody(), $m))
            return $request->getRawBody();

        return $request->getBody();
    }*/
}
