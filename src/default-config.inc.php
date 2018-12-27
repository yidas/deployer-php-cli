<?php
/**
 * @see https://github.com/yidas/deployer-php-cli
 */
return [
    'servers' => [
        '127.0.0.1',
    ],
    'user' => [
        'local' => '',
        'remote' => '',
    ],
    'source' => '',
    'destination' => '',
    'exclude' => [
        '.git',
    ],
    'git' => [
        'enabled' => false,
        'path' => './',
        'checkout' => true,
        'branch' => 'master',
        'submodule' => false,
    ],
    'composer' => [
        'enabled' => false,
        'path' => './',
        'command' => 'composer -n install',
    ],
    'rsync' => [
        'enabled' => true,
        'params' => '-av --delete',
        'sleepSeconds' => 0,
        'timeout' => 60,
        'identityFile' => null,
    ],
    'commands' => [
        'before' => [
            '',
        ],
    ],
    'webhook' => [
        'enabled' => false,
        'provider' => 'gitlab',
        'project' => '',
        'token' => '',
    ],
    'verbose' => false,
];