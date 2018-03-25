Deployer *by PHP-CLI*
=====================

Code deployment tool based on RSYNC running by PHP-CLI script

[![Latest Stable Version](https://poser.pugx.org/yidas/deployer-php-cli/v/stable?format=flat-square)](https://packagist.org/packages/yidas/deployer-php-cli)
[![Latest Unstable Version](https://poser.pugx.org/yidas/deployer-php-cli/v/unstable?format=flat-square)](https://packagist.org/packages/yidas/deployer-php-cli)
[![License](https://poser.pugx.org/yidas/deployer-php-cli/license?format=flat-square)](https://packagist.org/packages/yidas/deployer-php-cli)

FEATURES
--------

- *Deploy to **multiple** servers by **projects/groups***

- ***Git support** for source project*

- ***Composer support** for source project*

- ***Filter** for excluding specified files support*

Helping developers to deploy codes from local instance to remote instances.

---

OUTLINE
-------

* [Demonstration](#demonstration)
* [Requirements](#requirements)
* [Installation](#installation)
  - [Download](#download)
  - [Make Command](#make-command)
* [Configuration](#configuration)
  - [Project Setting](#project-setting)
  - [Config Options](#config-options)
  - [Example](#example)
* [Usage](#usage)
  - [Interactive Project Select](#interactive-project-select)
  - [Non-Interactive Project Select](#non-interactive-project-select)
  - [Skip Flows](#skip-flows)
  - [Revert & Reset back](#revert--reset-back)
* [Implementation](#implementation)
* [Additions](#additions)
  - [Rsync without Password](#rsync-without-password)
  - [Save Binary Encode File](#save-binary-encode-file)
  
---

DEMONSTRATION
-------------

Deploy local project to remote servers by just executing the deployer in command after installation:

```
$ deployer
```
Or you can call the original bootstrap:
```
$ ./deployer
$ php ./deployer
```

The result could like be:
```
$ deployer --project="project1"

Successful Excuted Task: Git
Successful Excuted Task: Composer
Successful Excuted Task: Deploy to 127.0.0.11
Successful Excuted Task: Deploy to 127.0.0.12
Successful Excuted Task: Deploy
```

---

REQUIREMENTS
------------

This library requires the following:

- PHP(CLI) 5.4.0+
- RSYNC

---

INSTALLATION
------------

### Download

You could choose a install way between Composer or Wget:

#### Option 1: Composer Installation

```
composer create-project yidas/deployer-php-cli
```

or

#### Option 2: Wget Installation

You could see [Release](https://github.com/yidas/deployer-php-cli/releases) for picking up the package with version, for example:
    
```
$ wget https://github.com/yidas/deployer-php-cli/archive/master.tar.gz -O deployer-php-cli.tar.gz
```

After download, uncompress the package:

```
$ tar -zxvf deployer-php-cli.tar.gz
```

### Make Command

To make a command for deployer, `cd` into the folder, then create `deployer.php` a symbol link to bin folder: 

```
$ sudo ln -s $(pwd -L)/deployer /usr/bin/deployer
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
            'timeout' => 60,
        ],
        'commands' => [
            'before' => [
                '',
            ],
        ],
        'verbose' => false,
    ],
];
```

### Config Options:

|Key|Type|Description|
|:-|:-|:-|
|**servers**|array|Distant server host list|
|**user**|array\|string|Local/Remote server user, auto detect current user if empty|
|**source**|string|Local directory for deploy, use `/` as end means `*` |
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
|timeout|int|Timeout seconds of each rsync connections|
|sleepSeconds|int|Seconds waiting of each rsync connections|

#### Commands

|Key|Type|Description|
|:-|:-|:-|
|init|array|Addition commands trigger at initialization|
|before|array|Addition commands trigger before deploying|
|after|array|Addition commands trigger after deploying|

### Example

* Copy `project` directory form `/var/www/html/` to destination under `/var/www/html/test/`:

```php
'source' => '/var/www/html/project',
'destination' => '/var/www/html/test/',
```

* Copy all files (`*`) form `/var/www/html/project/` to destination under `/var/www/html/test/`:

```php
'source' => '/var/www/html/project/',
'destination' => '/var/www/html/test/',
```

---

USAGE
-----

```
Usage:
  deployer [options] [arguments]
  ./deployer [options] [arguments]

Options:
      --help            Display this help message
      --version         Show the current version of the application
  -p, --project         Project key by configuration for deployment
      --config          Show the seleted project configuration
      --configuration
      --skip-git        Force to skip Git process
      --skip-composer   Force to skip Composer process
      --git-reset       Git reset to given commit with --hard option
  -v, --verbose         Increase the verbosity of messages
```

### Interactive Project Select

```
$ deployer

Your available projects in configuration:
  [0] default
  [1] project1

  Please select a project [number or project, Ctrl+C to quit]:default

Successful Excuted Task: Git
Successful Excuted Task: Composer
Successful Excuted Task: Deploy to 127.0.0.11
Successful Excuted Task: Deploy
```

### Non-Interactive Project Select

```
$ deployer --project="default"
```

### Skip Flows

You could force to skip flows such as Git and Composer even when you enable then in config.

```
$ deployer --project="default" --skip-git --skip-composer
```

### Revert & Reset back

You could reset git to specified commit by using `--git-reset` option when you get trouble after newest release.

```
$ deployer --project="default" --git-reset="79616d"
```

> This option is same as executing `git reset --hard 79616d` in source project.

---

IMPLEMENTATION
--------------

Assuming `project1` is the developing project which you want to deploy.

Developers must has their own site to develop, for example:

```
# Dev host
/var/www/html/dev/nick/project1
/var/www/html/dev/eric/project1
```

In general, you would has stage `project1` which the files are same as production:

```
# Dev/Stage host
/var/www/html/project1
```

The purpose is that production files need to be synchronous from stage:

```
# Production host
/var/www/html/project1
```

This tool regard stage project as `source`, which means production refers to `destination`, so the config file could like:

```php
return [
    'project1' => [
        ...
        'source' => '/var/www/html/project1',
        'destination' => '/var/www/html/',
        ...
```

After running this tool to deploy `project1`, the stage project's files would execute processes likes `git pull` then synchronise to production.


---

ADDITIONS
---------

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

### Minify/Uglify by Gulp

#### 1. Install NPM, for Debian/Ubuntu:

```
apt-get install npm
```

#### 2. Install Gulp by NPM

```
npm install -g gulp
```

#### 3. Create Gulp Project

```
cd /srv/tools/minify-project
npm install gulp --save-dev
touch gulpfile.js
```

#### 4. Set Gulp with packages

Package: [gulp-uglify](https://www.npmjs.com/package/gulp-uglify)

`gulpfile.js`:

```javascript
var gulp = require('gulp'),
    uglify = require('gulp-uglify');

gulp.task('minify', function () {
    // Create or cleanup a temporary folder
    // Copy all JS files from application into temporary folder
    // Run JS uglify() in temporary folder
    // Move finished JS files back to application
});
```

#### 5. Set Gulp Process into Deployer

```
'source' => '/srv/project',
'commands' => [
    'before' => [
        'cd ./../tools/minify-project; gulp minify',
    ],
],
```



