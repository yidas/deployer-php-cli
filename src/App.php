<?php

/**
 * Application
 */
class App
{
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
            "git-commit:",
            "verbose",
            "config",
            "configuration",
        );

        try {

            $getOpt = new GetOpt($shortopts, $longopts);
            // var_dump($getOpt->getOptions());
    
            $projectKey = $getOpt->get(['project', 'p']);
            $showConfig = $getOpt->has(['config', 'configuration']);
            // var_dump($skipGit);exit;
    
            // Check project config
            if (!isset($configList[$projectKey])) {
    
                // First time flag
                $isFirstTime = ($projectKey===null) ? true : false;
    
                while (!isset($configList[$projectKey])) {
                    
                    // Not in the first round
                    if (!$isFirstTime) {
                        echo "ERROR: The `{$projectKey}` project doesn't exist in your configuration.\n\n";
                    }
    
                    // Available project list
                    echo "Your available projects in configuration:\n";
                    foreach ($configList as $key => $project) {
                        echo "{$key}\n";
                    }
                    echo "\r\n";
                    // Get project input
                    echo "Please enter the project:";
                    $projectKey = trim(fgets(STDIN));
                    echo "\r\n";

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

            /* Exception function */
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
