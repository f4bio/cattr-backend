<?php


namespace App\Helpers\ExternalServices\Google\Sheets\Reports\TimeIntervals;

use App\Helpers\Time\Time;
use App\Models\Task;
use App\Models\User;
use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_BatchUpdateSpreadsheetRequest;
use Google_Service_Sheets_Request;
use Google_Service_Sheets_Spreadsheet;
use Google_Service_Sheets_ValueRange;

class DashboardReportBuilder
{
    private Google_Client $googleClient;

    public function __construct(Google_Client $googleClient)
    {
        $this->googleClient = $googleClient;
    }

    /**
     * @param array $intervals
     * @param string $title
     * @return string - created sheet's URL
     * @see \App\Helpers\TimeIntervalReports\Reports\DashboardReportBuilder::build() - for build $intervals
     */
    public function build(array $intervals, string $title): string
    {
        $googleSheetService = new Google_Service_Sheets($this->googleClient);
        $spreadsheet = new Google_Service_Sheets_Spreadsheet([
            'properties' => [
                'title' => $title
            ]
        ]);

        $spreadsheet = $googleSheetService->spreadsheets->create($spreadsheet, ['fields' => 'spreadsheetId']);

        $tableRangeValues = $this->buildTable($intervals);
        $googleSheetService->spreadsheets_values->update(
            $spreadsheet->getSpreadsheetId(),
            'A1:E',
            $tableRangeValues,
            ["valueInputOption" => "RAW"]
        );

        $requests = [
            new Google_Service_Sheets_Request([
                'repeatCell' => [
                    "range" => [
                        "startRowIndex" => 0,
                        "endRowIndex" => 1,
                        "startColumnIndex" => 0,
                        "endColumnIndex" => 5
                    ],
                    // https://developers.google.com/sheets/api/reference/rest/v4/spreadsheets#CellFormat
                    "cell" => [
                        "userEnteredFormat" => [
                            // https://developers.google.com/sheets/api/reference/rest/v4/spreadsheets#Color
                            "backgroundColor" => [
                                "red" => 0.9,
                                "green" => 0.99,
                                "blue" => 0.86,
                            ],
                            // https://developers.google.com/sheets/api/reference/rest/v4/spreadsheets#HorizontalAlign
                            "horizontalAlignment" => "CENTER",
                            // https://developers.google.com/sheets/api/reference/rest/v4/spreadsheets#textformat
                            "textFormat" => [
                                "bold" => true,
                            ]
                        ]
                    ],
                    "fields" => "UserEnteredFormat(backgroundColor,horizontalAlignment,padding,textFormat)"
                ]
            ]),
        ];
        $batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
            'requests' => $requests
        ]);

        $googleSheetService->spreadsheets->batchUpdate($spreadsheet->getSpreadsheetId(), $batchUpdateRequest);

        return $this->buildUrlToSheetById($spreadsheet->getSpreadsheetId());
    }

    private function buildUrlToSheetById(string $sheetId): string
    {
        return sprintf("https://docs.google.com/spreadsheets/d/%s", $sheetId);
    }

    private function buildTable(array $intervals): Google_Service_Sheets_ValueRange
    {
        $tableValues[] = ["Project", "User", "Task", "Time", "Hours (decimal)"];

        foreach ($this->transformIntervalsToTableBody($intervals) as $row) {
            $tableValues[] = $row;
        }

        return new Google_Service_Sheets_ValueRange([
            "values" => $tableValues,
        ]);
    }

    private function transformIntervalsToTableBody(array $intervals): iterable
    {
        return $this->transformProjectsToMatrix($this->transformIntervalsToProjects($intervals));
    }

    private function transformIntervalsToProjects(array $intervals): array
    {
        $projects = [];
        $userIds = [];

        foreach ($intervals as $userIntervals) {
            foreach ($userIntervals['intervals'] as $interval) {
                /* @var Task $task */
                $task = $interval['task'];
                $duration = $interval['duration'];
                $project = $task->project;
                $projects[$project->id]['name'] = $project->name;
                $projects[$project->id]['users'][$interval['user_id']]['tasks'][$task->id]['name'] = $task->task_name;
                $projects[$project->id]['users'][$interval['user_id']]['tasks'][$task->id]['duration'] = $duration;
                $userIds[] = $interval['user_id'];
            }
        }

        return $this->transformUserIdToNameForProjects($projects, array_unique($userIds));
    }

    private function transformUserIdToNameForProjects(array $projects, array $userIds): array
    {
        $users = User::whereIn('id', $userIds)->get();

        foreach ($users as $user) {
            foreach ($projects as $projectId => $project) {
                $projects[$projectId]['users'][$user->id]['name'] = $user->full_name;
            }
        }

        return $projects;
    }

    private function transformProjectsToMatrix(array $projects): iterable
    {
        $totalDuration = new Time(0);

        foreach ($projects as $projectId => $project) {
            $projectName = $project['name'];
            $projectDuration = new Time(0);
            $needSkipProject = false;

            foreach ($project['users'] as $userId => $user) {
                $userName = $user['name'];
                $needSkipUser = false;

                foreach ($user['tasks'] as $task) {
                    $taskName = $task['name'];
                    $taskDuration = new Time($task['duration']);
                    $projectDuration = $projectDuration->addTime($taskDuration);

                    yield [
                        $needSkipProject ? '' : $projectName,
                        $needSkipUser ? '' : $userName,
                        $taskName,
                        $taskDuration->toString(),
                        $taskDuration->toDecimalFormat(),
                    ];

                    $needSkipUser = true;
                }

                $needSkipProject = true;
            }

            yield ['', '', '', '', '',];

            yield [
                '',
                '',
                sprintf("Subtotal for %s", $projectName),
                $projectDuration->toString(),
                $projectDuration->toDecimalFormat(),
            ];

            $totalDuration = $totalDuration->addTime($projectDuration);

            yield ['', '', '', '', '',];
        }

        if (!empty($projects)) {
            yield [
                '',
                '',
                'Total',
                $totalDuration->toString(),
                $totalDuration->toDecimalFormat(),
            ];
        }

        return [];
    }
}
