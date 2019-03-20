<?php

/**
 * Shell Console
 * 
 * @author  Nick Tsai <myintaer@gmail.com>
 */
trait ShellConsole
{
    /** 
     * Command
     * 
     * @param string $cmd
     * @return mixed Response
     */
    // private function _exec($cmd)
    // {
    //     return shell_exec($cmd);
    // }

    /**
     * Execute command line with status returning
     *
     * @param string $cmd
     * @param string $resultText
     * @param array $output
     * @param integer $errorCode
     * @return boolean Last command success or not
     */
    private function _exec($cmd, &$resultText='', &$output='', &$errorCode='')
    {
        $cmd = trim($cmd);
        $cmd = rtrim($cmd, ';');
    
        // stdout
        $cmd = "{$cmd} 2>&1;";
        exec($cmd, $output, $errorCode);
    
        // Build result text
        foreach ($output as $key => $string) {
            $resultText .= "{$string}\r\n";
        }
    
        return (!$errorCode) ? true : false;
    }
    
    /** 
     * Get username
     * 
     * @return string User
     */
    private function _getUser()
    {
        $this->_exec('echo $USER;', $user);
        
        return trim($user);
    }

    /**
     * Response
     * 
     * @param string $string
     */
    private function _print($string)
    {
        echo "{$string}\n";
    }
}