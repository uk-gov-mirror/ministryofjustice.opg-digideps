<?php

namespace DigidepsBehat;

trait LinksTrait
{
    /**
     * @Then the :text link url should contain ":expectedLink"
     */
    public function linkWithTextContains($text, $expectedLink)
    {
        $linksElementsFound = $this->getSession()->getPage()->findAll('xpath', '//a[text()="' . $text . '"]');
        $count = count($linksElementsFound);

        if (count($linksElementsFound) === 0) {
            throw new \RuntimeException('Element not found');
        }

        if (count($linksElementsFound) > 1) {
            throw new \RuntimeException('Returned multiple elements');
        }

        $href = $linksElementsFound[0]->getAttribute('href');

        if (strpos($href, $expectedLink) === false) {
            throw new \Exception("Link: $href does not contain $expectedLink");
        }
    }

    public function visitBehatLink($link)
    {
        $secret = md5('behat-dd-' . getenv('SECRET'));

        $this->visit("/behat/{$secret}/{$link}");
    }

    public function visitBehatAdminLink($link)
    {
        $secret = md5('behat-dd-' . getenv('SECRET'));

        $adminUrl = $this->getAdminUrl();
        $this->visitPath($adminUrl . "/behat/{$secret}/{$link}");
    }

    /**
     * Click on element with attribute [behat-link=:link].
     *
     * @When I click on ":link"
     */
    public function clickOnBehatLink($link)
    {
        // if multiple links are specified (comma-separated), click on all of them
        if (strpos($link, ',') !== false) {
            foreach (explode(',', $link) as $singleLink) {
                $this->clickOnBehatLink(trim($singleLink));
            }

            return;
        }

        // find link inside the region
        $linkSelector = self::behatElementToCssSelector($link, 'link');
        $linksElementsFound = $this->getSession()->getPage()->findAll('css', $linkSelector);
        $count = count($linksElementsFound);

        if (count($linksElementsFound) > 1) {
            throw new \RuntimeException("Found more than one $linkSelector element in the page ($count). Interrupted");
        }
        if (count($linksElementsFound) === 0) {
            $this->clickOnHashLink($link);

            return;
        }

        // click on the found link
        $this->scrollTo($linkSelector);
        $linksElementsFound[0]->click();
    }

    private function clickOnHashLink($link)
    {
        $linksElementsFound = $this->getSession()->getPage()->findAll('css', '#' . $link);
        if (count($linksElementsFound) > 1) {
            throw new \RuntimeException("Found more than a #$link element in the page. Interrupted");
        }
        if (count($linksElementsFound) === 0) {
            throw new \RuntimeException("Element #$link not found. Interrupted");
        }

        // click on the found link
        $this->scrollTo('#' . $link);
        $linksElementsFound[0]->click();
    }

    /**
     * Click on element with attribute [behat-link=:link].
     *
     * @When I click on link with text :text
     */
    public function clickOnLinkWithText($text)
    {
        $linksElementsFound = $this->getSession()->getPage()->find('xpath', '//*[text()="' . $text . '"]');
        $count = count($linksElementsFound);

        if ($count === 0) {
            throw new \RuntimeException('Element not found');
        }

        if ($count > 1) {
            throw new \RuntimeException('Returned multiple elements');
        }

        // click on the found link
        $linksElementsFound[0]->click();
    }

    /**
     * Click on element with attribute [behat-link=:link].
     *
     * @When I press :text in the :region region
     */
    public function clickOnLinkWithTextInRegion($text, $region)
    {
        $region = $this->findRegion($region);

        $linksElementsFound = $region->findAll('xpath', '//a[normalize-space(text())="' . $text . '"]');
        $count = count($linksElementsFound);
        if ($count === 0) {
            throw new \RuntimeException('Element not found');
        }

        if ($count > 1) {
            throw new \RuntimeException('Returned multiple elements');
        }

        // click on the found link
        $linksElementsFound[0]->click();
    }

    private function findRegion($region)
    {
        // find region
        $regionSelector = '#' . $region . ', ' . self::behatElementToCssSelector($region, 'region');
        $regionsFound = $this->getSession()->getPage()->findAll('css', $regionSelector);
        if (count($regionsFound) > 1) {
            throw new \RuntimeException("Found more than one $regionSelector");
        }
        if (count($regionsFound) === 0) {
            throw new \RuntimeException("Region $regionSelector not found.");
        }

        return $regionsFound[0];
    }

    /**
     * Click on element with attribute [behat-link=:link] inside the element with attribute [behat-region=:region].
     *
     * @When I click on :link in the :region region
     */
    public function clickLinkInsideElement($link, $region, $theFirst = false)
    {
        $linkSelector = self::behatElementToCssSelector($link, 'link');

        $regionSelector = $this->findRegion($region);
        $linksElementsFound = $regionSelector->findAll('css', $linkSelector);
        if (count($linksElementsFound) > 1 && !$theFirst) {
            throw new \RuntimeException("Found more than a $linkSelector element inside $regionSelector . Interrupted");
        }
        if (count($linksElementsFound) === 0) {
            throw new \RuntimeException("Element $linkSelector not found inside $regionSelector . Interrupted");
        }

        // click on the found link
        $linksElementsFound[0]->click();
    }

    /**
     * @When I click on the first :link in the :region region
     */
    public function clickFirstLinkInsideElement($link, $region)
    {
        $this->clickLinkInsideElement($link, $region, true);
    }

    /**
     * @Then the :text link, in the :region region, url should contain ":expectedLink"
     * @Then the :text link, in the :region, url should contain ":expectedLink"
     */
    public function linkWithTextInRegionContains($text, $expectedLink, $region)
    {
        $region = $this->findRegion($region);

        $linksElementsFound = $region->findAll('xpath', '//a[normalize-space(text())="' . $text . '"]');
        $count = count($linksElementsFound);

        if ($count === 0) {
            throw new \RuntimeException('Element not found');
        }

        if ($count > 1) {
            throw new \RuntimeException('Returned multiple elements');
        }

        $href = $linksElementsFound[0]->getAttribute('href');

        if (strpos($href, $expectedLink) === false) {
            throw new \Exception("Link: $href does not contain $expectedLink");
        }
    }

    /**
     * @Given I click the :arg1 element
     */
    public function iClickTheElement($selector)
    {
        $page = $this->getSession()->getPage();
        $element = $page->find('css', $selector);

        if (empty($element)) {
            throw new \Exception("No html element found for the selector ('$selector')");
        }

        $element->click();
    }

    /**
     * Click on a specific link in a row that contains a specified string
     *
     * @When I click on :linkText in the :rowText row
     */
    public function clickLinkInsideATableRow(string $linkText, string $rowText)
    {
        $row = $this->findRowByText($rowText);
        $link = $row->findLink($linkText);

        if (null === $link) {
            throw new \Exception('Cannot find link in row with text: '.$linkText);
        }

        $link->click();
    }

    /**
     * @param $rowText
     */
    private function findRowByText($rowText)
    {
        $row = $this->getSession()->getPage()->find('css', sprintf('table tr:contains("%s")', $rowText));

        if (null === $row) {
            throw new \Exception('Cannot find a table row with text: ' . $rowText);
        }

        return $row;
    }
}
