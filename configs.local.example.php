<?php
/*
 * Installation-specific configuration.
 *
 * Copy this file to 'configs.local.php' and fill in your values. 'configs.local.php' is excluded from version
 * control and protected by the .htaccess, so it's the right place for credentials. Every value defined here
 * overrides the default from 'configs.php'; you only need to list what differs.
 */

return [
    'application' => [
        'debug' => false,
    ],

    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'database_name',
        'user' => 'database_user',
        'pass' => 'database_password',
    ],
];
