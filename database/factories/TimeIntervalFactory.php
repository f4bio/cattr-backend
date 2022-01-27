<?php

namespace Database\Factories;

use App\Helpers\FakeScreenshotGenerator;
use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimeIntervalFactory extends Factory
{
    protected $model = TimeInterval::class;

    public function definition()
    {
        $randomDateTime = $this->faker->unique()->dateTimeThisYear();
        $randomDateTime = Carbon::instance($randomDateTime);


        return [
            'end_at' => $randomDateTime->toIso8601String(),
            'start_at' => $randomDateTime->subSeconds(random_int(1, 3600))->toIso8601String(),
            'activity_fill' => random_int(1, 100),
            'mouse_fill' => random_int(1, 100),
            'keyboard_fill' => random_int(1, 100),
        ];
    }

    public function withScreenshot(): TimeIntervalFactory
    {
        return $this->afterCreating(function (TimeInterval $timeInterval) {
            FakeScreenshotGenerator::runForTimeInterval($timeInterval);
        });
    }
}
