<?php declare(strict_types=1);

namespace DigidepsBehat\v2\Common;

trait AlertsTrait
{
    public function assertOnAlertMessage(string $alertMessage)
    {
        $alertDiv = $this->getSession()->getPage()->find('css', 'div.opg-alert--info');

        if (is_null($alertDiv)) {
            $this->throwContextualException(
                'A div with the class opg-alert--info was not found. This suggests the page is not was expected or a condition to display an alert has not been met'
            );
        }

        $alertHtml = $alertDiv->getHtml();
        $alertMessageFound = str_contains($alertHtml, $alertMessage);

        if (!$alertMessageFound) {
            $this->throwContextualException(
                sprintf(
                    'The alert element did not contain the expected message. Expected: "%s", got (full HTML): %s',
                    $alertMessage,
                    $alertHtml
                )
            );
        }
    }
}
