Deployer *by PHP-CLI*
=====================

Code deployment tool based on RSYNC running by PHP-CLI script

FEATURES
--------

***1. Deploy to multiple servers by projects/groups***

***2. Git supported for source project***

***3. Composer supported for source project***

***4. Filter for excluding specified files supported***

helping developers to deploy codes from local instance to remote instances.

---

DEMONSTRATION
-------------

Deploy local project to remote servers by just executing the deployer in command:

```
$ ./deployer
```
Or you can call it by PHP-CLI:
```
$ php ./deployer
```

The result could like be:
```
$ ./deployer

Successful Excuted Task: Git
Successful Excuted Task: Composer
Successful Excuted Task: Deploy to 127.0.0.1
Successful Excuted Task: Deploy
```

---

INSTALLATION
------------

You could see [Release](https://github.com/yidas/deployer-php-cli/releases) for picking up the package with version, for example:
    
```
$ wget https://github.com/yidas/deployer-php-cli/archive/master.tar.gz -O deployer-php-cli.tar.gz
```

After download, uncompress the package:

```
$ tar -zxvf deployer-php-cli.tar.gz
```

Then `cd` into the folder, you could create `deployer.php` a symbol link to bin folder: 

```
$ sudo ln -s $(pwd -L)/deployer.php /usr/local/bin/deployer
```

> you could check `deployer.php` file is executable, if not you can modify excuted property by `chmod +x`.

After all, you could start to set up the `config.inc.php` for deployer, and enjoy to use:

```
$ deployer
```

---

CONFIGURATION
-------------

### Project Setting:

You need to set up the projects configuration such as servers, source and destination in `config.inc.php` file:

```php
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
```

### Config Options

|Key|Type|Description|
|:-|:-|:-|
|**servers**|array|Distant server host list|
|**user**|array\|string|Local/Remote server user, auto detect current user if empty|
|**source**|string|Local directory for deploy |
|**destination**|string|Remote path for synchronism|
|**exclude**|array|Excluded files based on sourceFile path|
|verbose|bool|Enable verbose with more infomation or not|

#### Git

|Key|Type|Description|
|:-|:-|:-|
|enabled|bool|Enable git or not|
|checkout|bool|Execute git checkout -- . before git pull  |
|branch|string|Branch name for git pull, pull default branch if empty  |

#### Composer

|Key|Type|Description|
|:-|:-|:-|
|enabled|bool|Enable Composer or not|
|command|string|Update command likes `composer update`|

#### Rsync

|Key|Type|Description|
|:-|:-|:-|
|params|string|Addition params of rsync command|
|sleepSeconds|int|Seconds waiting of each rsync connections|

#### Commands

|Key|Type|Description|
|:-|:-|:-|
|init|array|Addition commands trigger at initialization|
|before|array|Addition commands trigger before deploying|
|after|array|Addition commands trigger after deploying|

---

USAGE
-----

```
$ ./deployer                    // Deploy default project
$ ./deployer default            // Deploy default project
$ ./deployer my_project         // Deploy the project named `my_project` by key
$ ./deployer deafult config     // Show configuration of default project
```

---

ADDITION
--------

### Rsync without Password:  

You can put your local user's SSH public key to destination server user for authorization.
```
.ssh/id_rsa.pub >> .ssh/authorized_keys
```

### Save Binary Encode File:  
  
While excuting script, if you get the error like `Exception: Zend Extension ./deployer does not exist`, you may save the script file with binary encode, which could done by using `vim`:

```
:set ff=unix
```



