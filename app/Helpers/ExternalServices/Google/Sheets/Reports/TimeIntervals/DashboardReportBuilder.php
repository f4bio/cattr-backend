<?php


namespace App\Helpers\ExternalServices\Google\Sheets\Reports\TimeIntervals;

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

        // TODO: build report from $intervals
        $tableRangeValues = $this->buildTable($intervals);
        $googleSheetService->spreadsheets_values->update(
            $spreadsheet->getSpreadsheetId(),
            'A1:E5',
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

        foreach ($this->buildTableBody($intervals) as $row) {
            $tableValues[] = $row;
        }

        return new Google_Service_Sheets_ValueRange([
            "values" => $tableValues,
        ]);
    }

    private function buildTableBody(array $intervals): iterable
    {
        foreach ($intervals as $key => $interval) {
            yield [1, 2, 3, 4, 5];
        }

        return [];
    }
}
