<?php
/**
 * Easyname REST API client sample usage.
 *
 * @copyright  2006-2014 easyname GmbH (http://www.easyname.com)
 * @license    easyname License Agreement
 */

require_once 'src/Easyname/RestApi/Client.php';

$result = null;
$api = new \Easyname\RestApi\Client(
    include 'config/sample.config.php'
);

/*
 * Domain
 */
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
//$result = $api->createContact('person', 'John Doe (person)', 'John Doe', 'Street 12/34', '1234', 'Vienna', 'AT', '004312345678', 'me@example.com', array('birthday' => '1970-01-31'));
//$result = $api->updateContact(1, 'John Doe (person)', 'Other Street 56/7', '1234', 'Vienna', '004312345678', 'me@example.com', array('birthplaceCity' => 'Vienna'));

/*
 * DNS
 */
//$result = $api->listDns(1);
//$result = $api->getDns(1, 1);
//$result = $api->createDns(1, '*', 'A', '127.0.0.1');
//$result = $api->updateDns(1, 2, '*', 'A', '127.0.0.1');
//$result = $api->deleteDns(1, 2);

/*
 * FTP-account
 */
//$result = $api->listFtpAccount();
//$result = $api->getFtpAccount(1);

/*
 * Database
 */
//$result = $api->listDatabase();
//$result = $api->getDatabase(1);


var_dump($result);
