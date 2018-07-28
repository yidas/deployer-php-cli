<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);

$defaultLogFile = '/tmp/deployer-php-cli_webhook.log';

// Log mode
$logMode = isset($_GET['log']);

// GitLab data fetch
$inputToken = isset($_SERVER['HTTP_X_GITLAB_TOKEN']) ? $_SERVER['HTTP_X_GITLAB_TOKEN'] : null;
$body = file_get_contents('php://input');
$data = json_decode($body, true);
// writeLog($data);exit;

// Push info
$info = [
    'token' => $inputToken,
    'project' => $data['project']['path_with_namespace'],
    'projectUrl' => $data['project']['url'],
    'branch' => str_replace('refs/heads/', '', $data['ref']),
];
// writeLog($info);exit;

// Log mode info rewite
if ($logMode) {
    $info = [
        'token' => isset($_GET['token']) ? $_GET['token'] : null,
        'project' => $_GET['log'],
        'branch' => isset($_GET['branch']) ? $_GET['branch'] : 'master',
    ];
}

try {

    // Check
    $configList = require __DIR__. '/../../config.inc.php';
   
    $matchedConfig = [];
    $errorInfo = null;

    foreach ($configList as $key => $config) {
        
        // Webhook setting check
        if (!isset($config['webhook']['enabled']) || !$config['webhook']['enabled']) {
            continue;
        }
        // Gitlab provider check
        elseif (!isset($config['webhook']['provider']) || $config['webhook']['provider']!='gitlab') {
            continue;
        }
        // Last mapping for project name
        elseif (!isset($config['webhook']['project']) || $config['webhook']['project']!=$info['project']) {
            continue;
        }
        // Webhook branch check
        elseif (isset($config['webhook']['branch']) && $config['webhook']['branch']!=$info['branch']) {
            $errorInfo[] = "Branch `{$info['branch']}` could not be matched from webhook config";
            continue;
        }
        // Use Git branch setting while no branch setting in Webhook
        elseif (!isset($config['webhook']['branch']) && isset($config['git']['branch']) && $config['git']['branch']!=$info['branch']) {
            $errorInfo[] = "Branch `{$info['branch']}` could not be matched from Git config";
            continue;
        }
        
        // match config
        $matchedConfig = $config;
        // For Deployer config
        $matchedConfig['projectKey'] = $key;

        break;
    }
} catch (\Exception $e) {
    responeseWithPack(null, $e->getCode(), $e->getMessage());
    exit;
}

// Matched config check
if (empty($matchedConfig)) {
    responeseWithPack($errorInfo, 404, 'No matched config found');
    exit;
}
// Authorization while setting token 
elseif (isset($matchedConfig['webhook']['token']) && $info['token'] != $matchedConfig['webhook']['token']) {
    responeseWithPack(['inputToken' => $info['token']], 403, 'Token is invalid');
    exit;
}

// Log mode
if ($logMode) {

    if (!isset($matchedConfig['webhook']['log'])) {
        die('Log setting is disabled');
    }
    
    $logFile = is_string($matchedConfig['webhook']['log']) 
        ? $matchedConfig['webhook']['log'] 
        : $defaultLogFile;

    if (!file_exists($logFile)) {
        die('Log file not found');
    }
    
    // Read log
    $oldList = json_decode(file_get_contents($logFile), true);
    $logList = is_array($oldList) ? $oldList : [];
    
    // Output
    if ($logList) {
        foreach ($logList as $key => $row) {
            echo "{$row['datetime']}<pre>". $row['response'] ."</pre>";
        }
    } else {
        echo 'No record yet';
    }
    
    exit;
}

/**
 * Fast response for webhook
 */
responeseWithPack([
    'resultUrl' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") 
        . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"
        . "?log={$info['project']}&branch={$info['branch']}&token={$info['token']}"
], 200, 'Deployer will start processing! Check log for result information.');
ignore_user_abort(true);
header('Connection: close'); 
flush();
fastcgi_finish_request();

/**
 * Bootstrap
 */
// Loader
require __DIR__. '/../../src/ShellConsole.php';
require __DIR__. '/../../src/Deployer.php';
// Initial Deployer
$deployer = new Deployer($matchedConfig);
// Run Deployer
$res = $deployer->run();

if ($res && isset($matchedConfig['webhook']['log'])) {

    // Max rows per each log file
    $limit = 100;
    // Log file
    $logFile = is_string($matchedConfig['webhook']['log']) 
        ? $matchedConfig['webhook']['log'] 
        : $defaultLogFile;
    // Format
    $row = [
        'provider' => $config['webhook']['provider'],
        'info' => $info, 
        'datetime' => date("Y-m-d H:i:s"), 
        'response' => $res,
    ];
    // Log text
    $logList = [];

    if (file_exists($logFile)) {
        
        // Read log
        $oldList = json_decode(file_get_contents($logFile), true);
        $logList = is_array($oldList) ? $oldList : [];
        // Limit handling
        if (count($logList) >= $limit) {
            array_pop($logList);
        }
    }
    array_unshift($logList, $row);
    // Write back to log
    file_put_contents($logFile, json_encode($logList));
}

/**
 * writeLog
 *
 * @param string $text
 * @return void
 */
function writeLog($text='no message', $writeLogFile='/tmp/deployer-php-cli.log')
{
    $text = is_array($text) ? print_r($text, true) : $text;
    
    file_put_contents($writeLogFile, $text);
}

/**
 * Response
 *
 * @param integer $status
 * @param array $body
 * @return void
 */
function response($status=200, $body=[])
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($body, JSON_UNESCAPED_SLASHES);
}

/**
 * Responese with pack
 *
 * @param [type] $data
 * @param integer $status
 * @param [type] $message
 * @return void
 */
function responeseWithPack($data=null, $status=200, $message=null)
{
    $body = [
        'code' => $status,
    ];
    // Message field
    if ($message) {
        $body['message'] = $message;
    }
    // Data field
    if ($data) {
        $body['data'] = $data;
    }
    
    return response($status, $body);
}