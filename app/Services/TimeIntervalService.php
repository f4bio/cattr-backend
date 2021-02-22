<?php


namespace App\Services;

use App\Helpers\TimeIntervalReports\Reports\DashboardReportBuilder;
use App\Queries\TimeInterval\TimeIntervalReportForDashboard;

class TimeIntervalService
{
    private TimeIntervalReportForDashboard $queryReportForDashboard;
    private DashboardReportBuilder $dashboardReportBuilder;

    public function __construct(
        TimeIntervalReportForDashboard $queryReportForDashboard,
        DashboardReportBuilder $dashboardReportBuilder
    ) {
        $this->queryReportForDashboard = $queryReportForDashboard;
        $this->dashboardReportBuilder = $dashboardReportBuilder;
    }

    public function getReportForDashboard(array $params): array
    {
        return $this->dashboardReportBuilder->build($this->queryReportForDashboard->searchByParams($params));
    }
}
