<?php
/**
 * Deployer
 *
 * Application for deploying projects with management, supporting git and excluding files.
 *
 * @since       1.0.0
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

/**
 * Deployer Core
 */
class Deployer
{
    use ShellConsole;
    
    private $_config;
    
    function __construct($config)
    {
        $this->_setConfig($config);
    }

    public function run()
    {
        $config = &$this->_config;

        // Check config
        $this->_checkConfig();
       
        ob_implicit_flush();

        // Local user
        if ($config['user']['local'] && $config['user']['local']!=$this->_getUser()) {
            $result = $this->_cmd("su {$config['user']['local']};");
        }
        
        $this->runCommands('init');
        $this->runGit();
        $this->runComposer();
        $this->runCommands('before');
        $this->runDeploy();
        $this->runCommands('after');
        
    }

    /**
     * Git Process
     */
    public function runGit()
    {
        if (!isset($this->_config['git'])) {
            return;
        }
        
        // Git Config
        $config = &$this->_config['git'];
        
        // Check enabled
        if (!$config || (isset($config['enabled']) && !$config['enabled']) ) {
            return;
        }
        
        // Git process
        
        $this->_verbose("/* --- Git Process Start --- */");
        // Git Checkout
        if ($config['checkout']) {
            $result = $this->_cmd("git checkout -- .", true);
        }
        // Git pull
        $cmd = ($config['branch']) 
            ? "git pull origin {$config['branch']}"
            : "git pull";
        $result = $this->_cmd($cmd, true);  

        $this->_verbose("/* --- Git Process Result --- */");
        $this->_verbose($result);
        $this->_verbose("/* --- Git Process End --- */");

        // Check error
        if (strpos($result, 'fatal:')===0) {
            
            $this->_error("Git");
            $this->_verbose($result);
            exit;
        }

        $this->_done("Git");
    }

    /**
     * Composer Process
     */
    public function runComposer()
    {
        if (!isset($this->_config['composer'])) {
            return;
        }
        
        // Composer Config
        $config = &$this->_config['composer'];
        
        // Check enabled
        if (!$config || (isset($config['enabled']) && !$config['enabled']) ) {
            return;
        }
        
        // Composer process
        $this->_verbose("/* --- Composer Process Start --- */");
        
        $cmd = $config['command'];
        // Shell execution
        $result = $this->_cmd($cmd, true);
        $this->_verbose($result);

        $this->_verbose("/* --- Composer Process Result --- */");
        $this->_verbose($result);
        $this->_verbose("/* --- Composer Process End --- */");

        /**
         * Check error
         * 
         * @todo   More error detections
         */
        // Error for Composer could not find a composer.json file
        if (strpos($result, 'Composer')===0) {
            
            $this->_error("Composer");
            $this->_verbose($result);
            exit;
        }

        $this->_done("Composer");
    }

    /**
     * Customized Commands Process
     * 
     * @param string Trigger point
     */
    public function runCommands($trigger)
    {
        if (!isset($this->_config['commands'])) {
            return;
        }
        
        // Commands Config
        $config = &$this->_config['commands'];
        
        // Check enabled
        if (!isset($config[$trigger]) || !is_array($config[$trigger])) {
            return;
        }

        // process
        foreach ($config[$trigger] as $key => $cmd) {

            if (!$cmd) {
                continue;
            }
            
            $this->_verbose("/* --- Command:{$key} Process Start --- */");
            
            // Format command
            $cmd = "{$cmd};";
            $result = $this->_cmd($cmd, true);

            $this->_verbose("/* --- Command:{$key} Process Result --- */");
            $this->_verbose($result);
            $this->_verbose("/* --- Command:{$key} Process Start --- */");

            $this->_done("Commands {$trigger}");
        }
    }

    /**
     * Deploy Process
     */
    public function runDeploy()
    {
        // Config
        $config = &$this->_config;

        // process
        foreach ($config['servers'] as $key => $server) {         

            // Info display
            $this->_verbose("/* --- Rsync Process Info --- */");
            $this->_verbose('[Process]: '.($key+1));
            $this->_verbose('[Server ]: '.$server);
            $this->_verbose('[User   ]: '.$config['user']['remote']);
            $this->_verbose('[Source ]: '.$config['source']);
            $this->_verbose('[Remote ]: '.$config['destination']);
            $this->_verbose("/* -------------------------- */");
            $this->_verbose("Processing Rsync...");


            /* Command builder */
            
            $cmd = 'rsync ' . $config['rsync']['params'];

            // Add exclude
            $excludeFiles = $config['exclude'];
            foreach ((array)$excludeFiles as $key => $file) {
                $cmd .= " --exclude \"{$file}\"";
            }

            // Rsync shell command
            $cmd = sprintf("%s %s %s@%s:%s",
                $cmd,
                $config['source'],
                $config['user']['remote'],
                $server,
                $config['destination'] 
            );
            
            $this->_verbose('[Command]: '.$cmd);

            // Shell execution
            $result = $this->_cmd($cmd);

            $this->_verbose("/* --- Rsync Process Result --- */");
            $this->_verbose($result);
            $this->_verbose("/* ---------------------------- */");
            $this->_verbose("");

            sleep($config['rsync']['sleepSeconds']);
            $this->_done("Deploy to {$server}");
        }

        $this->_done("Deploy");
    }

    /**
     * Get project config 
     * 
     * @return array Config
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * Config setting
     * 
     * @param array $config
     */
    private function _setConfig($config)
    {
        if (!isset($config['servers']) || !$config['servers'] || !is_array($config['servers'])) {
            throw new Exception('Config not set: servers', 400);
        }

        if (!isset($config['source']) || !$config['source']) {
            throw new Exception('Config not set: source', 400);
        }

        $config['user'] = (isset($config['user'])) 
            ? $config['user']
            : [];

        $config['user']['local'] = is_string($config['user']) ? $config['user'] : '';
        $config['user']['local'] = (isset($config['user']['local']) && $config['user']['local']) 
            ? $config['user']['local']
            : $this->_getUser();

        $config['user']['remote'] = (isset($config['user']['remote']) && $config['user']['remote']) 
            ? $config['user']['remote']
            : $config['user']['local'];

        $config['destination'] = (isset($config['destination'])) 
            ? $config['destination']
            : $config['source'];

        return $this->_config = $config;
    }

    private function _checkConfig()
    {
        $config = &$this->_config;

        // Check for type of file / directory
        if (!is_dir($config['source']) ) {
        
            throw new Exception('Source file is not a directory (project)');
        }
    
        // Check for type of link
        if (is_link($config['source'])) {
    
            throw new Exception('File input is symblic link');
        }
    }

    /**
     * Response
     * 
     * @param string $string
     */
    private function _done($string)
    {
        $this->_print("Successful Excuted Task: {$string}");
    }

    /**
     * Response for error
     * 
     * @param string $string
     */
    private function _error($string)
    {
        $this->_print("Failing Excuted Task: {$string}");
    }

    /** 
     * Command (Shell as default)
     * 
     * @param string $cmd
     * @return mixed Response
     */
    private function _cmd($cmd, $cdSource=false, $output=false)
    {
        // Clear rtrim
        $cmd = rtrim($cmd, ';');
        
        if ($cdSource) { 
            $cmd = "cd {$this->_config['source']};{$cmd}";
        }

        if (!$output) {
            $cmd .= " 2>&1";
        }

        // End cmd
        $cmd = "{$cmd};";

        return $this->_exec($cmd);
    }

    /**
     * Verbose response
     * 
     * @param string $string
     */
    private function _verbose($string)
    {
        if (isset($this->_config['verbose']) && $this->_config['verbose']) {
            $this->_print($string);
        }
    }
}