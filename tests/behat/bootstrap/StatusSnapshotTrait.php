<?php

namespace DigidepsBehat;

use Behat\Testwork\Hook\Scope\BeforeSuiteScope;

trait StatusSnapshotTrait
{

    /**
     * @Then I save the application status into :status
     */
    public static function iSaveTheApplicationStatusInto($status)
    {
        $sqlFile = self::getSnapshotPath($status);
        exec("sudo -u postgres pg_dump -U postgres " . self::$dbName . " --clean > {$sqlFile}");
    }

    /**
     * @Then I load the application status from :status
     */
    public static function iLoadtheApplicationStatusFrom($status)
    {
        $sqlFile = self::getSnapshotPath($status);
        if (!file_exists($sqlFile)) {
            $error = "File $sqlFile not found. Re-run the full behat suite to recreate the missing snapshots.";
            echo $error;
            //throw new \RuntimeException($error);
        }
        exec("sudo -u postgres psql -U postgres  " . self::$dbName . " < {$sqlFile}");
    }

    /**
     * @param string $name
     * 
     * @return string
     */
    private static function getSnapshotPath($name)
    {
        return getcwd()
                . '/misc/tmp/behat-snapshot-'
                . strtolower(preg_replace('/[^\w]+/', '-', $name))
                . '.sql';
    }

}