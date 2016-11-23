#!/usr/bin/php -q

<?php
/**
 * Rsync Static Deployment Tool by PHP
 *
 * Rsync a specified source folder to destination servers under the 
 * setting path, supporting filtering files from excludeList.
 *
 * @version     v1.1.2
 * @author      Nick Tsai <myintaer@gmail.com>
 * @filesource  PHP >= 5.4 (Support 5.0 if removing Short-Array-Syntax)
 * @param string $argv[1] Target servers group key of serverList
 * @example
 *  $ ./rsyncStatic.php           // Rsync to servers in default group
 *  $ ./rsyncStatic.php stage     // Rsync to servers in stage group
 *
 */

ob_implicit_flush();

// Target server group list for RSYNC
$serverEnv = (isset($argv[1])) ? $argv[1] : 'default';

/* --- Config Block --- */

// Config: Distant server list
$config['serverList'] = [
    'default' => [],
    'stage' => [
        '110.1.1.1',
    ],
    'prod' => [
        '110.1.2.1',
    ],
];

// Config: Distant server username
$config['destUsername'] = 'www-data';

// Config: Source directory on local
$config['sourceDir'] = '/home/www/www.project.com/webroot';

// Config: Destination directory on local
$config['destDir'] = '/home/www/www.project.com/';

// Config: Rsync command
$config['rsyncParams'] = '-av --delete';

// Config: Exclude list
$config['excludeList'] = [

    'web/upload',
    'yii',
];


// Config: rsync usleep micro second
$config['rsyncUsleepTime'] = 100000;

/* --- End of Config --- */



try {

    /* Check for server list */
    if (!isset($config['serverList'][$serverEnv]) || !$config['serverList'][$serverEnv]) {
        
        throw new Exception("No server data in this list: {$serverEnv}");
    }

    $sourceFile = $config['sourceDir'];
    $destDir = $config['destDir'];

    /* File existence check */
    if (strlen(trim($sourceFile))==0) {

        throw new Exception('None of file input');
    }

    /* Check for type of file / directory */
    if (!is_file($sourceFile) && !is_dir($sourceFile) ) {

        throw new Exception('File input is not a file or directory');
    }

    /* Check for type of link */
    if (is_link($sourceFile)) {

        throw new Exception('File input is symblic link');
    }
        
    /* Check for syntax if is PHP */
    if ( preg_match("/\.php$/i",$sourceFile) 
        && !preg_match("/No syntax errors detected/i", shell_exec("php -l ".$sourceFile))  ) {

        throw new Exception('PHP syntax error!');
    }


    /* Check for name of ignored directory */

    $reg = '/(template_c|templates_c|cache|\.sass\-cache)\/.*$/';

    preg_match($reg,$sourceFile,$matches);

    if ($matches) {

        //print_r($matches);

        die("PASS: Ignored directory\n");

        continue;
    }


    /**
     * Shell Process
     */
    foreach ($config['serverList'][$serverEnv] as $key => $server) {

        echo '/* --- Process Start ---  */'."\n";
        echo '[Process ] '.($key+1)."\n";
        echo '[Server  ] '.$server."\n";
        echo '[Source  ] '.$sourceFile."\n";
        echo '[DestDir ] '.$destDir."\n";

        /* Shell Process */
        if (true) {

            usleep($config['rsyncUsleepTime']);

            $cmd = 'rsync ' . $config['rsyncParams'];

            # Exclude process
            $excludeList = $config['excludeList'];
            foreach ((array)$excludeList as $key => $file) {
                
                $cmd .= " --exclude \"{$file}\"";
            }

            # Rsync shell command
            $cmd = sprintf("%s %s %s@%s:%s",
                $cmd,
                $sourceFile,
                $config['destUsername'],
                $server,
                $destDir 
            );
            
            echo '[Command]  '.$cmd."\n";
            

            # Shell execution

            $result = shell_exec($cmd);

            echo '[Message]  '."\n".$result;
        }

        echo '/* --- /Process End ---  */'."\n";
        echo "\r\n";
    }

    echo "\r\n";
    
    
} catch (Exception $e) {

    die('ERROR:'.$e->getMessage()."\n");
}


