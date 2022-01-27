<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class MeTest extends TestCase
{
    private const URI = 'auth/me';

    private Model $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_me(): void
    {
        $response = $this->actingAs($this->user)->getJson(self::URI);

        $response->assertOk();
        $response->assertJson(['user' => $this->user->toArray()]);
    }

    public function test_without_auth(): void
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }
}
