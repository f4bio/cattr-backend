<?php

namespace Tests\Feature\Time;

use App\Models\TimeInterval;
use App\Models\User;
use Tests\TestCase;

class TasksTest extends TestCase
{
    private const URI = 'time/tasks';

    private const INTERVALS_AMOUNT = 10;

    private $intervals;
    private $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->asAdmin()->create();

//        $this->intervals = IntervalFactory::forUser($this->admin)->createMany(self::INTERVALS_AMOUNT);
        $this->intervals = TimeInterval::factory()->for($this->admin)->count(self::INTERVALS_AMOUNT)->make();
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

        //TODO CHECK RESPONSE CONTENT
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }

    public function test_wrong_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, ['task_id' => 'wrong']);

        $response->assertValidationError();
    }
}
