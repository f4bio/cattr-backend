<?php


namespace Tests\Feature\TimeIntervals;

use App\Models\TimeInterval;
use App\Models\User;
use Tests\TestCase;

class BulkRemoveTest extends TestCase
{
    private const URI = 'time-intervals/bulk-remove';

    private const INTERVALS_AMOUNT = 5;

    private $admin;
    private $manager;
    private $auditor;
    private $user;

    private $intervals;
    private $intervalsForManager;
    private $intervalsForAuditor;
    private $intervalsForUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->asAdmin()->create();
        $this->manager = User::factory()->asManager()->create();
        $this->auditor = User::factory()->asAuditor()->create();
        $this->user = User::factory()->create();



        $this->intervals = TimeInterval::factory()->count(self::INTERVALS_AMOUNT)->create();
        $this->intervalsForManager = TimeInterval::factory()->for($this->manager)
            ->count(self::INTERVALS_AMOUNT)->create();
        $this->intervalsForAuditor = TimeInterval::factory()->for($this->auditor)
            ->count(self::INTERVALS_AMOUNT)->create();
        $this->intervalsForUser = TimeInterval::factory()->for($this->user)
            ->count(self::INTERVALS_AMOUNT)->create();
    }

    public function test_bulk_remove_as_admin(): void
    {
        foreach ($this->intervals as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }
        $requestData = ['intervals' => $this->intervals->pluck('id')->toArray()];

        $response = $this->actingAs($this->admin)->postJson(self::URI, $requestData);

        $response->assertOk();

        foreach ($this->intervals as $interval) {
            $this->assertSoftDeleted('time_intervals', $interval->toArray());
        }
    }

    public function test_bulk_remove_as_manager(): void
    {
        foreach ($this->intervals as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }

        $requestData = ['intervals' => $this->intervals->pluck('id')->toArray()];

        $response = $this->actingAs($this->manager)->postJson(self::URI, $requestData);

        $response->assertForbidden();
    }

    public function test_bulk_remove_your_own_as_manager(): void
    {
        foreach ($this->intervalsForManager as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }

        $requestData = ['intervals' => $this->intervalsForManager->pluck('id')->toArray()];

        $response = $this->actingAs($this->manager)->postJson(self::URI, $requestData);

        $response->assertOk();

        foreach ($this->intervalsForManager as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }
    }

    public function test_bulk_remove_as_user(): void
    {
        foreach ($this->intervals as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }

        $requestData = ['intervals' => $this->intervals->pluck('id')->toArray()];

        $response = $this->actingAs($this->user)->postJson(self::URI, $requestData);

        $response->assertForbidden();
    }

    public function test_bulk_remove_your_own_as_user(): void
    {
        foreach ($this->intervalsForUser as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }

        $requestData = ['intervals' => $this->intervalsForUser->pluck('id')->toArray()];

        $response = $this->actingAs($this->user)->postJson(self::URI, $requestData);

        $response->assertOk();

        foreach ($this->intervalsForUser as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }
    }

    public function test_bulk_remove_your_own_as_auditor(): void
    {
        foreach ($this->intervalsForAuditor as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }

        $requestData = ['intervals' => $this->intervalsForAuditor->pluck('id')->toArray()];

        $response = $this->actingAs($this->auditor)->postJson(self::URI, $requestData);

        $response->assertOk();

        foreach ($this->intervalsForAuditor as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
        }
    }

    public function test_with_not_existing_intervals(): void
    {
        $nonIntervals = [TimeInterval::max('id') + 1, TimeInterval::max('id') + 2];

        $requestData = ['intervals' => array_merge($this->intervals->pluck('id')->toArray(), $nonIntervals)];

        foreach ($this->intervals as $interval) {
            $this->assertDatabaseHas('time_intervals', $interval->toArray());
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
