<?php

return [
    'driver' => env_value('DB_DRIVER', 'pgsql'),
    'host' => env_value('DB_HOST', '127.0.0.1'),
    'port' => env_value('DB_PORT', '5444'),
    'database' => env_value('DB_DATABASE', 'binarkas'),
    'username' => env_value('DB_USERNAME', 'admbinarkas'),
    'password' => env_value('DB_PASSWORD', '@B1n4rK4s'),
    'charset' => env_value('DB_CHARSET', 'utf8'),
    'schema' => env_value('DB_SCHEMA', 'public'),
];
