<?php declare(strict_types=1);

namespace DigidepsBehat\CrossBrowser;

use DigidepsBehat\Common\BaseFeatureContext;
use DigidepsBehat\Common\CourtOrderTrait;
use DigidepsBehat\Common\LinksTrait;
use DigidepsBehat\Common\RegionTrait;
use DigidepsBehat\Common\ReportTrait;
use DigidepsBehat\ReportManagement\ReportManagementTrait;

class CrossBrowserFeatureContext extends BaseFeatureContext
{
    use ReportTrait;
    use LinksTrait;
    use RegionTrait;
    use CourtOrderTrait;
    use ReportManagementTrait;
}
