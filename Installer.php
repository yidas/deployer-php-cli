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
}
