<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);

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
            continue;
        }
        // Use Git branch setting while no branch setting in Webhook
        elseif (!isset($config['webhook']['branch']) && isset($config['git']['branch']) && $config['git']['branch']!=$info['branch']) {
            $errorInfo[] = "Branch {$info['branch']} could not be matched";
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
    responeseWithPack($errorInfo, 403, 'No matched config found');
    exit;
}
// Authorization while setting token 
elseif (isset($matchedConfig['webhook']['token']) && $inputToken != $matchedConfig['webhook']['token']) {
    responeseWithPack(['inputToken' => $info['token']], 403, 'Token is invalid');
    exit;
}


/**
 * Bootstrap
 */
// Loader
require __DIR__. '/../../src/ShellConsole.php';
require __DIR__. '/../../src/Deployer.php';
// Initial Deployer
$deployer = new Deployer($matchedConfig);
// Run Deployer
$deployer->run();

// responeseWithPack($info, 200, 'Success');

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
    echo json_encode($body);
    exit;
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