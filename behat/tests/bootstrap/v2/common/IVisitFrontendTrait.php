<?php declare(strict_types=1);


namespace DigidepsBehat\v2\Common;

trait IVisitFrontendTrait
{
    /**
     * @When I visit the report submitted page
     */
    public function iVisitReportSubmissionPage()
    {
        if (is_null($this->loggedInUserDetails->getPreviousReportId())) {
            $this->throwContextualException(
                "Logged in user doesn't have a previous report ID associated with them. Try using a user that has submitted a report instead."
            );
        }

        $submittedReportUrl = $this->getReportSubmittedUrl($this->loggedInUserDetails->getPreviousReportId());
        $this->visitFrontendPath($submittedReportUrl);
    }

    /**
     * @When I visit the accounts report section
     */
    public function iViewAccountsSection()
    {
        $activeReportId = $this->loggedInUserDetails->getCurrentReportId();
        $reportSectionUrl = sprintf(self::REPORT_SECTION_ENDPOINT, $this->reportUrlPrefix, $activeReportId, 'bank-accounts');
        $this->visitPath($reportSectionUrl);
    }

    /**
     * @When I visit the accounts summary section
     */
    public function iViewAccountsSummarySection()
    {
        $this->visitPath($this->getAccountsSummaryUrl($this->loggedInUserDetails->getCurrentReportId()));
    }
}
