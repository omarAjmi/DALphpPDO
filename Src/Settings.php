<?php
return [
    'mysql' => [ //the default pool (driver)
        'driver' => 'mysql',
        'host' => 'localhost',
        'dbname' => 'mydatabase1',
        'user' => 'myusername1',
        'password' => 'mypassword',
        'prefix' => 'DB1_',
        'port' => 3306,
        'persistent' => 1,
        'fetchmode' => 'object',
        'prepare' => 1
    ],
    'pgsql' => [
        'driver' => 'pgsql',
        'host' => 'localhost2',
        'dbname' => 'mydatabase2',
        'user' => 'myusername2',
        'password' => 'mypassword',
        'port' => 5432
    ]
];