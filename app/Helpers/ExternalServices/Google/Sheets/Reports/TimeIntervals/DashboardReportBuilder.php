<?php


namespace App\Helpers\ExternalServices\Google\Sheets\Reports\TimeIntervals;

use DateTimeImmutable;
use Google_Client;
use Google_Service_Sheets;
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
     * @return string - created sheet's URL
     * @see \App\Helpers\TimeIntervalReports\Reports\DashboardReportBuilder::build() - for build $intervals
     */
    public function build(array $intervals): string
    {
        $googleSheetService = new Google_Service_Sheets($this->googleClient);
        $spreadsheet = new Google_Service_Sheets_Spreadsheet([
            'properties' => [
                'title' => sprintf("Cattr Report from %s", (new DateTimeImmutable())->format('Y-m-d H:i:s'))
            ]
        ]);

        $spreadsheet = $googleSheetService->spreadsheets->create($spreadsheet, ['fields' => 'spreadsheetId']);

        // TODO: build report from $intervals
        // Add table head
        $tableHead = new Google_Service_Sheets_ValueRange();
        $tableHead->setValues(["values" => ["Project", "User", "Task", "Time", "Hours (decimal)"]]);
        $googleSheetService->spreadsheets_values->update(
            $spreadsheet->getSpreadsheetId(),
            'A1:E1',
            $tableHead,
            ["valueInputOption" => "RAW"]
        );

        return $this->buildUrlToSheetById($spreadsheet->getSpreadsheetId());
    }

    private function buildUrlToSheetById(string $sheetId): string
    {
        return sprintf("https://docs.google.com/spreadsheets/d/%s", $sheetId);
    }
}
