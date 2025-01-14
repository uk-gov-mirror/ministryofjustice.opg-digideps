<?php declare(strict_types=1);

namespace DigidepsBehat\v2\Common;

trait AssertTrait
{
    public function assertStringContainsString(
        $expected,
        $found,
        string $comparisonSubject
    ) {
        $foundFormatted = strval(trim(strtolower($found)));
        $expectedFormatted = strval(trim(strtolower($expected)));
        assert(
            str_contains($foundFormatted, $expectedFormatted),
            $this->getAssertMessage($expectedFormatted, 'Not Found', $comparisonSubject)
        );
    }

    public function assertStringEqualsString(
        $expected,
        $found,
        string $comparisonSubject
    ) {
        $foundFormatted = strval(trim(strtolower($found)));
        $expectedFormatted = strval(trim(strtolower($expected)));
        assert(
            $foundFormatted == $expectedFormatted,
            $this->getAssertMessage($expectedFormatted, $foundFormatted, $comparisonSubject)
        );
    }

    private function getAssertMessage(
        $expected,
        $found,
        string $comparisonSubject
    ) {
        $message = <<<MESSAGE

============================
Expecting: %s
Found: %s

Subject of Comparison: %s
Page URL: %s
============================

MESSAGE;

        return sprintf(
            $message,
            $expected,
            $found,
            $comparisonSubject,
            $this->getCurrentUrl()
        );
    }
}
