<?php

/**
 * @see https://github.com/yidas/deployer-php-cli
 */

return [
    'default' => [
        'servers' => [
            '127.0.0.1',
        ],
        'user' => [
            'local' => '',
            'remote' => '',
        ],
        'source' => '/var/www/html/project',
        'destination' => '/var/www/html/test/',
        'exclude' => [
            '.git',
            'tmp/*',
        ],
        'git' => [
            'enabled' => true,
            'path' => './',
            'checkout' => true,
            'branch' => 'master',
            'submodule' => false,
        ],
        'composer' => [
            'enabled' => true,
            'path' => './',
            'command' => 'composer install',
        ],
        'rsync' => [
            'params' => '-av --delete',
            // 'sleepSeconds' => 0,
            // 'timeout' => 60,
            // 'identityFile' => '/root/.ssh/id_rsa',
        ],
        'commands' => [
            'before' => [
                '',
            ],
        ],
        'verbose' => false,
    ],
];
