<?php

namespace Tests\Feature\Invitations;

use App\Models\invitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class ListTest extends TestCase
{
    private const URI = 'invitations/list';

    private $admin;
    private $manager;
    private $auditor;
    private Model $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->manager = User::factory()->asManager()->create();
        $this->admin = User::factory()->asAdmin()->create();
        $this->auditor = User::factory()->asAuditor()->create();
    }

    public function test_list_as_admin(): void
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $invitations = invitation::all()->toArray();

        $response->assertJson($invitations);
    }

    public function test_list_as_manager(): void
    {
        $response = $this->actingAs($this->manager)->getJson(self::URI);

        $response->assertForbidden();
    }

    public function test_list_as_auditor(): void
    {
        $response = $this->actingAs($this->auditor)->getJson(self::URI);

        $response->assertForbidden();
    }

    public function test_list_as_user(): void
    {
        $response = $this->actingAs($this->user)->getJson(self::URI);

        $response->assertForbidden();
    }
}
