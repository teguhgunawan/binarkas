<?php

return [
    'name' => env_value('APP_NAME', 'BINARKAS'),
    'base_path' => env_value('APP_BASE_PATH', '/binarkas'),
    'env' => env_value('APP_ENV', 'development'),
    'currency' => 'IDR',
    'auth' => [
        'admin_name' => env_value('APP_ADMIN_NAME', 'BINARKAS Admin'),
        'admin_email' => env_value('APP_ADMIN_EMAIL', 'admin@binarkas.local'),
        'admin_password' => env_value('APP_ADMIN_PASSWORD', 'admin123'),
    ],
];
