<?php

namespace App\Http\Controllers\Api\Statistic;

use App\Models\TimeInterval;
use App\Services\TimeIntervalService;
use Carbon\Carbon;
use Filter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Validator;

class DashboardController extends ReportController
{
    public static function getControllerRules(): array
    {
        return [
            'timeIntervals' => 'time-intervals.list',
            'timePerDay' => 'time-intervals.list',
        ];
    }

    public function getEventUniqueNamePart(): string
    {
        return 'dashboard';
    }

    public function timePerDay(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            $this->getValidationRules()
        );

        if ($validator->fails()) {
            return new JsonResponse([
                'error_type' => 'validation',
                'message' => 'Validation error',
                'info' => $validator->errors(),
            ], 400);
        }

        $uids = $request->input('user_ids', []);

        $timezone = $request->input('timezone') ?: 'UTC';
        $timezoneOffset = (new Carbon())->setTimezone($timezone)->format('P');

        $startAt = Carbon::parse($request->input('start_at'), $timezone)
            ->tz('UTC')
            ->toDateTimeString();

        $endAt = Carbon::parse($request->input('end_at'), $timezone)
            ->tz('UTC')
            ->toDateTimeString();

        $intervals = TimeInterval::select(
            ['user_id',
                DB::raw("DATE(CONVERT_TZ(start_at, '+00:00', '{$timezoneOffset}')) as date"),
                DB::raw('SUM(TIMESTAMPDIFF(SECOND, start_at, end_at)) as duration')]
        )
            ->whereIn('user_id', $uids)
            ->where('start_at', '>=', $startAt)
            ->where('start_at', '<', $endAt)
            ->whereNull('deleted_at')
            ->orderBy('start_at')
            ->groupBy('date')
            ->get()
            ->groupBy('user_id')
            ->map(static function ($intervals) {
                $totalDuration = 0;

                foreach ($intervals as $interval) {
                    $interval->duration = (int)$interval->duration;
                    $totalDuration += $interval->duration;
                }

                return [
                    'intervals' => $intervals,
                    'duration' => $totalDuration,
                ];
            });

        $result = [
            'user_intervals' => $intervals,
        ];

        return new JsonResponse($result);
    }

    /**
     * @api             {post} /time-intervals/day-duration Day Duration
     * @apiDescription  Get info for dashboard summed by days
     *
     * @apiVersion      1.0.0
     * @apiName         Day Duration
     * @apiGroup        Time Interval
     *
     * @apiUse          AuthHeader
     *
     * @apiParam {Integer[]}  user_ids  List of user ids
     * @apiParam {ISO8601}    start_at  DateTime of start
     * @apiParam {ISO8601}    end_at    DateTime of end
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "user_ids": [ 1 ],
     *    "start_at": "2013-04-12 20:40:00",
     *    "end_at": "2013-04-12 20:41:00"
     *  }
     *
     * @apiSuccess {Object}    user_intervals                     Response, keys => requested user ids
     * @apiSuccess {Object[]}  user_intervals.intervals           Intervals info
     * @apiSuccess {Integer}   user_intervals.intervals.user_id   Intervals user ID
     * @apiSuccess {String}    user_intervals.intervals.date      Intervals date
     * @apiSuccess {Integer}   user_intervals.intervals.duration  Intervals duration
     * @apiSuccess {Integer}   user_intervals.duration            Total duration of intervals
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "user_intervals": {
     *      "2": {
     *        "intervals": [
     *          {
     *            "user_id": 2,
     *            "date": "2020-01-23",
     *            "duration": 298
     *          },
     *          {
     *            "user_id": 2,
     *            "date": "2020-01-24",
     *            "duration": 298
     *          }
     *        ],
     *        "duration": 596
     *      }
     *    }
     *  }
     *
     * @apiUse          400Error
     * @apiUse          UnauthorizedError
     * @apiUse          ForbiddenError
     * @apiUse          ValidationError
     */

    public function getValidationRules(): array
    {
        return [
            'user_ids' => 'exists:users,id|array',
            'project_ids' => 'nullable|exists:projects,id|array',
            'start_at' => 'date|required',
            'end_at' => 'date|required',
        ];
    }

    /**
     * @api             {post} /time-intervals/dashboard Dashboard
     * @apiDescription  Get info for dashboard
     *
     * @apiVersion      1.0.0
     * @apiName         Dashboard
     * @apiGroup        Time Interval
     *
     * @apiUse          AuthHeader
     *
     * @apiParam {Integer[]}  user_ids  List of user ids
     * @apiParam {ISO8601}    start_at  DateTime of start
     * @apiParam {ISO8601}    end_at    DateTime of end
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "user_ids": [ 1 ],
     *    "start_at": "2013-04-12 20:40:00",
     *    "end_at": "2013-04-12 20:41:00"
     *  }
     *
     * @apiSuccess {Object}    userIntervals                       Response, keys => requested user ids
     * @apiSuccess {Integer}   userIntervals.user_id               ID of the user
     * @apiSuccess {Object[]}  userIntervals.intervals             Intervals info
     * @apiSuccess {Integer}   userIntervals.intervals.id          Interval ID
     * @apiSuccess {Integer}   userIntervals.intervals.user_id     Interval user ID
     * @apiSuccess {Integer}   userIntervals.intervals.task_id     Interval task ID
     * @apiSuccess {Integer}   userIntervals.intervals.project_id  Interval project ID
     * @apiSuccess {Integer}   userIntervals.intervals.duration    Interval duration
     * @apiSuccess {ISO8601}   userIntervals.intervals.start_at    DateTime of interval start
     * @apiSuccess {ISO8601}   userIntervals.intervals.end_at      DateTime of interval end
     * @apiSuccess {Integer}   userIntervals.duration              Total duration of intervals
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "userIntervals": {
     *      "1": {
     *        "user_id": 1,
     *        "intervals": [
     *          {
     *            "id": 3261,
     *            "user_id": 1,
     *            "task_id": 1,
     *            "project_id": 1,
     *            "duration": 60,
     *            "start_at": "2013-04-12T20:40:00+00:00",
     *            "end_at": "2013-04-12T20:41:00+00:00"
     *          }
     *        ],
     *        "duration": 60
     *      }
     *    }
     *  }
     *
     * @apiUse          400Error
     * @apiUse          UnauthorizedError
     * @apiUse          ForbiddenError
     * @apiUse          ValidationError
     */

    /**
     * Handle the incoming request.
     * @param Request $request
     * @param TimeIntervalService $timeIntervalService
     * @return JsonResponse
     */
    public function timeIntervals(Request $request, TimeIntervalService $timeIntervalService): JsonResponse
    {
        $request->validate($this->getValidationRules());

        $userIds = $request->input('user_ids');
        $projectIds = $request->input('project_ids');
        $timezone = $request->input('timezone') ?: 'UTC';
        $timezoneOffset = (new Carbon())->setTimezone($timezone)->format('P');
        $startAt = Carbon::parse($request->input('start_at'), $timezone)
            ->tz('UTC')
            ->toDateTimeString();
        $endAt = Carbon::parse($request->input('end_at'), $timezone)
            ->tz('UTC')
            ->toDateTimeString();

        $reports = $timeIntervalService->getReportForDashBoard(compact(
            'startAt',
            'endAt',
            'timezoneOffset',
            'projectIds',
            'userIds'
        ));

        return new JsonResponse(
            Filter::process(
                $this->getEventUniqueName('answer.success.report.show'),
                ['userIntervals' => $reports,]
            )
        );
    }
}
