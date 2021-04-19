<?php declare(strict_types=1);

namespace DigidepsBehat\v2\Common;

use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Behat\Tester\Result\ExecutedStepResult;

trait DebugTrait
{
    private static $DEBUG_SNAPSHOT_DIR = '/tmp/html';

    /**
     * @Then /^wtf$/
     */
    public function wtf()
    {
        $this->printLastResponse();
    }

    /**
     * Clean the snapshot folder before running a suite
     *
     * @BeforeSuite
     */
    public static function cleanDebugSnapshots()
    {
        $handle = opendir(self::$DEBUG_SNAPSHOT_DIR);

        while (false !== ($file = readdir($handle))) {
            $path = self::$DEBUG_SNAPSHOT_DIR . '/' . $file;
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    /**
     * Call debug() when an exception is thrown after a step.
     *
     * @AfterStep
     */
    public function debugOnException(AfterStepScope $scope)
    {
        if (($result = $scope->getTestResult())
            && $result instanceof ExecutedStepResult
            && $result->hasException()
        ) {
            $feature = basename($scope->getFeature()->getFile());
            $this->debug($feature);
        }
    }

    /**
     * @Then I save the page as :name
     */
    public function debug($name)
    {
        for ($i = 1; $i < 100; ++$i) {
            $iPadded = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $filename = self::$DEBUG_SNAPSHOT_DIR . '/behat-response-' . $name . '-' . $iPadded . '.html';
            if (!file_exists($filename)) {
                break;
            }
        }

        $session = $this->getSession();

        $pageContent = $session->getPage()->getContent();
        $data = str_replace('"/assets', '"https://digideps.local/assets', $pageContent);

        $bytes = file_put_contents($filename, $data);
        $file = basename($filename);

        echo "** Test failed **\n";
        echo 'Url: ' . $session->getCurrentUrl() . "\n";
        echo "Response saved ({$bytes} bytes):\n";
        echo "$file";
    }

    public function bespokeAssert(
        $expected,
        $found,
        string $comparisonSubject,
        bool $exactMatch
    ) {
        $message = <<<MESSAGE

============================
Expecting: %s
Found: %s

Subject of Comparison: %s
Page URL: %s
============================

MESSAGE;
        $foundFormatted = strval(trim(strtolower($found)));
        $expectedFormatted = strval(trim(strtolower($expected)));
        assert(
            $exactMatch ? $foundFormatted == $expectedFormatted : str_contains($foundFormatted, $expectedFormatted),
            sprintf(
                $message,
                $expectedFormatted,
                $exactMatch ? $foundFormatted : 'Not Found',
                $comparisonSubject,
                $this->getCurrentUrl()
            )
        );
//        return sprintf($message, strval($expecting), strval($found), $comparisonSubject, $url);
    }
}
