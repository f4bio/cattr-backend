<?php

namespace Tests\Feature\ProjectReport;

use App\Models\Project;
use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Tests\TestResponse;

class ListTest extends TestCase
{
    private const URI = 'project-report/list';

    private const INTERVALS_AMOUNT = 10;

    private $admin;

    private array $pids;
    private array $uids;

    private $intervals;

    private int $duration = 0;
    private array $requestData;

    private function collectResponseProjects(TestResponse $response): Collection
    {
        return collect($response->json('projects'));
    }

    private function collectResponseUsers(TestResponse $response): Collection
    {
        return $this->collectResponseProjects($response)->pluck('users')->collapse();
    }

    protected function setUp(): void
    {
        //error model screenshot from ProjectHelper
        parent::setUp();

        $this->admin = User::factory()->asAdmin()->create();

        $this->intervals = TimeInterval::factory()
            ->for($this->admin)
            ->for(Task::factory()
                ->for(Project::factory()))
            ->count(self::INTERVALS_AMOUNT)
            ->create();

        $this->intervals->each(function (TimeInterval $interval) {
            $this->uids[] = $interval->user_id;
            $this->pids[] = $interval->task->project->id;
            $this->duration += Carbon::parse($interval->end_at)->diffInSeconds($interval->start_at);
        });


        $this->requestData = [
            'start_at' => $this->intervals->min('start_at'),
            'end_at' => $this->intervals->max('end_at')->addMinute(),
            'uids' => $this->uids,
            'pids' => $this->pids
        ];
        $this->withoutExceptionHandling();
    }

    public function test_list(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->requestData);

        $response->assertOk();

        $users = $this->collectResponseUsers($response);

        $this->assertEquals($this->duration, $users->sum('tasks_time'));
        $this->assertEquals(count($this->uids), $users->count());

        //TODO change later
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
