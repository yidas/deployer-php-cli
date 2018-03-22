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
        'source' => __DIR__,
        'destination' => __DIR__,
        'exclude' => [
            '.git',
        ],
        'git' => [
            'enabled' => true,
            'path' => './',
            'checkout' => true,
            'branch' => 'master',
        ],
        'composer' => [
            'enabled' => true,
            'path' => './',
            'command' => 'composer install',
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
    ],
];
