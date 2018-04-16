/**
 * @author Omar A.Ajmi
 * @email devdevoops@gmail.com
 * @create date 2018-04-15 19:54:19
 * @modify date 2018-04-16 04:19:42
 * @desc [description]
*/
<?php
return [
    'mysql' => [ //the default pool (driver)
        'driver' => 'mysql',
        'host' => 'localhost',
        'dbname' => 'sys',
        'user' => 'root',
        'password' => 'toor',
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