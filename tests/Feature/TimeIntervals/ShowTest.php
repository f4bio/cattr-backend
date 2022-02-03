<?php


namespace Tests\Feature\TimeIntervals;

use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class ShowTest extends TestCase
{
    private const URI = 'time-intervals/show';

    private $admin;
    private $manager;
    private $auditor;
    private Model $user;

    private Model $timeInterval;
    private Model $timeIntervalForUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->asAdmin()->create();
        $this->manager = User::factory()->asManager()->create();
        $this->auditor = User::factory()->asAuditor()->create();
        $this->user = User::factory()->create();

        $this->timeInterval = TimeInterval::factory()->create();
        $this->timeIntervalForUser = TimeInterval::factory()->for($this->user)->create();
    }

    public function test_show_as_admin(): void
    {
        $this->assertDatabaseHas('time_intervals', $this->timeInterval->toArray());

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->timeInterval->only('id'));
        $response->assertOk();

        $response->assertJson($this->timeInterval->toArray());
    }

    public function test_show_as_manager(): void
    {
        $this->assertDatabaseHas('time_intervals', $this->timeInterval->toArray());

        $response = $this->actingAs($this->manager)->postJson(self::URI, $this->timeInterval->only('id'));
        $response->assertOk();

        $response->assertJson($this->timeInterval->toArray());
    }

    public function test_show_as_auditor(): void
    {
        $this->assertDatabaseHas('time_intervals', $this->timeInterval->toArray());

        $response = $this->actingAs($this->auditor)->postJson(self::URI, $this->timeInterval->only('id'));
        $response->assertOk();

        $response->assertJson($this->timeInterval->toArray());
    }

    public function test_show_as_user(): void
    {
        $this->assertDatabaseHas('time_intervals', $this->timeInterval->toArray());

        $response = $this->actingAs($this->user)->postJson(self::URI, $this->timeInterval->only('id'));

        $response->assertForbidden();
    }

    public function test_show_your_own_as_user(): void
    {
        $this->assertDatabaseHas('time_intervals', $this->timeIntervalForUser->toArray());

        $response = $this
            ->actingAs($this->user)
            ->postJson(self::URI, $this->timeIntervalForUser->only('id'));

        $response->assertJson($this->timeIntervalForUser->toArray());
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
