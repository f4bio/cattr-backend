<?php

namespace Tests\Feature\TimeUseReport;

use App\Models\Project;
use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ListTest extends TestCase
{
    //error model screenshot from ProjectHelper
    private const URI = 'time-use-report/list';

    private const INTERVALS_AMOUNT = 10;

    private User $admin;
    private int $duration = 0;
    private array $userIds;
    private array $requestData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->asAdmin()->create();

        $intervals = TimeInterval::factory()->for($this->admin)->for(Task::factory()->for(Project::factory()))->count(self::INTERVALS_AMOUNT)->create();

        $intervals->each(function (TimeInterval $interval) {
            $this->userIds[] = $interval->user_id;
            $this->duration += Carbon::parse($interval->end_at)->diffInSeconds($interval->start_at);
        });

        $this->requestData = [
            'start_at' => $intervals->min('start_at'),
            'end_at' => $intervals->max('end_at')->addMinute(),
            'user_ids' => $this->userIds
        ];
        $this->withoutExceptionHandling();
    }

    public function test_list(): void
    {

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->requestData);

        $response->assertOk();
        $totalTime = collect($response->json())->pluck('total_time')->sum();

        $this->assertEquals($this->duration, $totalTime);
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $response->assertValidationError();
    }
}
