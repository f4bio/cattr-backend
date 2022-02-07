<?php


namespace Tests\Feature\TimeIntervals;

use App\Models\TimeInterval;
use App\Models\User;
use Tests\TestCase;

class CountTest extends TestCase
{
    private const URI = 'time-intervals/count';

    private const SCREENSHOTS_AMOUNT = 10;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->asAdmin()->create();

        TimeInterval::factory()->count(self::SCREENSHOTS_AMOUNT)->create();
    }

    public function test_count(): void
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $response->assertOk();
        $response->assertJson(['total' => TimeInterval::count()]);
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }
}
