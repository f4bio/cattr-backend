<?php

namespace Tests\Feature\TimeIntervals;

use App\Models\TimeInterval;
use App\Models\User;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    private const URI = 'time-intervals/dashboard';

    private const INTERVALS_AMOUNT = 2;

    private $intervals;
    private $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->asAdmin()->create();

        $this->intervals = TimeInterval::factory()->count(self::INTERVALS_AMOUNT)->for($this->admin)->create();
//        $this->withoutExceptionHandling();
    }

    public function test_dashboard(): void
    {
        $requestData = [
            'start_at' => $this->intervals->min('start_at'),
            'end_at' => $this->intervals->max('start_at')->addHour(),
            'user_ids' => [$this->admin->id]
        ];

        $response = $this->actingAs($this->admin)->postJson(self::URI, $requestData);

        $response->assertOk();
        $this->assertCount(
            $this->intervals->count(),
            $response->json('userIntervals')[$this->admin->id]['intervals']
        );

        #TODO change later
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
