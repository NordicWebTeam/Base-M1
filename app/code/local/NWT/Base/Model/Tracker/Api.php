<?php

class NWT_Base_Model_Tracker_Api extends Mage_Core_Model_Abstract
{
    const NWT_REST_ENDPOINT = 'https://tracker.nordicwebteam.se/api/v1/';
    const CONFIG_DATA_PROJECT_ID = 'nwt_base/tracker/setup_id';

    /**
     * @return bool
     */
    public function createLog()
    {
        Mage::app()->getConfig()->reinit();
        $setupId = Mage::getStoreConfig(self::CONFIG_DATA_PROJECT_ID);

        $client = $this->_getClient();
        try {
            $client->setRawData(json_encode([
                'project_log' => array_merge(
                    Mage::getModel('nwt/tracker_collect')->getAllInfo(),
                    ['setup_id' => $setupId]
                )
            ]));

            $response = $client->request('POST');
            if (($requestBody = json_decode($response->getBody())) && $requestBody->id) {
                if (empty($setupId)) {
                    Mage::getConfig()->saveConfig(self::CONFIG_DATA_PROJECT_ID, $requestBody->id);
                }
                return $requestBody->id;
            } else {
                Mage::log("Tracker api create Log error. Request body: " . $requestBody, Zend_Log::ERR);
            }
        } catch (\Exception $e) {
            Mage::log("Exception while creating NWT Api tracker id. " . $e->getMessage(), Zend_Log::ERR);
        }

        return false;
    }

    /**
     * @param string $controller
     * @return mixed|Zend_Http_Client
     */
    private function _getClient($controller = 'project_logs')
    {
        if (!isset($this->client))
        {
            $client = new Zend_Http_Client();
            $client->setAdapter(new Zend_Http_Client_Adapter_Curl());
            $client->setUri(self::NWT_REST_ENDPOINT . $controller);
            $client->setHeaders(['Content-Type: application/json']);

            $this->client = $client;
        }

        return $this->client;
    }
}