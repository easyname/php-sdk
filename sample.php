<?php
/**
 * Easyname REST API client sample usage.
 *
 * @copyright  2006-2016 easyname GmbH (http://www.easyname.com)
 * @license    easyname License Agreement
 */

ini_set('date.timezone', 'UTC');

require_once 'src/Easyname/RestApi/Client.php';
require_once 'src/Easyname/RestApi/Exception.php';

$result = null;
$api = new \Easyname\RestApi\Client(
    include 'config/sample.config.php'
);

/*
 * Domain
 */
$result = $api->listDomainPrice();
//$result = $api->listDomain();
//$result = $api->getDomain(1);
//$result = $api->createDomain("example.com", 23, 23, 23, 23, array(), false);
//$result = $api->transferDomain("example.com", 23, 23, 23, 23, array(), false, 'aaaaaa');
//$result = $api->changeOwnerOfDomain(1, 23);
//$result = $api->changeContactOfDomain(1, 23, 23, 23);
//$result = $api->changeNameserverOfDomain(1, array('ns1.example.com', 'ns2.example.com'));
//$result = $api->expireDomain(1);
//$result = $api->unexpireDomain(1);
//$result = $api->deleteDomain(1);
//$result = $api->restoreDomain(1);

/*
 * Contact
 */
//$result = $api->listContact();
//$result = $api->getContact(1);
//$result = $api->createContact('person', 'John Doe (person)', 'John Doe', 'Street 12/34', '1234', 'Vienna', 'AT', '+4312345678', 'me@example.com', array('birthday' => '1970-01-31'));
//$result = $api->updateContact(1, 'John Doe (person)', 'Other Street 56/7', '1234', 'Vienna', '+4312345678', 'me@example.com', array('birthplaceCity' => 'Vienna'));
//$result = $api->deleteContact(1);

echo "<pre>\n";
var_dump($result);
