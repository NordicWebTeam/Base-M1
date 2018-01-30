<?php

class NWT_Base_Model_Tracker_Collect extends Mage_Core_Model_Abstract
{
    protected $_additionalStoreInfoKeys = [
        'name' => 'general/store_information/name',
        "url" => 'web/unsecure/base_url',
        "email" => 'trans_email/ident_general/email',
        "locale" => 'general/locale/code',
        'street_line1' => 'general/store_information/address',
        'vat_number' => 'general/store_information/merchant_vat_number',
        'country_id' => 'general/store_information/merchant_country',
        'phone' => 'general/store_information/phone',
    ];

    /**
     * @return array
     */
    public function getAllInfo()
    {
        Mage::app()->getConfig()->reinit();

        return array_merge(
            $this->getStoreInfo(),
            ['urls' => $this->_getAllStoresBaseUrls()],
            ['modules' => $this->getNWTModulesInfo()],
            ['host' => gethostbyname(gethostname())]
        );
    }

    /**
     * @return array
     */
    public function getStoreInfo()
    {
        $storeInfo = [];
        foreach ($this->_additionalStoreInfoKeys as $key => $value) {
            $configValue = Mage::getStoreConfig($value);
            $storeInfo['store'][$key] = $key == 'street_line1' ?
                preg_replace('~[\r\n]+~', '', nl2br($configValue)) :
                $configValue;
        }

        $storeInfo['magento_edition'] = Mage::getEdition();
        $storeInfo['magento_version'] = Mage::getVersion();
        $storeInfo['description'] = Mage::getStoreConfig('design/head/default_description');

        return $storeInfo;
    }

    /**
     * Get list of all non core Magento modules
     *
     * @return array
     */
    public function getNWTModulesInfo()
    {
        $allModules = Mage::getConfig()->getNode('modules')->children();

        $list = [];
        foreach ($allModules as $moduleName => $moduleSettings) {
            if (preg_match('#^NWT#', $moduleName) === 1) {
                $item = [
                    'name' => $moduleName,
                    'enabled' => (bool)$moduleSettings->active,
                    'is_output_enabled' => (bool)!Mage::getStoreConfig(
                        'advanced/modules_disable_output/' . $moduleName
                    ),
                    'setup_version' => (string)$moduleSettings->version,
                ];

                if ($moduleName === 'NWT_KCO') {
                    $item['test_mode'] = (bool)Mage::getStoreConfig('nwtkco/settings/test_mode');
                    $item['eid'] = Mage::getStoreConfig('nwtkco/settings/eid');
                }
                else if ($moduleName === 'NWT_Unifaun') {
                    $item['test_mode'] = (bool)Mage::getStoreConfig('nwt_unifaun/connection/test_mode');
                    $item['eid'] = Mage::getStoreConfig('nwt_unifaun/tracking/username');
                }
                else if ($moduleName === 'NWT_Specter') {
                    $item['test_mode'] = (bool)Mage::getStoreConfig('nordincwebteam_specter/main/enabled');
                    $item['eid'] = Mage::getStoreConfig('nordincwebteam_specter/main/specter_smbid');
                }

                $list[] = $item;
            }
        }

        return $list;
    }

    /**
     * @return array
     */
    private function _getAllStoresBaseUrls()
    {
        static $urls;
        if (!isset($urls)) {
            foreach (Mage::app()->getStores() as $store) {
                $urls[] = [
                    'store_code' => $store->getCode(),
                    'store_base_url' => $store->getBaseUrl()
                ];
            }
        }

        return $urls;
    }
}