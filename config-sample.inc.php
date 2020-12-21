<?php

/**
 * @see https://github.com/yidas/deployer-php-cli
 */

return [
    // This project config processes deployment only for simple usage
    'default' => [
        'servers' => [
            '127.0.0.1',
        ],
        'source' => '/home/user/project',
        'destination' => '/var/www/html/prod/',
    ],
    // This project config processes Git and Composer before deployment
    'advanced' => [
        'servers' => [
            '127.0.0.1',
        ],
        'user' => [
            'local' => '',
            'remote' => '',
        ],
        'source' => '/home/user/project-advanced',
        'destination' => '/var/www/html/prod/',
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
            // 'path' => ['./', './application/'],
            // Use xdebug on php-fpm
            // 'command' => 'COMPOSER_ALLOW_XDEBUG=1 composer -n install',
            'command' => 'composer -n install',
        ],
        'test' => [
            'enabled' => false,
            'name' => 'PHPUnit',
            'type' => 'phpunit',
            // CodeIgniter 3 for example (https://github.com/yidas/codeigniter-phpunit)
            'command' => './application/vendor/bin/phpunit',
            'configuration' => './application/phpunit.xml',
        ],
        'rsync' => [
            'enabled' => true,
            'params' => '-av --delete',
            // 'sleepSeconds' => 0,
            // 'timeout' => 60,
            // 'identityFile' => '/home/deployer/.ssh/id_rsa',
        ],
        'commands' => [
            'before' => [
                '',
            ],
        ],
        'webhook' => [
            'enabled' => false,
            'provider' => 'gitlab',
            'project' => 'yidas/deployer-php-cli',
            'token' => 'thisistoken',
        ],
        'verbose' => false,
    ],
];
