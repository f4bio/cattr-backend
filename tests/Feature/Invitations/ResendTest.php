<?php

namespace Tests\Feature\Invitations;

use App\Models\User;
use App\Models\Invitation;
use Tests\TestCase;

class ResendTest extends TestCase
{
    private const URI = 'invitations/resend';

    private $admin;
    private $manager;
    private $auditor;
    private $user;

    private $invitation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->manager = User::factory()->asManager()->create();
        $this->admin = User::factory()->asAdmin()->create();
        $this->auditor = User::factory()->asAuditor()->create();

        $this->invitation = Invitation::factory()->create();
    }

    public function test_resend_as_admin(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, ['id' => $this->invitation->id]);

        $response->assertOk();
        $response->assertNotEquals(
            $response->decodeResponseJson()['res']['expires_at'],
            $this->invitation->expires_at->toISOString()
        );
    }

    public function test_resend_as_manager(): void
    {
        $response = $this->actingAs($this->manager)->postJson(self::URI, ['id' => $this->invitation->id]);

        $response->assertForbidden();
    }

    public function test_resend_as_auditor(): void
    {
        $response = $this->actingAs($this->auditor)->postJson(self::URI, ['id' => $this->invitation->id]);

        $response->assertForbidden();
    }

    public function test_resend_as_user(): void
    {
        $response = $this->actingAs($this->user)->postJson(self::URI, ['id' => $this->invitation->id]);

        $response->assertForbidden();
    }
}
