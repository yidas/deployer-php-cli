<?php

/**
 * Application
 * 
 * @since       1.10.2
 * @author      Nick Tsai <myintaer@gmail.com>
 */
class App
{
    const VERSION = '1.12.0';
    
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
        $shortopts .= "h";
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
            $showHelp = $getOpt->has(['help', 'h']);
            $showVersion = $getOpt->has(['version']);

            /**
             * Exception before App 
             */
            // Help
            if ($showHelp) {
                // Version first
                $this->_echoVersion();
                echo "\r\n";
                // Load view with CLI auto display
                require __DIR__. '/views/help.php';
                echo "\r\n";
                return;
            }
            // Version
            if ($showVersion) {
                // Get version
                $this->_echoVersion();
                return;
            }
    
            // Check project config
            if (!isset($configList[$projectKey])) {

                // Welcome information
                // Get app root path
                $fileLocate = dirname(__DIR__);
                $this->_echoVersion();
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
                        // Get map key
                        end($projectKeyMap);
                        $num = key($projectKeyMap);

                        echo "  [{$num}] {$key}\n";
                    }
                    echo "\r\n";
                    // Get project input
                    echo "  Please select a project [number or project, Ctrl+C to quit]:";
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

            // Config initialized
            $defaultConfig = require __DIR__. '/default-config.inc.php';
            $config = array_replace_recursive($defaultConfig, $configList[$projectKey]);
            // Add `projectKey` key to the current config 
            $config['projectKey'] = $projectKey;

            // Rewrite config
            $config['git']['enabled'] = ($getOpt->has('skip-git')) 
                ? false : $this->_val($config, ['git', 'enabled']);
            $config['composer']['enabled'] = ($getOpt->has('skip-composer')) 
                ? false : $this->_val($config, ['composer', 'enabled']);
            $config['verbose'] = ($getOpt->has(['verbose', 'v'])) 
                ? true : $this->_val($config, ['verbose']);
            // Other config
            $config['git']['reset'] = $getOpt->get('git-reset');

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

    /**
     * Echo a line of version info
     */
    protected function _echoVersion()
    {
        $version = self::VERSION;
        echo "Deployer-PHP-CLI version {$version} \r\n";
    }

    /**
     * Var checker
     * 
     * @param mixed Variable
     * @param array Variable array level ['level1', 'key']
     * @return mixed value of specified variable 
     */
    protected function _val($var, $arrayLevel=[])
    {
        if (!isset($var)) {
            
            return null;
        }
        
        foreach ($arrayLevel as $key => $level) {
            
            if (!isset($var[$level])) {
                
                return null;
            }

            $var = &$var[$level];
        }
        
        return $var;
    }
}
