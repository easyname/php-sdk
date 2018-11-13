<?php

return array(
    'url' => 'https://api.easyname.com',
    'user' => array(
        'id' => 0,
        'email' => 'foo@example.org'
    ),
    'api' => array(
        'key' => 'aaaaaaa',
        'authentication-salt' => 'aaaaaaaa%sbbbbbbbb%scccccccc',
        'signing-salt' => 'AaaBbbCccDddEeeFffGggHhh'
    ),
    'domain' => array(
        'Price' => (include 'sample.config.price.php'),
    ),
    'debug' => true,
);