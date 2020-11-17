<?php

return [
    'connections' => [
			'mysql_gdpr' => [
				'driver' => 'mysql',
				'host' => env('DB_GDPR_PII_HOST', '127.0.0.1'),
				'port' => env('DB_GDPR_PII_PORT', '3306'),
				'database' => env('DB_GDPR_PII_DATABASE', 'gdpr'),
				'username' => env('DB_GDPR_PII_USERNAME', 'gdpr'),
				'password' => env('DB_GDPR_PII_PASSWORD', ''),
				'unix_socket' => env('DB_GDPR_PII_SOCKET', ''),
				'charset' => 'utf8',
				'collation' => 'utf8_general_ci',
				'prefix' => '',
				'strict' => true,
				'engine' => null,
        ],
    ]
];
