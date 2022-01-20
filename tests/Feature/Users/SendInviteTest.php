<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class SendInviteTest extends TestCase
{
    private const URI = 'users/send-invite';

    private $admin;
    private $manager;
    private $auditor;
    private Model $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->asAdmin()->create();
        $this->manager = User::factory()->asManager()->create();
        $this->auditor = User::factory()->asAuditor()->create();
        $this->user = User::factory()->create();
    }

    public function test_send_invite_as_admin(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->user->only('id'));

        $response->assertOk();
    }

    public function test_send_invite_as_manager(): void
    {
        $response = $this->actingAs($this->manager)->postJson(self::URI, $this->user->only('id'));

        $response->assertForbidden();
    }

    public function test_send_invite_as_auditor(): void
    {
        $response = $this->actingAs($this->auditor)->postJson(self::URI, $this->user->only('id'));

        $response->assertForbidden();
    }

    public function test_send_invite_as_user(): void
    {
        $response = $this->actingAs($this->user)->postJson(self::URI, $this->user->only('id'));

        $response->assertForbidden();
    }
}
