<?php

/**
 * Easyname REST API client sample usage.
 *
 * @category   Easyname
 * @copyright  Copyright 2006-present Nessus GmbH (http://www.nessus.at)
 */

require_once 'src/Easyname.php';

$result = null;
$api = new Easyname();

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
