<?php

$this->startSetup ();
Mage::getModel('nwt/tracker_api')->createLog();
$this->endSetup ();
