#!/usr/bin/php -q

<?php
/**
 * Rsync Deployment Tool by PHP
 *
 * Rsync a file or a folder from current path to destinate servers with 
 * the same path automatically, the current path is base on Linux's 
 * "pwd -P" command.
 *
 * @version     v1.1.0
 * @author      Nick Tsai <myintaer@gmail.com>
 * @filesource  PHP >= 5.4 (Support >= 5.0 if removing Short-Array-Syntax)
 * @param string $argv[1] File/directory in current path for rsync
 * @param string $argv[2] (Optional) Target servers group key of serverList
 * @example
 *  $ ~/rsync.php file.php      // Rsync file.php to servers with same path
 *  $ ~/rsync.php folderA       // Rsync whole folderA to servers
 *  $ ~/rsync.php ./            // Rsync current whole folder
 *  $ ~/rsync.php ./ stage      // Rsync to servers in stage group
 *
 */

ob_implicit_flush();

// print_r($argv);exit;

// File input
$file = $argv[1];

// Target server group list for RSYNC
$serverEnv = (isset($argv[2])) ? $argv[2] : 'default';


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

// Config: rsync command
$config['rsync']['param'] = '-av --delete';

// Config: rsync mode ('deamon' or ssh)
$config['rsync']['mode'] = '';

// Config: rsync path in deamon mode only
$config['rsync']['path'] = "/(\/usr)?\/home\/wwwdata/";

// Config: rsync usleep micro second
$config['rsync']['usleep_time'] = 100000;

/* --- /Config --- */


// Directory of destination same as source
$dir = trim(shell_exec("pwd -P"));


try {

    /* File existence check */
    if (strlen(trim($file))==0)
        throw new Exception('None of file input');
    
    /* Check $argv likes asterisk */
    if (isset($argv[3])) 
        throw new Exception('Invalid arguments input');

    /**
     * Validating file name input
     *
     * @var sstring $reg Regular patterns
     * @example
     *  \w\/    // folderA/
     *  \*      // * or *.*
     *  ^\/     // / or /etc
     *
     */
    $reg = '/(\w\/|\*|^\/)/';

    preg_match($reg,$file,$matches);

    if ($matches) {

        //print_r($matches);

        throw new Exception('Invalid file name input');
    }

    /* Check for server list */
    if (!isset($config['serverList'][$serverEnv]) || !$config['serverList'][$serverEnv])
        throw new Exception("No server data in this list: {$serverEnv}");
    

    /* File or directory of source definition */
    $this_file = $dir.'/'.$file;

    /* Check for type of link */
    if (is_link($this_file))
        throw new Exception('File input is symblic link');
    

    /* Check for type of file / directory */
    if (!is_file($this_file) && !is_dir($this_file) )
        throw new Exception('File input is not a file or directory');
    
        
    /* Check for syntax if is PHP */
    if ( preg_match("/\.php$/i",$file) 
        && !preg_match("/No syntax errors detected/i", shell_exec("php -l ".$this_file))  ) {

        throw new Exception('PHP syntax error!');
    }


    /**
     * Shell Process
     */
    foreach ($config['serverList'][$serverEnv] as $key => $server) {

        echo '/* --- Process Start ---  */'."\n";
        echo '[Process]  '.($key+1)."\n";
        echo '[Server]   '.$server."\n";
        echo '[Filename] '.$file."\n";
        echo '[Dir Path] '.$dir."\n";

        # Shell Process
        if (true) {

            usleep($config['rsync']['usleep_time']);

            $cmd = 'rsync ' . $config['rsync']['param'];

            if ($config['rsync']['mode'] == 'deamon') {
                
                # Mode: daemon 873 port
                $cmd = sprintf("%s %s %s::%s%s",
                    $cmd,
                    $file,
                    $server,
                    $config['destUsername'],
                    preg_replace($config['rsync']['path'], '', $dir).'/'
                );

            } else {

                # Mode: SSH
                $cmd = sprintf("%s %s %s@%s:%s",
                    $cmd,
                    $file,
                    $config['destUsername'],
                    $server,
                    $dir 
                );
            }

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
