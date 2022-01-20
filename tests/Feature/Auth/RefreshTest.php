<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class RefreshTest extends TestCase
{
    private const URI = 'auth/refresh';
    private const TEST_URI = 'auth/me';

    private Model $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_refresh(): void
    {
        $token = cache("testing:{$this->user->id}:tokens");

        $this->assertNotEmpty($token);
        $this->assertNotEmpty($token[0]);
        $this->assertNotEmpty($token[0]['token']);

        $this->actingAs($token[0]['token'])->get(self::TEST_URI)->assertOk();

        $response = $this->actingAs($token[0]['token'])->postJson(self::URI);

        $response->assertOk();

        $this->actingAs($token[0]['token'])->get(self::TEST_URI)->assertUnauthorized();
    }
}
