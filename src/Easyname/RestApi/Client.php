<?php
/**
 * @category   Easyname\RestApi
 * @package    Easyname\RestApi
 * @copyright  2006-2016 easyname GmbH (http://www.easyname.com)
 * @license    easyname License Agreement
 */
namespace Easyname\RestApi;

/**
 * Easyname REST API client.
 *
 * @category   Easyname\RestApi
 * @package    Easyname\RestApi
 * @copyright  2006-2016 easyname GmbH (http://www.easyname.com)
 */
class Client
{
    const POST = 'POST';
    const GET = 'GET';
    const DELETE = 'DELETE';

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $apiAuthenticationSalt;

    /**
     * @var string
     */
    private $apiSigningSalt;

    /**
     * @var string
     */
    private $userId;

    /**
     * @var string
     */
    private $userEmail;

    /**
     * @var bool
     */
    private $debug = false;

    /**
     * @var string
     */
    private $xdebugKey;

    /**
     * Constructor loads all neccessary information from the yaml config file.
     */
    public function __construct(array $config = array())
    {
        if (!function_exists('curl_init')) {
            throw new Exception('CURL extension required');
        }

        if (!$config) {
            throw new Exception('No config given.');
        }

        $this->setConfig($config);
    }

    private function setConfig(array $config)
    {
        foreach ($config as $key => $c) {
            if (is_array($c)) {
                foreach ($c as $subKey => $subC) {
                    if (strpos($subKey, '-') !== false) {
                        $tmp = explode('-', $subKey);
                        array_walk($tmp, function (&$item, $ignore) {
                            $item = ucfirst($item);
                        });
                        $subKey = implode('', $tmp);
                    }

                    $method = 'set' . ucfirst($key) . ucfirst($subKey);
                    if (method_exists($this, $method)) {
                        $this->{$method}($subC);
                    }
                }
            } else {
                $method = 'set' . ucfirst($key);
                if (method_exists($this, $method)) {
                    $this->{$method}($c);
                }
            }
        }
    }

    /**
     * @param string $type
     * @param string $resource
     * @param null|int $id
     * @param null $subResource
     * @param null $subId
     * @param array|null $data
     * @param null|string $perform
     * @param null|int $limit
     * @param null|int $offset
     * @param null|int|array $filter
     * @return array
     */
    private function doRequest($type, $resource, $id = null, $subResource = null, $subId = null, array $data = null, $perform = null, $limit = null, $offset = null, $filter = null)
    {
        $uri = '/' . $resource;
        if ($id) {
            $uri .= '/' . ((int)$id);
        }

        if ($subResource) {
            $uri .= '/' . $subResource;
        }

        if ($subId) {
            $uri .= '/' . ((int)$subId);
        }

        if ($perform) {
            $uri .= '/' . $perform;
        }

        $uriParameters = array();

        if ($type === self::GET) {
            if ($offset !== null) {
                $uriParameters['offset'] = (int)$offset;
            }

            if ($limit !== null) {
                $uriParameters['limit'] = (int)$limit;
            }

            if ($filter !== null) {
                if (is_array($filter)) {
                    $uriParameters['filter'] = implode(',',$filter);
                } else {
                    $uriParameters['filter'] = (int)$filter;
                }
            }
        }

        if ($this->debuggingEnabled() && $this->getXdebugKey()) {
            $uriParameters['XDEBUG_SESSION_START'] = $this->getXdebugKey();
        }

        if ($uriParameters) {
            $uri .= '?' . http_build_query($uriParameters);
        }

        $url = $this->getUrl() . $uri;
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $type);
        curl_setopt($curl, CURLOPT_HTTPHEADER,
            array(
                'X-User-ApiKey:' . $this->getApiKey(),
                'X-User-Authentication:' . $this->createApiAuthentication(),
                'Accept:application/json',
                'Content-Type: application/json',
                'X-Readable-JSON:' . ((int)$this->debuggingEnabled())
            )
        );
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);

        if ($type === self::POST) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $this->createBody($data));
        }

        $this->debug($type . ': ' . $url);

        $response = curl_exec($curl);

        curl_close($curl);

        $this->debug($response);

        return json_decode($response, true);
    }

    /**
     * @return string
     */
    private function createApiAuthentication()
    {
        $authentication = base64_encode(
            md5(
                sprintf($this->getApiAuthenticationSalt(), $this->getUserId(), $this->getUserEmail())
            )
        );

        $this->debug($authentication);

        return $authentication;
    }

    /**
     * @param array $data
     * @return string Urlencoded string
     */
    private function createBody(array $data = null)
    {
        if (!$data) {
            $data = array();
        }
        $timestamp = time();
        $body = array(
            'data' => $data,
            'timestamp' => $timestamp,
            'signature' => $this->signRequest($data, $timestamp)
        );

        $this->debug($body);

        return urlencode(json_encode($body));
    }

    /**
     * @param array $data
     * @param int $timestamp
     * @return string
     */
    private function signRequest(array $data, $timestamp)
    {
        $keys = array_merge(array_keys($data), array('timestamp'));
        sort($keys);

        $string = '';
        foreach($keys as $key) {
            if ($key !== 'timestamp') {
                $string .= (string)$data[$key];
            } else {
                $string .= (string)$timestamp;
            }
        }

        $length = strlen($string);
        $length = $length%2 == 0 ? (int)($length/2) : (int)($length/2)+1;
        $strings = str_split($string, $length);

        $signature = base64_encode(md5($strings[0] . $this->getApiSigningSalt() . $strings[1]));

        $this->debug($signature);

        return $signature;
    }

    /**
     * @param mixed $data
     */
    private function debug($data)
    {
        if ($this->debuggingEnabled()) {
            $backtrace = debug_backtrace();
            echo date('Y-m-d H:i:s') . ' - ' . $backtrace[1]['function'] . ': ';
            if (is_array($data)) {
                echo print_r($data, true) . "\n";
            } else {
                echo $data . "\n";
            }
        }
    }


    /**
     * DOMAIN
     */

    /**
     * Fetch information about a single domain.
     *
     * @param int $id
     * @return array
     */
    public function getDomain($id)
    {
        return $this->doRequest(self::GET, 'domain', $id);
    }

    /**
     * List all active domains.
     *
     * @param null|int $limit
     * @param null|int $offset
     * @param null|int|array $filter
     * @return array
     */
    public function listDomain($limit = null, $offset = null, $filter = null)
    {
        return $this->doRequest(self::GET, 'domain', null, null, null, null, null, $limit, $offset, $filter);
    }

    /**
     * List Domain Price (Static)
     *
     * @param null|int $limit
     * @param null|int $offset
     * @param null|int|array $filter
     * @return array
     */
    public function listDomainPrice($limit = null, $offset = null, $filter = null)
    {
        // todo: Feature-Request - Easyname TICKET #522293
        return $this->getDomainPrice();
    }

    /**
     * Register a new domain name.
     *
     * @param string $domain
     * @param int $registrantContact
     * @param int $adminContact
     * @param int $techContact
     * @param int $zoneContact
     * @param array $nameservers
     * @param bool $trustee
     * @return array
     */
    public function createDomain($domain, $registrantContact, $adminContact, $techContact, $zoneContact, $nameservers = array(), $trustee = false)
    {
        $tmpNameservers = array();
        for ($i = 0; $i < 6; $i++) {
            if ($nameservers[$i]) {
                $tmpNameservers['nameserver' . ($i+1)] = $nameservers[$i];
            }
        }

        return $this->doRequest(
            self::POST,
            'domain',
            null,
            null,
            null,
            array_merge(
                array(
                    'domain' => $domain,
                    'registrantContact' => $registrantContact,
                    'adminContact' => $adminContact,
                    'techContact' => $techContact,
                    'zoneContact' => $zoneContact,
                    'trustee' => ($trustee ? 1 : 0),
                    'transferIn' => 0
                ),
                $tmpNameservers
            )
        );
    }

    /**
     * Transfer an existing domain name.
     *
     * @param string $domain
     * @param int $registrantContact
     * @param int $adminContact
     * @param int $techContact
     * @param int $zoneContact
     * @param array $nameservers
     * @param bool $trustee
     * @param null $transferAuthcode
     * @return array
     */
    public function transferDomain($domain, $registrantContact, $adminContact, $techContact, $zoneContact, $nameservers = array(), $trustee = false, $transferAuthcode = null)
    {
        $tmpNameservers = array();
        for ($i = 0; $i < 6; $i++) {
            if ($nameservers[$i]) {
                $tmpNameservers['nameserver' . ($i+1)] = $nameservers[$i];
            }
        }

        $tmpTransferAuthcode = array();
        if ($transferAuthcode) {
            $tmpTransferAuthcode['transferAuthcode'] = $transferAuthcode;
        }

        return $this->doRequest(
            self::POST,
            'domain',
            null,
            null,
            null,
            array_merge(
                array(
                    'domain' => $domain,
                    'registrantContact' => $registrantContact,
                    'adminContact' => $adminContact,
                    'techContact' => $techContact,
                    'zoneContact' => $zoneContact,
                    'trustee' => ($trustee ? 1 : 0),
                    'transferIn' => 1
                ),
                $tmpNameservers,
                $tmpTransferAuthcode
            )
        );
    }

    /**
     * Delete a specific domain instantly.
     *
     * @param int $id
     * @return array
     */
    public function deleteDomain($id)
    {
        return $this->doRequest(self::POST, 'domain', $id, null, null, null, 'delete');
    }

    /**
     * Re-purchase a previously deleted domain.
     *
     * @param int $id
     * @return array
     */
    public function restoreDomain($id)
    {
        return $this->doRequest(self::POST, 'domain', $id, null, null, null, 'restore');
    }

    /**
     * Set an active domain to be deleted on expiration.
     *
     * @param int $id
     * @return array
     */
    public function expireDomain($id)
    {
        return $this->doRequest(self::POST, 'domain', $id, null, null, null, 'expire');
    }

    /**
     * Undo a previously commited expire command.
     *
     * @param int $id
     * @return array
     */
    public function unexpireDomain($id)
    {
        return $this->doRequest(self::POST, 'domain', $id, null, null, null, 'unexpire');
    }

    /**
     * Change the owner of an active domain.
     *
     * @param int $id
     * @param int $registrantContact
     * @return array
     */
    public function changeOwnerOfDomain($id, $registrantContact)
    {
        return $this->doRequest(self::POST, 'domain', $id, null, null, array('registrantContact' => $registrantContact), 'ownerchange');
    }

    /**
     * Change additional contacts of an active domain.
     *
     * @param int $id
     * @param int $adminContact
     * @param int $techContact
     * @param int $zoneContact
     * @return array
     */
    public function changeContactOfDomain($id, $adminContact, $techContact, $zoneContact)
    {
        return $this->doRequest(
            self::POST,
            'domain',
            $id,
            null,
            null,
            array(
                'adminContact' => $adminContact,
                'techContact' => $techContact,
                'zoneContact' => $zoneContact
            ),
            'contactchange'
        );
    }

    /**
     * Change the nameserver settings of a domain.
     *
     * @param int $id
     * @param array $nameservers
     * @return array
     */
    public function changeNameserverOfDomain($id, $nameservers = array())
    {
        $tmpNameservers = array();
        for ($i = 0; $i < 6; $i++) {
            if ($nameservers[$i]) {
                $tmpNameservers['nameserver' . ($i+1)] = $nameservers[$i];
            }
        }

        return $this->doRequest(
            self::POST,
            'domain',
            $id,
            null,
            null,
            $tmpNameservers,
            'nameserverchange'
        );
    }


    /**
     * CONTACT
     */

    /**
     * Fetch information about a contact.
     *
     * @param int $id
     * @return array
     */
    public function getContact($id)
    {
        return $this->doRequest(self::GET, 'contact', $id);
    }

    /**
     * List all contacts.
     *
     * @param int|null $limit
     * @param int|null $offset
     * @param null|int|array $filter
     * @return array
     */
    public function listContact($limit = null, $offset = null, $filter = null)
    {
        return $this->doRequest(self::GET, 'contact', null, null, null, null, null, $limit, $offset, $filter);
    }

    /**
     * Create a contact.
     *
     * @param string $type
     * @param string $alias
     * @param string $name
     * @param string $address
     * @param string $zip
     * @param string $city
     * @param string $country
     * @param string $phone
     * @param string $email
     * @param array|null $additionalData
     * @return array
     */
    public function createContact($type, $alias, $name, $address, $zip, $city, $country, $phone, $email, array $additionalData = array())
    {
        return $this->doRequest(
            self::POST,
            'contact',
            null,
            null,
            null,
            array_merge(
                array(
                    'type' => $type,
                    'alias' => $alias,
                    'name' => $name,
                    'address' => $address,
                    'zip' => $zip,
                    'city' => $city,
                    'country' => $country,
                    'phone' => $phone,
                    'email' => $email
                ),
                $additionalData
            )
        );
    }

    /**
     * Modify a specific contact.
     *
     * @param $id
     * @param $alias
     * @param $address
     * @param $zip
     * @param $city
     * @param $phone
     * @param $email
     * @param array $additionalData
     * @return array
     */
    public function updateContact($id, $alias, $address, $zip, $city, $phone, $email, array $additionalData = array())
    {
        return $this->doRequest(
            self::POST,
            'contact',
            $id,
            null,
            null,
            array_merge(
                array(
                    'alias' => $alias,
                    'address' => $address,
                    'zip' => $zip,
                    'city' => $city,
                    'phone' => $phone,
                    'email' => $email
                ),
                $additionalData
            )
        );
    }

    /**
     * Delete the specified contact
     *
     * @param int $id
     * @return array
     */
    public function deleteContact($id)
    {
        return $this->doRequest(
            self::DELETE,
            'contact',
            $id
        );
    }


    /**
     * @return array
     * @throws Exception
     */
    public function getUserBalance()
    {
        return $this->doRequest(
            self::GET,
            'user',
            $this->getUserId(),
            'balance',
            null
        );
    }

    /****************************************
     * GETTER AND SETTER FOR CLIENT
     */

    /**
     * @param string $apiAuthenticationSalt
     */
    public function setApiAuthenticationSalt($apiAuthenticationSalt)
    {
        $this->apiAuthenticationSalt = $apiAuthenticationSalt;
    }

    /**
     * @throws Exception
     * @return string
     */
    public function getApiAuthenticationSalt()
    {
        if (!$this->apiAuthenticationSalt) {
            throw new Exception('API authentication salt not set.');
        }
        return $this->apiAuthenticationSalt;
    }

    /**
     * @param string $userEmail
     */
    public function setUserEmail($userEmail)
    {
        $this->userEmail = $userEmail;
    }

    /**
     * @throws Exception
     * @return string
     */
    public function getUserEmail()
    {
        if (!$this->userEmail) {
            throw new Exception('User email not set.');
        }

        return $this->userEmail;
    }

    /**
     * @param string $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @throws Exception
     * @return string
     */
    public function getApiKey()
    {
        if (!$this->apiKey) {
            throw new Exception('API key not set.');
        }

        return $this->apiKey;
    }

    /**
     * @param string $apiSigningSalt
     */
    public function setApiSigningSalt($apiSigningSalt)
    {
        $this->apiSigningSalt = $apiSigningSalt;
    }

    /**
     * @throws Exception
     * @return string
     */
    public function getApiSigningSalt()
    {
        if (!$this->apiSigningSalt) {
            throw new Exception('API signing salt not set.');
        }

        return $this->apiSigningSalt;
    }

    /**
     * @param string $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @throws Exception
     * @return string
     */
    public function getUserId()
    {
        if (!$this->userId) {
            throw new Exception('User ID not set.');
        }

        return $this->userId;
    }

    /**
     * @param boolean $debug
     */
    public function setDebug($debug)
    {
        $this->debug = (bool)$debug;
    }

    /**
     * @return boolean
     */
    public function debuggingEnabled()
    {
        return $this->debug;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @throws Exception
     * @return string
     */
    public function getUrl()
    {
        if (!$this->url) {
            throw new Exception('Url not set.');
        }

        return $this->url;
    }

    /**
     * @param array $domainPrice
     */
    public function setDomainPrice($domainPrice)
    {
        $this->domainPrice = $domainPrice;
    }

    /**
     * @throws Exception
     * @return array
     */
    public function getDomainPrice()
    {
        if (!$this->domainPrice) {
            throw new Exception('domainPrice not set.');
        }

        return $this->domainPrice;
    }

    /**
     * @param string $xdebugKey
     */
    public function setXdebugKey($xdebugKey)
    {
        $this->xdebugKey = $xdebugKey;
    }

    /**
     * @return string
     */
    public function getXdebugKey()
    {
        return $this->xdebugKey;
    }
}
