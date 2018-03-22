<?php

/**
 * Application
 * 
 * @since       1.1.0
 * @author      Nick Tsai <myintaer@gmail.com>
 */
class App
{
    const VERSION = '1.1.0';
    
    function __construct() 
    {
        // Loader
        require __DIR__. '/ShellConsole.php';
        require __DIR__. '/Deployer.php';
        require __DIR__. '/GetOpt.php';
    }
    
    /**
     * @param array $configList
     */
    public function run(Array $configList, Array $argv)
    {
        // $projectKey = (isset($argv[1])) ? $argv[1] : 'default';

        /**
         * Options definition
         */
        $shortopts  = "";
        $shortopts .= "p:";
        $shortopts .= "v";

        $longopts  = array(
            "project:",
            "skip-git",
            "skip-composer",
            "git-reset:",
            "verbose",
            "config",
            "configuration",
            "help",
            "version",
        );

        try {

            // GetOpt
            $getOpt = new GetOpt($shortopts, $longopts);
            // var_dump($getOpt->getOptions());
    
            $projectKey = $getOpt->get(['project', 'p']);
            $showConfig = $getOpt->has(['config', 'configuration']);
            $showHelp = $getOpt->has(['help']);
            $showVersion = $getOpt->has(['version']);

            /**
             * Exception before App 
             */
            // Help
            if ($showHelp) {
                // Load view with CLI auto display
                require __DIR__. '/views/help.php';
                echo "\r\n";
                return;
            }
            // Version
            if ($showVersion) {
                // Get version
                $version = self::VERSION;
                echo "Deployer-PHP-CLI version {$version} \r\n";
                return;
            }
    
            // Check project config
            if (!isset($configList[$projectKey])) {
                // Get app root path
                $fileLocate = dirname(__DIR__);
                $version = self::VERSION;
                echo "Deployer-PHP-CLI version {$version} \r\n";
                echo "  Bootstrap directory: {$fileLocate}. \r\n";
                echo "  Usage manual: `deployer --help`\r\n";
                echo "\r\n";
    
                // First time flag
                $isFirstTime = ($projectKey===null) ? true : false;
    
                while (!isset($configList[$projectKey])) {
                    
                    // Not in the first round
                    if (!$isFirstTime) {
                        echo "ERROR: The `{$projectKey}` project doesn't exist in your configuration.\n\n";
                    }
    
                    // Available project list
                    echo "Your available projects in configuration:\n";
                    $projectKeyMap = [];
                    foreach ($configList as $key => $project) {

                        $projectKeyMap[] = $key;
                        $num = key($projectKeyMap);
                        echo "  [{$num}] {$key}\n";
                    }
                    echo "\r\n";
                    // Get project input
                    echo "  Please select a project [project key, or Ctrl+C to quit]:";
                    $projectKey = trim(fgets(STDIN));
                    echo "\r\n";

                    // Number input finding by $projectKeyMap
                    if (is_numeric($projectKey)) {
                        $projectKey = isset($projectKeyMap[$projectKey])
                            ? $projectKeyMap[$projectKey]
                            : $projectKey;
                    }

                    $isFirstTime = false;
                }
            }

            $config = &$configList[$projectKey];

            // Rewrite config
            $config['git']['enabled'] = ($getOpt->has('skip-git')) 
                ? false : $config['git']['enabled'];
            $config['composer']['enabled'] = ($getOpt->has('skip-composer')) 
                ? false : $config['composer']['enabled'];
            $config['verbose'] = ($getOpt->has(['verbose', 'v'])) 
                ? true : $config['verbose'];
            // Other config
            $config['git']['commit'] = $getOpt->get('git-commit');

            // Initial Deployer
            $deployer = new Deployer($config);

            /**
             * Exception before Deployer run 
             */
            if ($showConfig) {
                echo "The `{$projectKey}` project's configuration is below:\n";
                print_r($deployer->getConfig());
                return;
            }

            // Run Deployer
            $deployer->run();
        
        } catch (Exception $e) {

            die("ERROR:{$e->getMessage()}\n");
        }
    }
}
