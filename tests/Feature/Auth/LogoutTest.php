<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    private const URI = 'auth/logout';
    private const TEST_URI = 'auth/me';

    private Model $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_logout(): void
    {
        $token = cache("testing:{$this->user->id}:tokens");
        $this->assertNotEmpty($token);
        $this->assertNotEmpty($token[0]);
        $this->assertNotEmpty($token[0]['token']);

        $response = $this->actingAs($token[0]['token'])->postJson(self::URI);

        $response->assertOk();

        $response = $this->actingAs($token[0]['token'])->get(self::TEST_URI);

        $response->assertUnauthorized();
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(self::URI);

        $response->assertUnauthorized();
    }
}
