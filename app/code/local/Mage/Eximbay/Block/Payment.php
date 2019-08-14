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
class Mage_Eximbay_Block_Payment extends Mage_Core_Block_Template
{
    /**
     * Return Payment logo src
     *
     * @return string
     */
    public function getEximbayLogoSrc()
    {
        $locale = Mage::getModel('eximbay/acc')->getLocale();
        $logoFilename = Mage::getDesign()
            ->getFilename('images' . DS . 'eximbay' . DS . 'banner_120_' . $locale . '.gif', array('_type' => 'skin'));

        return $this->getSkinUrl('images/eximbay/banner_120_int.gif');
    }
}
