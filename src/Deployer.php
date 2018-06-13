<?php
/**
 * Deployer
 *
 * Application for deploying projects with management, supporting git and excluding files.
 *
 * @since       1.3.2
 * @author      Nick Tsai <myintaer@gmail.com>
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

        // cd into source directory
        $this->_cmd("cd {$this->_config['source']};");
        
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
        if (!$config || empty($config['enabled']) ) {
            return;
        }
        
        // Git process
        
        $this->_verbose("/* --- Git Process Start --- */");

        // Path
        $path = (isset($config['path'])) ? $config['path'] : null;
        $path = $this->_getAbsolutePath($path);

        // Git Checkout
        if ($config['checkout']) {
            $result = $this->_cmd("git checkout -- .", $path);
        }
        // Git pull
        $cmd = ($config['branch']) 
            ? "git pull origin {$config['branch']}"
            : "git pull";
        $result = $this->_cmd($cmd, $path);  
        $this->_verbose("/* --- Git Process Pull --- */");
        $this->_verbose($result);

        // Git Checkout
        if ($config['submodule']) {
            $result = $this->_cmd("git submodule init", $path);
            $result = $this->_cmd("git submodule update", $path);
        }

        // Git reset commit
        if ($config['reset']) {
            $result = $this->_cmd("git reset --hard {$config['reset']}", $path);
            $this->_verbose("/* --- Git Process Reset Commit --- */");
            $this->_verbose($result);
        } 

        $this->_verbose("/* --- Git Process End --- */");

        /**
         * Check error
         */
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
        if (!$config || empty($config['enabled']) ) {
            return;
        }
        
        // Composer process
        $this->_verbose("/* --- Composer Process Start --- */");

        // Path
        $path = (isset($config['path'])) ? $config['path'] : null;
        $path = $this->_getAbsolutePath($path);
        
        $cmd = $config['command'];
        // Shell execution
        $result = $this->_cmd($cmd, $path);
        $this->_verbose($result);

        $this->_verbose("/* --- Composer Process Result --- */");
        $this->_verbose($result);
        $this->_verbose("/* --- Composer Process End --- */");

        /**
         * Check error
         */
        // Success only: Loading composer
        if (strpos($result, 'Loading composer')!==0) {
            
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

            $this->_done("Commands {$trigger}: {$key}");
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
            $cmd = sprintf("%s --timeout=%d %s %s@%s:%s",
                $cmd,
                isset($config['rsync']['timeout']) ? $config['rsync']['timeout'] : 15,
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

            /**
             * Check error
             */
            // Success only: sending incremental file list
            if (strpos($result, 'sending')!==0) {
                // Error
                $this->_error("Deploy to {$server}");
                $this->_verbose($result);

            } else {

                sleep($config['rsync']['sleepSeconds']);
                $this->_done("Deploy to {$server}");
            }
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
     * Combined path with config source path if is relatived path
     * 
     * @param $path
     * @return string Path
     */
    private function _getAbsolutePath($path=null)
    {   
        // Is absolute path
        if (strpos($path, '/')===0 && file_exists($path)) {

            return $path;
        }
        
        return ($path) ? $this->_config['source'] ."/{$path}" : $this->_config['source'];
    }

    /** 
     * Command (Shell as default)
     * 
     * @param string $cmd
     * @param bool|string cd into source directory first (CentOS issue), string for customization
     * @return mixed Response
     */
    private function _cmd($cmd, $cdSource=false, $output=false)
    {
        // Clear rtrim
        $cmd = rtrim($cmd, ';');
        
        if ($cdSource) { 
            // Get path with the determination
            $path = ($cdSource===true) ? $this->_config['source'] : $cdSource;
            $cmd = "cd {$path};{$cmd}";
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