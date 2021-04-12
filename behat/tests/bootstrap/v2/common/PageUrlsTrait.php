<?php declare(strict_types=1);


namespace DigidepsBehat\v2\Common;

trait PageUrlsTrait
{
    // Frontend
    private string $reportSubmittedUrl = '/report/%s/submitted';
    private string $postSubmissionUserResearchUrl = '/report/%s/post_submission_user_research';
    private string $userResearchSubmittedUrl = '/report/%s/post_submission_user_research/submitted';
    private string $contactsSummaryUrl = '/report/%s/contacts/summary';
    private string $contactsAddUrl = '/report/%s/contacts/add';
    private string $contactsAddAnotherUrl = '/report/%s/contacts/add_another';
    private string $layReportsOverviewUrl = '/lay';

    // Admin
    private string $adminClientSearchUrl = '/admin/client/search';

    // Fixtures
    private string $duplicateClientFixtureUrl = '/admin/fixture/duplicate-client/%s';
    private string $courtOrdersFixtureUrl = '/admin/fixture/court-orders?%s';

    /**
     * @return string
     */
    public function getReportSubmittedUrl(int $reportId): string
    {
        return sprintf($this->reportSubmittedUrl, $reportId);
    }

    /**
     * @return string
     */
    public function getPostSubmissionUserResearchUrl(int $reportId): string
    {
        return sprintf($this->postSubmissionUserResearchUrl, $reportId);
    }

    /**
     * @return string
     */
    public function getUserResearchSubmittedUrl(int $reportId): string
    {
        return sprintf($this->userResearchSubmittedUrl, $reportId);
    }

    /**
     * @return string
     */
    public function getContactsSummaryUrl(int $reportId): string
    {
        return sprintf($this->contactsSummaryUrl, $reportId);
    }

    /**
     * @return string
     */
    public function getContactsAddUrl(int $reportId): string
    {
        return sprintf($this->contactsAddUrl, $reportId);
    }

    /**
     * @return string
     */
    public function getContactsAddAnotherUrl(int $reportId): string
    {
        return sprintf($this->contactsAddAnotherUrl, $reportId);
    }

    /**
     * @return string
     */
    public function getLayReportsOverviewUrl(): string
    {
        return $this->layReportsOverviewUrl;
    }

    /**
     * @return string
     */
    public function getAdminClientSearchUrl(): string
    {
        return $this->adminClientSearchUrl;
    }

    /**
     * @return string
     */
    public function getCourtOrdersFixtureUrl(string $queryString): string
    {
        return sprintf($this->courtOrdersFixtureUrl, $queryString);
    }

    /**
     * @return string
     */
    public function getDuplicateClientFixtureUrl(int $clientId): string
    {
        return sprintf($this->duplicateClientFixtureUrl, $clientId);
    }
}
