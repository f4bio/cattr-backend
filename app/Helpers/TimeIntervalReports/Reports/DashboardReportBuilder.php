<?php


namespace App\Helpers\TimeIntervalReports\Reports;

use App\Models\TimeInterval;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardReportBuilder
{
    /**
     * @param Collection|TimeInterval[] $intervals
     * @return array
     */
    public function build(Collection $intervals): array
    {
        $users = [];
        $previousInterval = false;
        foreach ($intervals as $interval) {
            $user_id = (int)$interval->user_id;
            $duration = (int)$interval->duration;

            if (!isset($users[$user_id])) {
                $users[$user_id] = [
                    'user_id' => $user_id,
                    'intervals' => [],
                    'duration' => 0,
                ];
            }

            $intervalData = [
                'id' => (int)$interval->id,
                'ids' => [(int)$interval->id],
                'user_id' => $user_id,
                'is_manual' => (int)$interval->is_manual,
                'duration' => $duration,
                'start_at' => Carbon::parse($interval->start_at)->toIso8601String(),
                'end_at' => Carbon::parse($interval->end_at)->toIso8601String(),
                'task' => $interval->task,
            ];

            // Merge with the previous interval if it is consecutive and has the same task
            if ($previousInterval !== false
                && (int)$interval->raw_start_at - (int)$previousInterval->raw_end_at <= 5
                && $interval->is_manual === $previousInterval->is_manual
                && $interval->user_id === $previousInterval->user_id
                && $interval->task_id === $previousInterval->task_id) {
                $previousIndex = count($users[$user_id]['intervals']) - 1;
                $users[$user_id]['intervals'][$previousIndex]['ids'][] = $intervalData['id'];
                $users[$user_id]['intervals'][$previousIndex]['duration'] += $intervalData['duration'];
                $users[$user_id]['intervals'][$previousIndex]['end_at'] = $intervalData['end_at'];
            } else {
                $users[$user_id]['intervals'][] = $intervalData;
            }

            $users[$user_id]['duration'] += $duration;
            $previousInterval = $interval;
        }

        return $users;
    }
}
