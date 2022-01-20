<?php


namespace Tests\Feature\TimeIntervals;

use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Tests\Facades\IntervalFactory;
use Tests\TestCase;

class RemoveTest extends TestCase
{
    private const URI = 'time-intervals/remove';

    private $admin;
    private $manager;
    private $auditor;
    private Model $user;

    private TimeInterval $timeInterval;
    private TimeInterval $timeIntervalForManager;
    private TimeInterval $timeIntervalForAuditor;
    private TimeInterval $timeIntervalForUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->asAdmin()->create();
        $this->manager = User::factory()->asManager()->create();
        $this->auditor = User::factory()->asAuditor()->create();
        $this->user = User::factory()->create();


        $this->timeInterval = IntervalFactory::create();
        $this->timeIntervalForManager = IntervalFactory::forUser($this->manager)->create();
        $this->timeIntervalForAuditor = IntervalFactory::forUser($this->auditor)->create();
        $this->timeIntervalForUser = IntervalFactory::forUser($this->user)->create();
    }

    public function test_remove_as_admin(): void
    {
        $this->assertDatabaseHas('time_intervals', $this->timeInterval->toArray());

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->timeInterval->only('id'));

        $response->assertOk();
        $this->assertSoftDeleted('time_intervals', ['id' => $this->timeInterval->id]);
    }

    public function test_remove_as_manager(): void
    {
        $this->assertDatabaseHas('time_intervals', $this->timeInterval->toArray());

        $response = $this->actingAs($this->manager)->postJson(self::URI, $this->timeInterval->only('id'));

        $response->assertForbidden();
    }

    public function test_remove_your_own_as_manager(): void
    {
        $this->assertDatabaseHas('time_intervals', $this->timeIntervalForManager->toArray());

        $response = $this
            ->actingAs($this->manager)
            ->postJson(self::URI, $this->timeIntervalForManager->only('id'));

        $response->assertOk();
        $this->assertSoftDeleted('time_intervals', ['id' => $this->timeIntervalForManager->id]);
    }

    public function test_remove_as_auditor(): void
    {
        $this->assertDatabaseHas('time_intervals', $this->timeInterval->toArray());

        $response = $this->actingAs($this->auditor)->postJson(self::URI, $this->timeInterval->only('id'));

        $response->assertForbidden();
    }

    public function test_remove_your_own_as_auditor(): void
    {
        $this->assertDatabaseHas('time_intervals', $this->timeIntervalForManager->toArray());

        $response = $this
            ->actingAs($this->auditor)
            ->postJson(self::URI, $this->timeIntervalForAuditor->only('id'));

        $response->assertOk();
        $this->assertSoftDeleted('time_intervals', ['id' => $this->timeIntervalForAuditor->id]);
    }

    public function test_remove_as_user(): void
    {
        $this->assertDatabaseHas('time_intervals', $this->timeInterval->toArray());

        $response = $this->actingAs($this->user)->postJson(self::URI, $this->timeInterval->only('id'));

        $response->assertForbidden();
    }

    public function test_remove_your_own_as_user(): void
    {
        $this->assertDatabaseHas('time_intervals', $this->timeIntervalForManager->toArray());

        $response = $this
            ->actingAs($this->user)
            ->postJson(self::URI, $this->timeIntervalForUser->only('id'));

        $response->assertOk();
        $this->assertSoftDeleted('time_intervals', ['id' => $this->timeIntervalForUser->id]);
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
