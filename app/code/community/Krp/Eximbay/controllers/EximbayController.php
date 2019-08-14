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
 * @package     Krp_Eximbay
 * @copyright   Copyright (c) 2014 KRPartners Co.,Ltd (https://www.eximbay.com)
 * @license     http://opensource.org/licenses/GPL-3.0  GNU General Public License (GPL 3.0)
 */
class Krp_Eximbay_EximbayController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Retrieve Eximbay's helper
     *
     * @return Krp_Eximbay_Helper_Data
     */
/*  protected function _getHelper()
    {
        return Mage::helper('eximbay');
    }
*/
    /**
     * Send activation Email
     */
/*    public function activateemailAction()
    {
        $this->_getHelper()->activateEmail();
    }
*/
    /**
     * Check if email is registered
     */
/*    public function checkemailAction()
    {
        try {
            $params = $this->getRequest()->getParams();
            if (empty($params['email'])) {
                Mage::throwException('Error: No parameters specified');
            }
            $response =  $this->_getHelper()->checkEmailRequest($params);
            if (empty($response)) {
                Mage::throwException('Error: Connection to eximbay.com failed');
            }
            $this->getResponse()->setBody($response);
            return;
        } catch (Mage_Core_Exception $e) {
            $response = $e->getMessage();
        } catch (Exception $e) {
            $response = 'Error: System error during request';
        }
        $this->getResponse()->setBody($response);
    }
*/
    /**
     * Check if entered secret is valid
     */
 /*   public function checksecretAction()
    {
        try {
            $params = $this->getRequest()->getParams();
            if (empty($params['email']) || empty($params['secret'])) {
                 Mage::throwException('Error: No parameters specified');
            }
            $response =  $this->_getHelper()->checkSecretRequest($params);
            if (empty($response)) {
                Mage::throwException('Error: Connection to eximbay.com failed');
            }
            $this->getResponse()->setBody($response);
            return;
        } catch (Mage_Core_Exception $e) {
            $response = $e->getMessage();
        } catch (Exception $e) {
            $response = 'Error: System error during request';
        }
        $this->getResponse()->setBody($response);
    }
*/
}
