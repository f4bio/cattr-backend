<?php


namespace Tests\Feature\TimeIntervals;

use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class EditTest extends TestCase
{
    private const URI = 'time-intervals/edit';

    private User $admin;
    private User $manager;
    private User $auditor;
    private Model $user;

    private Model $timeInterval;
    private Model $timeIntervalForManager;
    private Model $timeIntervalForAuditor;
    private Model $timeIntervalForUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->asAdmin()->create();
        $this->manager = User::factory()->asManager()->create();
        $this->auditor = User::factory()->asAuditor()->create();
        $this->user = User::factory()->create();

        $this->timeInterval = TimeInterval::factory()->create();
        $this->timeIntervalForManager = TimeInterval::factory()->for($this->manager)->create();
        $this->timeIntervalForAuditor = TimeInterval::factory()->for($this->auditor)->create();
        $this->timeIntervalForUser = TimeInterval::factory()->for($this->user)->create();
//        $this->withoutExceptionHandling();
    }

    public function test_edit_as_admin(): void
    {
        $this->assertDatabaseHas('time_intervals', $this->timeInterval->toArray());

        $editedInterval = clone $this->timeInterval;
        $editedInterval->user_id = User::factory()->asAdmin()->create()->id;

        $response = $this->actingAs($this->admin)->postJson(self::URI, $editedInterval->toArray());

        $response->assertOk();
        $this->assertDatabaseHas('time_intervals', $editedInterval->toArray());
    }

    public function test_edit_as_manager(): void
    {
        $this->assertDatabaseHas('time_intervals', $this->timeInterval->toArray());

        $editedInterval = clone $this->timeInterval;
        $editedInterval->user_id = User::factory()->create()->id;

        $response = $this->actingAs($this->manager)->postJson(self::URI, $editedInterval->toArray());
        $response->assertForbidden();
    }

    public function test_edit_your_own_as_manager(): void
    {
        $this->assertDatabaseHas('time_intervals', $this->timeInterval->toArray());

        $editedInterval = clone $this->timeIntervalForManager;
        $editedInterval->user_id = User::factory()->create()->id;

        $response = $this->actingAs($this->manager)->postJson(self::URI, $editedInterval->toArray());

        $response->assertOk();
        $this->assertDatabaseHas('time_intervals', $editedInterval->toArray());
    }

    public function test_edit_as_auditor(): void
    {
        $this->assertDatabaseHas('time_intervals', $this->timeInterval->toArray());

        $editedInterval = clone $this->timeInterval;
        $editedInterval->user_id = User::factory()->create()->id;

        $response = $this->actingAs($this->auditor)->postJson(self::URI, $editedInterval->toArray());

        $response->assertForbidden();
    }

    public function test_edit_your_own_as_auditor(): void
    {
        $this->assertDatabaseHas('time_intervals', $this->timeIntervalForAuditor->toArray());

        $editedInterval = clone $this->timeIntervalForAuditor;
        $editedInterval->user_id = User::factory()->create()->id;

        $response = $this->actingAs($this->auditor)->postJson(self::URI, $editedInterval->toArray());

        $response->assertOk();
        $this->assertDatabaseHas('time_intervals', $editedInterval->toArray());
    }

    public function test_edit_as_user(): void
    {
        $this->assertDatabaseHas('time_intervals', $this->timeInterval->toArray());

        $editedInterval = clone $this->timeInterval;
        $editedInterval->user_id = User::factory()->create()->id;

        $response = $this->actingAs($this->user)->postJson(self::URI, $editedInterval->toArray());

        $response->assertForbidden();
    }

    public function test_edit_your_own_as_user(): void
    {
        $this->assertDatabaseHas('time_intervals', $this->timeIntervalForManager->toArray());

        $editedInterval = clone $this->timeIntervalForUser;
        $editedInterval->user_id = User::factory()->create()->id;

        $response = $this
            ->actingAs($this->user)
            ->postJson(self::URI, $editedInterval->toArray());

        $response->assertOk();
        $this->assertDatabaseHas('time_intervals', $editedInterval->toArray());
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
