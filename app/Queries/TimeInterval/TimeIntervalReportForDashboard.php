<?php


namespace App\Queries\TimeInterval;

use App\Models\TimeInterval;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TimeIntervalReportForDashboard
{
    /**
     * @param array $params
     * @return Collection|TimeInterval[]
     */
    public function searchByParams(array $params): Collection
    {
        return $this->buildQuery($params)->get();
    }

    /**
     * @param array $params
     * @return EloquentBuilder|TimeInterval
     */
    public function buildQuery(array $params)
    {
        $timezoneOffset = $params['timezoneOffset'];
        $userIds = $params['userIds'];
        $projectIds = $params['projectIds'] ?? [];
        $startAt = $params['startAt'];
        $endAt = $params['endAt'];

        $intervalsQb = TimeInterval::with('task', 'task.project')
            ->select(
                'user_id',
                'id',
                'task_id',
                'is_manual',
                DB::raw("CONVERT_TZ(start_at, '+00:00', '{$timezoneOffset}') as start_at"),
                DB::raw("CONVERT_TZ(end_at, '+00:00', '{$timezoneOffset}') as end_at"),
                DB::raw('TIMESTAMPDIFF(SECOND, start_at, end_at) as duration'),
                DB::raw('UNIX_TIMESTAMP(start_at) as raw_start_at'),
                DB::raw('UNIX_TIMESTAMP(end_at) as raw_end_at')
            )
            ->whereIn('user_id', $userIds)
            ->where('start_at', '>=', $startAt)
            ->where('start_at', '<', $endAt)
            ->whereNull('deleted_at')
            ->orderBy('user_id')
            ->orderBy('task_id')
            ->orderBy('start_at');

        if (!empty($projectIds)) {
            $intervalsQb = $intervalsQb->whereHas('task', function ($query) use ($projectIds) {
                $query->whereIn('tasks.project_id', $projectIds);
            });
        }

        return $intervalsQb;
    }
}
