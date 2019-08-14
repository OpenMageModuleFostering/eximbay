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
class Mage_Eximbay_Model_Acc extends Mage_Eximbay_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'eximbay_acc';
    protected $_paymentMethod		= 'ACC';
}
