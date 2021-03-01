<?php


namespace App\Http\Requests\Google\Sheets;

use App\Http\Requests\FormRequest;
use App\Models\Property;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

final class ExportReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'user_ids' => 'exists:users,id|array',
            'project_ids' => 'nullable|exists:projects,id|array',
            'start_at' => 'required|date',
            'end_at' => 'required|date',
        ];
    }

    public function toState(): array
    {
        $state = $this->prepareParams();
        $state['instanceId'] = Property::getInstanceId();
        $state['userId'] = $this->getAuthUserId();
        $state['successRedirect'] = sprintf(
            "http://%s/time-intervals/dashboard/export-in-sheets/end?%s",
            config('app.domain'),
            http_build_query(['state' => base64_encode(json_encode($state, JSON_THROW_ON_ERROR))])
        );

        return $state;
    }

    private function prepareParams(): array
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
