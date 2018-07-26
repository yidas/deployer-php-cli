<?php

class Installer
{
    /**
     * For Composer post-create-project-cmd
     *
     * @return void
     */
    public static function postCreateProject()
    {
        self::processChmod();
        self::processLn();
    }

    /**
     * Make command for Deployer
     *
     * @return void
     */
    public static function processChmod()
    {
        $result = chmod(__DIR__ . '/deployer', 0755);

        if ($result) {
            echo "chmod deployer success.\n";
        } else {
            echo "chmod deployer failed.\n";
        }
    }

    /**
     * Make command for Deployer
     *
     * @return void
     */
    public static function processLn()
    {
        $binDir = '/usr/bin';
        $ln = "{$binDir}/deployer";
        
        if (!is_dir($binDir)) {
            echo "{$binDir} doesn't exist for {$ln}.\n";
            return;
        }

        if (file_exists($ln)) {
            echo "{$ln} already exist.\n";
            return;
        }
        
        $cmd = "sudo ln -s " . __DIR__ . "/deployer {$binDir}/deployer";
        exec($cmd);

        if (file_exists($ln)) {
            echo "Make deployer in {$binDir} success.\n";
            echo "You could run by command: `$ deployer`\n";
        } else {
            echo "Make deployer in {$binDir} failed.\n";
            echo "You could manual execute `$cmd`\n";
        }
    }
}
