<?php


namespace App\Helpers\TimeIntervalReports\Reports;

use App\Models\TimeInterval;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class DashboardLargeReportBuilder
{
    private string $pathToIntermediateFile;
    private LoggerInterface $logger;

    public function __construct(string $pathToIntermediateFile, LoggerInterface $logger)
    {
        $this->pathToIntermediateFile = $pathToIntermediateFile;
        $this->logger = $logger;
    }

    /**
     * @param Collection|TimeInterval[] $intervals
     * @throws RuntimeException
     */
    public function build(Collection $intervals): void
    {
        $report = $this->extractPrecalculatedData();
        $report = $this->intervalsToProjects($intervals, $report);
        $this->exportPrecalculatedData($report);
    }

    /**
     * @return array
     * @throws RuntimeException
     */
    public function getBuiltReport(): array
    {
        return $this->extractPrecalculatedData();
    }

    private function extractPrecalculatedData(): array
    {
        try {
            if (file_exists($this->pathToIntermediateFile)) {
                $projects = json_decode(
                    file_get_contents($this->pathToIntermediateFile),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );
            } else {
                $projects = [];
            }
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                "Reading from the intermediate file %s was failed%s%s%s%s",
                $this->pathToIntermediateFile,
                PHP_EOL,
                $e->getMessage(),
                PHP_EOL,
                $e->getTraceAsString()
            ));

            throw new RuntimeException('Reading from the intermediate file', 0, $e);
        }

        return $projects;
    }

    /**
     * @param Collection|TimeInterval[] $intervals
     * @param array $projects
     * @return array
     * @throws RuntimeException
     */
    private function intervalsToProjects(Collection $intervals, array $projects = []): array
    {
        try {
            foreach ($intervals as $interval) {
                $user = $interval->user;
                $task = $interval->task;
                $project = $task->project;
                $diff = strtotime($interval->end_at) - strtotime($interval->start_at);
                $projects[$project->id]['name'] = $project->name;
                $projects[$project->id]['users'][$user->id]['name'] = $user->full_name;
                $projects[$project->id]['users'][$user->id]['tasks'][$task->id]['name'] = $task->task_name;
                $alreadyAddedTime = $projects[$project->id]['users'][$user->id]['tasks'][$task->id]['time'] ?? 0;
                $alreadyAddedTime += $diff;
                $projects[$project->id]['users'][$user->id]['tasks'][$task->id]['time'] = $alreadyAddedTime;
            }

            return $projects;
        } catch (Throwable $throwable) {
            $message = 'Operation transform intervals to projects report was failed.';
            $this->logger->alert(sprintf(
                "%s%s%s%s%s",
                $message,
                PHP_EOL,
                $throwable->getMessage(),
                PHP_EOL,
                $throwable->getTraceAsString()
            ));

            throw new RuntimeException($message, 0, $throwable);
        }
    }

    /**
     * @param array $projects
     * @throws RuntimeException
     */
    private function exportPrecalculatedData(array $projects): void
    {
        try {
            file_put_contents($this->pathToIntermediateFile, json_encode($projects, JSON_THROW_ON_ERROR));
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                "Writing the to intermediate file was failed %s%s%s%s",
                PHP_EOL,
                $e->getMessage(),
                PHP_EOL,
                $e->getTraceAsString()
            ));

            throw new RuntimeException('Writing the to intermediate file was failed', 0, $e);
        }
    }
}
