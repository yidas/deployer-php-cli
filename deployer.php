#!/usr/bin/php -q

<?php
/**
 * Deployer
 *
 * Application for deploying projects with management, supporting git and excluding files.
 *
 * @version     1.1.0
 * @author      Nick Tsai <myintaer@gmail.com>
 * @filesource  PHP 5.4.0+
 * @filesource  RSYNC commander
 * @filesource  Git commander
 * @filesource  Composer commander
 *
 * @param string $argv[1] Project
 * @example
 *  $ ./deployer                    // Deploy default project
 *  $ ./deployer default            // Deploy default project
 *  $ ./deployer my_project         // Deploy the project named `my_project` by key
 *  $ ./deployer deafult config     // Show configuration of default project
 */

// App loader
require __DIR__. '/src/App.php';


/* Bootstrap */

// error_reporting(E_ALL);
// ini_set("display_errors", 1);

/* Config List Handler */
$configList = require __DIR__. '/config.inc.php';
// print_r($configList);

$argv = isset($argv) ? $argv : [];

$app = new App;
$app->run($configList, $argv);

