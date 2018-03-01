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
    private function _exec($cmd)
    {
        return shell_exec($cmd);
    }
    
    /** 
     * Get username
     * 
     * @return string User
     */
    private function _getUser()
    {
        return trim($this->_exec('echo $USER;'));
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