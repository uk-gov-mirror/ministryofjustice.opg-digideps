<?php

namespace DigidepsBehat\ReportManagement;

use DigidepsBehat\AuthenticationTrait;
use DigidepsBehat\Common\BaseFeatureContext;
use DigidepsBehat\Common\CourtOrderTrait;
use DigidepsBehat\FormTrait;
use DigidepsBehat\LinksTrait;
use DigidepsBehat\RegionTrait;
use DigidepsBehat\ReportTrait;
use DigidepsBehat\SiteNavigationTrait;

class ReportManagementFeatureContext extends BaseFeatureContext
{
    use CourtOrderTrait;
    use LinksTrait;
    use RegionTrait;
    use ReportManagementTrait;
    use ReportTrait;
}