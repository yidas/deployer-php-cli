<?php
/**
 * Deployer
 *
 * Application for deploying projects with management, supporting git and excluding files.
 *
 * @since       1.11.0
 * @author      Nick Tsai <myintaer@gmail.com>
 */

/**
 * Deployer Core
 */
class Deployer
{
    use ShellConsole;
    
    private $_config;

    /**
     * Result response
     *
     * @var string Text
     */
    private $_response;
    
    function __construct($config)
    {
        $this->_setConfig($config);
    }
    
    /**
     * Run
     *
     * @return string Result response
     */
    public function run()
    {
        $config = &$this->_config;

        // Check config
        $this->_checkConfig();
       
        ob_implicit_flush();

        // Local user check
        /**
         * @todo Switch user
         */
        if ($config['user']['local'] && $config['user']['local']!=$this->_getUser()) {
            $this->_print("Access denied, please switch to local user: `{$config['user']['local']}` from config");
            exit;
        }

        // cd into source directory
        $this->_cmd("cd {$this->_config['source']};");
        
        // Project selected info
        $this->_result("Selected Project: {$config['projectKey']}");

        // Total cost time start
        $startSecond = microtime(true);

        $this->runCommands('init');
        $this->runGit();
        $this->runComposer();
        $this->runTest();
        $this->runTests();
        $this->runCommands('before');
        $this->runDeploy();
        $this->runCommands('after');

        // Total cost time end
        $costSecond = abs(microtime(true) - $startSecond);
        $costSecond = number_format($costSecond, 2, ".", "");
        $this->_result("Total Cost Time: {$costSecond}s");
        
        return $this->_response;
    }

    /**
     * Git Process
     */
    public function runGit()
    {
        if (!isset($this->_config['git'])) {
            return;
        }

        // Default config
        $defaultConfig = [
            'enabled' => false,
            'path' => './',
            'checkout' => true,
            'branch' => 'master',
            'submodule' => false,
        ];
        
        // Config init
        $config = array_merge($defaultConfig, $this->_config['git']);
        
        // Check enabled
        if (!$config || empty($config['enabled']) ) {
            return;
        }
        
        // Git process
        $this->_verbose("");
        $this->_verbose("### Git Process Start");

        // Path
        $path = (isset($config['path'])) ? $config['path'] : './';
        $path = $this->_getAbsolutePath($path);

        // Git Checkout
        if ($config['checkout']) {
            $result = $this->_cmd("git checkout -- .", $output, $path);
            // Common error check
            $this->checkError($result, $output);
        }
        // Git pull
        $cmd = ($config['branch']) 
            ? "git pull origin {$config['branch']}"
            : "git pull";
        $result = $this->_cmd($cmd, $output, $path);  
        // Common error check
        $this->checkError($result, $output);
        $this->_verbose("### Git Process Pull");
        $this->_verbose($output);

        // Git Checkout
        if (isset($config['submodule']) && $config['submodule']) {
            $result = $this->_cmd("git submodule init", $output, $path);
            $result = $this->_cmd("git submodule update", $output, $path);
            // Common error check
            $this->checkError($result, $output);
        }

        // Git reset commit
        if (isset($config['reset']) && $config['reset']) {
            $result = $this->_cmd("git reset --hard {$config['reset']}", $output, $path);
            $this->_verbose("### Git Process Reset Commit");
            $this->_verbose($result);
            // Common error check
            $this->checkError($result, $output);
        } 

        $this->_verbose("### /Git Process End\n");

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
        $this->_verbose("");
        $this->_verbose("### Composer Process Start");

        // Path
        $path = (isset($config['path'])) ? $config['path'] : './';
        // Alternative multiple composer option
        $paths = is_array($path) ? $path : [$path];
        $isSinglePath = (count($paths)<=1) ? true : false;

        // Each composer path with same setting
        foreach ($paths as $key => $path) {
            
            $path = $this->_getAbsolutePath($path);
        
            $cmd = $config['command'];
            // Shell execution
            $result = $this->_cmd($cmd, $output, $path);

            $this->_verbose("### Composer Process Result");
            $this->_verbose($output);

            /**
             * Check error
             */
            if (!$result) {
                // Error
                $this->_verbose($output);
                // Single or multiple
                if ($isSinglePath) {
                    // Single path does not show the key
                    $this->_error("Composer");
                } else {
                    // Multiple paths shows current info
                    $this->_error("Composer #{$key} with path: {$path}");
                }
            }

        }

        $this->_verbose("### /Composer Process End\n");

        $this->_done("Composer");
    }

    /**
     * Test Process
     */
    public function runTest($config=null)
    {
        if (!$config) {
            if (!isset($this->_config['test'])) {
                return;
            }
            
            // Test Config
            $config = &$this->_config['test'];
        }
        
        // Check enabled
        if (!$config || empty($config['enabled']) ) {
            return;
        }
        
        // Commend required
        if (!isset($config['command'])) {
            $this->_error("Test (Config `command` not found)");
        }

        $name = (isset($config['name'])) ? $config['name'] : $config['command'];

        // Start process
        $this->_verbose("");
        $this->_verbose("### Test `{$name}` Process Start");

        // command
        $cmd = $this->_getAbsolutePath($config['command']);

        $configuration = (isset($config['configuration'])) ? $this->_getAbsolutePath($config['configuration']) : null;

        switch ($type = isset($config['type']) ? $config['type'] : null) {
            
            case 'phpunit':
            default:
                
                $cmd = ($configuration) ? "{$cmd} -c {$configuration}" : $cmd;
                break;
        }
        
        // Shell execution
        $result = $this->_cmd($cmd, $output);

        $this->_verbose("### Test `{$name}` Process Result");
        $this->_verbose($output);

        // Failures check
        $this->checkError($result, $output);

        $this->_verbose("### /Test Process End\n");

        $this->_done("Test `{$name}`");
    }

    /**
     * Test Process
     */
    public function runTests()
    {
        if (!isset($this->_config['tests'])) {
            return;
        }
        
        // Tests Config
        $configs = &$this->_config['tests'];

        if (!is_array($configs)) {
            $this->_error("Tests (Config must be array)");
        }

        foreach ($configs as $key => $config) {
            $this->runTest($config);
        }
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

            // Format compatibility
            $cmd = is_array($cmd) ? $cmd : ['command' => $cmd];
            
            $this->_verbose("");
            $this->_verbose("### Command:{$key} Process Start");
            
            // Format command
            $command = "{$cmd['command']};";
            $result = $this->_cmd($command, $output, true);

            // Check
            if (!$result) {
                $this->_verbose($output);
                $this->_error("Command:{$key}");
            }
            
            $this->_verbose("### Command:{$key} Process Result");
            $this->_verbose($output);
            $this->_verbose("### Command:{$key} Process Start");

            $this->_done("Commands {$trigger}: {$key}");
        }
    }

    /**
     * Deploy Process
     */
    public function runDeploy()
    {
        // Config
        $config = isset( $this->_config['rsync']) ?  $this->_config['rsync'] : [];

        // Default config
        $defaultConfig = [
            'enabled' => true,
            'params' => '-av --delete',
            'timeout' => 15,
        ];
        
        // Config init
        $config = array_merge($defaultConfig, $this->_config['rsync']);
        
        // Check enabled
        if (!$config['enabled']) {
            return;
        }

        /**
         * Command builder
         */
        $rsyncCmd = 'rsync ' . $config['params'];

        // Add exclude
        $excludeFiles = $this->_config['exclude'];
        foreach ((array)$excludeFiles as $key => $file) {
            $rsyncCmd .= " --exclude \"{$file}\"";
        }

        // IdentityFile
        $identityFile = isset($config['identityFile']) 
            ? $config['identityFile'] 
            : null;
        if ($identityFile && file_exists($identityFile)) {
            $rsyncCmd .= " -e \"ssh -i {$identityFile}\"";
        } 
        elseif ($identityFile) {
            $this->_error("Deploy (IdentityFile not found: {$identityFile})");
        }

        // Common parameters
        $rsyncCmd = sprintf("%s --timeout=%d %s",
            $rsyncCmd,
            $config['timeout'],
            $this->_config['source']
        );

        /**
         * Process
         */
        foreach ($this->_config['servers'] as $key => $server) {         

            // Info display
            $this->_verbose("");
            $this->_verbose("### Rsync Process Info");
            $this->_verbose('[Process]: '.($key+1));
            $this->_verbose('[Server ]: '.$server);
            $this->_verbose('[User   ]: '.$this->_config['user']['remote']);
            $this->_verbose('[Source ]: '.$this->_config['source']);
            $this->_verbose('[Remote ]: '.$this->_config['destination']);

            // Rsync destination building for each server
            $cmd = sprintf("%s --no-owner --no-group %s@%s:%s",
                $rsyncCmd,
                $this->_config['user']['remote'],
                $server,
                $this->_config['destination'] 
            );
            
            $this->_verbose('[Command]: '.$cmd);

            // Shell execution
            $result = $this->_cmd($cmd, $output);

            $this->_verbose("### Rsync Process Result");
            $this->_verbose("--------------------------");
            $this->_verbose($output);
            $this->_verbose("----------------------------");
            $this->_verbose("");

            /**
             * Check error
             */
            // Success only: sending incremental file list
            if (!$result) {
                // Error
                $this->_error("Deploy to {$server}");

            } else {

                // Sleep option per each deployed server
                if (isset($config['sleepSeconds'])) {
                    
                    sleep((int)$config['sleepSeconds']);
                }

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

        $config['user']['local'] = is_string($config['user']) ? $config['user'] : $config['user']['local'];
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
        $this->_result("Successful Excuted Task: {$string}");
    }

    /**
     * Response for error
     * 
     * @param string $string
     */
    private function _error($string)
    {
        $this->_result("Failing Excuted Task: {$string}");
        if (!isset($this->_config['verbose']) || !$this->_config['verbose']) {
            $this->_result("(Use -v --verbose parameter to display error message)");
        }
        exit;
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
     * @param string $resultText
     * @param bool|string cd into source directory first (CentOS issue), string for customization
     * @return mixed Response
     */
    private function _cmd($cmd, &$resultText='', $cdSource=false)
    {
        // Clear rtrim
        $cmd = rtrim($cmd, ';');
        
        if ($cdSource) { 
            // Get path with the determination
            $path = ($cdSource===true) ? $this->_config['source'] : $cdSource;
            $cmd = "cd {$path};{$cmd}";
        }

        return $this->_exec($cmd, $resultText);
    }

    /**
     * Result response
     * 
     * @param string $string
     */
    private function _result($string='')
    {
        $this->_response .= $string . "\n";
        $this->_print($string);
    }

    /**
     * Verbose response
     * 
     * @param string $string
     */
    private function _verbose($string='')
    {
        if (isset($this->_config['verbose']) && $this->_config['verbose']) {
            $this->_result($string);
        }
    }

    /**
     * check error for Git
     *
     * @param boolean $result Command result
     * @param string $output Result text
     * @return void
     */
    private function checkError($result, $output)
    {
        if (!$result) {
            
            $this->_verbose($output);
            $this->_error("Git");
        }
    }
}
