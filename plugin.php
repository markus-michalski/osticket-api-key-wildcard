<?php

return [
    'id' => 'com.github.osticket:api-key-wildcard',
    'version' =>        '2.0.1',
    'name' => 'API Key Wildcard Support',
    'author' => 'osTicket Community',
    'description' => 'Allows API keys with IP address 0.0.0.0 to accept requests from any IP address. Use only in development environments!',
    'url' => 'https://github.com/markus-michalski/osticket-api-key-wildcard',
    'plugin' => 'class.ApiKeyWildcard.php:ApiKeyWildcardPlugin'
];
