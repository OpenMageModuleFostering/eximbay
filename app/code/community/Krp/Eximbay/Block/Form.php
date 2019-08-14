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

class Krp_Eximbay_Block_Form extends Mage_Payment_Block_Form
{
    /**
     * Available locales for content URL generation
     *
     * @var array
     */
    protected $_supportedInfoLocales = array('zh', 'en', 'ja', 'ko');

    /**
     * Default locale for content URL generation
     *
     * @var string
     */
    protected $_defaultInfoLocale = 'EN';

    /**
     * Constructor. Set template.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('eximbay/form.phtml');
    }

    /**
     * Return payment logo image src
     *
     * @param string $payment Payment Code
     * @return string|bool
     */
    public function getPaymentImageSrc($payment)
    {
        /*$imageFilename = Mage::getDesign()
            ->getFilename('images' . DS . 'eximbay' . DS . $payment, array('_type' => 'skin'));

        if (file_exists($imageFilename . '.jpg')) {
            return $this->getSkinUrl('images/eximbay/' . $payment . '.jpg');
        } else if (file_exists($imageFilename . '.png')) {
            return $this->getSkinUrl('images/eximbay/' . $payment . '.png');
        } else if (file_exists($imageFilename . '.gif')) {
            return $this->getSkinUrl('images/eximbay/' . $payment . '.gif');
        }*/
    	
    	return $this->getSkinUrl('images/eximbay/eximbay_banner.png');

        //return false;
    }

    /**
     * Return supported locale for information text
     *
     * @return string
     */
    public function getInfoLocale()
    {
        $locale = substr(Mage::app()->getLocale()->getLocaleCode(), 0 ,2);
        if (in_array($locale, $this->_supportedInfoLocales)) {
        	if($locale == 'ko'){
        		return 'KR';
        	}else if($locale == 'zh'){
        		return 'CN';
        	}else if($locale == 'ja'){
        		return 'JP';
        	}
        }
        $locale = $this->_defaultInfoLocale;
        
        return $locale;
    }
    
    /**
     * Return info URL for Eximbay payment
     *
     * @return string
     */
    public function getEximbayInfoUrl()
    {
    	$locale = $this->getInfoLocale();
    	return 'https://www.eximbay.com/index.do?lang_sel=' . $locale;
    }
}
