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
    }
    
    /**
     * @param array $configList
     */
    public function run(Array $configList, Array $argv)
    {
        $projectKey = (isset($argv[1])) ? $argv[1] : 'default';
        $secondOption = (isset($argv[2])) ? $argv[2] : NULL;

        try {
        
            // Check project config
            if (!isset($configList[$projectKey])) {

                if (is_array($configList) && $configList) {

                    echo "Your project list in configuration:\n";
                    foreach ($configList as $key => $project) {
                        echo "{$key}\n";
                    }
                    exit;

                } else {
                    
                    throw new Exception("Config project not found: {$projectKey}", 400);
                }
            }

            $config = $configList[$projectKey];

            $deployer = new Deployer($config);

            /* Function Option */
            if ($secondOption) {
                switch ($secondOption) {
                    case 'config':
                    case 'configuration':
                    case 'show':
                        print_r($deployer->getConfig());
                        exit;
                        break;
                }
            }

            $deployer->run();
        

        } catch (Exception $e) {

            die("ERROR:{$e->getMessage()}\n");
        }
    }
}
