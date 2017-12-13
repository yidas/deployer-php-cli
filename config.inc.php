<?php

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
            'web/upload',
            'runtime/log',
        ],
        'git' => [
            'enabled' => true,
            'checkout' => true,
            'branch' => 'master',
        ],
        'composer' => [
            'enabled' => true,
            'command' => 'composer update',
        ],
        'rsync' => [
            'params' => '-av --delete',
            'sleepSeconds' => 0,
        ],
        'commands' => [
            'before' => [
                '',
            ],
        ],
        'verbose' => false,
    ]
];