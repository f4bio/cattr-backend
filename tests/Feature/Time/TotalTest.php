<?php

namespace Tests\Feature\Time;

use App\Models\Project;
use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TotalTest extends TestCase
{
    private const URI = 'time/total';

    private const INTERVALS_AMOUNT = 10;

    private $intervals;

    private $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->asAdmin()->create();

        $this->intervals = TimeInterval::factory()
            ->for($this->admin)
            ->for(Task::factory()->for(Project::factory()))
            ->count(self::INTERVALS_AMOUNT)
            ->create();
    }

    public function test_total(): void
    {
        $requestData = [
            'start_at' => $this->intervals->min('start_at'),
            'end_at' => $this->intervals->max('end_at')->addMinute(),
            'user_id' => $this->admin->id
        ];

        $response = $this->actingAs($this->admin)->postJson(self::URI, $requestData);
        $response->assertOk();
        $totalTime = $this->intervals->sum(static function ($interval) {
            return Carbon::parse($interval->end_at)->diffInSeconds($interval->start_at);
        });

        $response->assertJson(['time' => $totalTime]);
        $response->assertJsonFragment(['start' => $this->intervals->min('start_at')]);
        $response->assertJsonFragment(['end' => $this->intervals->max('end_at')]);
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
