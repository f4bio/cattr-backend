<?php


namespace App\Http\Requests\Google\Sheets;

use App\Http\Requests\FormRequest;
use Carbon\Carbon;

final class ExportReportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_ids' => 'exists:users,id|array',
            'project_ids' => 'nullable|exists:projects,id|array',
            'start_at' => 'required|date',
            'end_at' => 'required|date',
        ];
    }

    public function prepareParams(): array
    {
        $userIds = $this->input('user_ids');
        $projectIds = $this->input('project_ids');
        $timezone = $this->input('timezone') ?: 'UTC';
        $timezoneOffset = (new Carbon())->setTimezone($timezone)->format('P');
        $startAt = Carbon::parse($this->input('start_at'), $timezone)
            ->tz('UTC')
            ->toDateTimeString();
        $endAt = Carbon::parse($this->input('end_at'), $timezone)
            ->tz('UTC')
            ->toDateTimeString();

        return compact(
            'startAt',
            'endAt',
            'timezoneOffset',
            'projectIds',
            'userIds'
        );
    }
}
