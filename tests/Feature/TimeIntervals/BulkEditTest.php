<?php


namespace Tests\Feature\TimeIntervals;

use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Tests\TestCase;

class BulkEditTest extends TestCase
{
    use WithFaker;

    private const URI = 'time-intervals/bulk-edit';

    private const INTERVALS_AMOUNT = 5;

    private User $admin;
    private User $manager;
    private User $auditor;
    private $user;

    private Collection $intervals;
    private Collection $intervalsForManager;
    private Collection $intervalsForAuditor;
    private Collection $intervalsForUser;

    private $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->asAdmin()->create();
        $this->manager = User::factory()->asManager()->create();
        $this->auditor = User::factory()->asAuditor()->create();
        $this->user = User::factory()->create();

        $this->task = Task::factory()->withProject()->create();


        $this->intervalsForManager = TimeInterval::factory()->for($this->manager)
            ->count(self::INTERVALS_AMOUNT)->create();
        $this->intervalsForAuditor = TimeInterval::factory()->for($this->auditor)
            ->count(self::INTERVALS_AMOUNT)->create();
        $this->intervalsForUser = TimeInterval::factory()->for($this->user)
            ->count(self::INTERVALS_AMOUNT)->create();
        $this->intervals = TimeInterval::factory()->count(self::INTERVALS_AMOUNT)->create();
    }

    public function test_bulk_edit_as_admin(): void
    {
        $this->intervals->each->setAttribute('task_id', $this->task->id);

        foreach ($this->intervals as $interval) {
            $this->assertDatabaseMissing('time_intervals', $interval->toArray());
        }

        $requestData = ['intervals' => $this->intervals->toArray()];

        $response = $this->actingAs($this->admin)->postJson(self::URI, $requestData);

        $response->assertOk();

        foreach ($this->intervals as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }
    }

    public function test_bulk_edit_as_manager(): void
    {
        $this->intervals->each->setAttribute('task_id', $this->task->id);

        foreach ($this->intervals as $interval) {
            $this->assertDatabaseMissing('time_intervals', $interval->toArray());
        }

        $requestData = ['intervals' => $this->intervals->toArray()];

        $response = $this->actingAs($this->manager)->postJson(self::URI, $requestData);

        $response->assertForbidden();
    }

    public function test_bulk_edit_your_own_as_manager(): void
    {
        $this->intervals->each->setAttribute('task_id', $this->task->id);

        foreach ($this->intervalsForManager as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }

        $requestData = ['intervals' => $this->intervalsForManager->toArray()];

        $response = $this->actingAs($this->manager)->postJson(self::URI, $requestData);

        $response->assertOk();

        foreach ($this->intervalsForManager as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }
    }

    public function test_bulk_edit_as_auditor(): void
    {
        $this->intervals->each->setAttribute('task_id', $this->task->id);

        foreach ($this->intervals as $interval) {
            $this->assertDatabaseMissing('time_intervals', $interval->toArray());
        }

        $requestData = ['intervals' => $this->intervals->toArray()];

        $response = $this->actingAs($this->auditor)->postJson(self::URI, $requestData);

        $response->assertForbidden();
    }

    public function test_bulk_edit_your_own_as_auditor(): void
    {
        $this->intervals->each->setAttribute('task_id', $this->task->id);

        foreach ($this->intervalsForAuditor as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }

        $requestData = ['intervals' => $this->intervalsForAuditor->toArray()];

        $response = $this->actingAs($this->auditor)->postJson(self::URI, $requestData);

        $response->assertOk();

        foreach ($this->intervalsForAuditor as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }
    }

    public function test_bulk_edit_as_user(): void
    {
        $this->intervals->each->setAttribute('task_id', $this->task->id);

        foreach ($this->intervals as $interval) {
            $this->assertDatabaseMissing('time_intervals', $interval->toArray());
        }

        $requestData = ['intervals' => $this->intervals->toArray()];

        $response = $this->actingAs($this->user)->postJson(self::URI, $requestData);

        $response->assertForbidden();
    }

    public function test_bulk_edit_your_own_as_user(): void
    {
        $this->intervals->each->setAttribute('task_id', $this->task->id);

        foreach ($this->intervalsForUser as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }

        $requestData = ['intervals' => $this->intervalsForUser->toArray()];

        $response = $this->actingAs($this->user)->postJson(self::URI, $requestData);

        $response->assertOk();

        foreach ($this->intervalsForUser as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }
    }

    public function test_with_not_existing_intervals(): void
    {
        $this->intervals->each->setAttribute('task_id', $this->task->id);

        $nonIntervals = [
            ['id' => TimeInterval::max('id') + 1, 'task_id' => $this->task->id],
            ['id' => TimeInterval::max('id') + 2, 'task_id' => $this->task->id]
        ];

        $requestData = ['intervals' => array_merge($this->intervals->toArray(), $nonIntervals)];

        foreach ($this->intervals as $interval) {
            $this->assertDatabaseMissing('time_intervals', $interval->toArray());
        }

        $response = $this->actingAs($this->admin)->postJson(self::URI, $requestData);

        $response->assertValidationError();
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(self::URI);

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI);

        $response->assertValidationError();
    }
}
