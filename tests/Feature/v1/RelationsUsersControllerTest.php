<?php

namespace Tests\Feature\v1;

use Tests\TestCase;

class RelationsUsersControllerTest extends TestCase
{
    public function test_BulkCreate_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $data = [
            "relations" => [
                [
                    "user_id"           => 1,
                    "attached_user_id"  => 1,
                ]
            ]
        ];

        $expectedFields = [
            "messages" => [
                "*" => [
                    "user_id", "attached_user_id", "updated_at", "created_at"
                ]
            ]
        ];

        $expectedJson = [
            "messages" => [
                [
                    "user_id" => 1,
                    "attached_user_id" => 1
                ]
            ]
        ];

        $response = $this->postJson("/api/v1/attached-users/bulk-create", $data, $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
        $response->assertJson($expectedJson);
    }

    public function test_BulkDestroy_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $data = [
           "relations" => [
              [
                  "user_id"           => 1,
                  "attached_user_id"  => 1
              ]
           ]
        ];

        $expectedFields = [
            "messages" => [
                "*" => [
                    "message"
                ]
            ]
        ];

        $expectedJson = [
            "messages" => [
                [
                    "message" => "Item has been removed"
                ]
            ]
        ];

        $this->postJson("/api/v1/attached-users/bulk-create", $data, $headers);

        $response = $this->postJson("/api/v1/attached-users/bulk-remove", $data, $headers);

        $response->assertJsonStructure($expectedFields);
        $response->assertJson($expectedJson);
        $response->assertStatus(200);
    }

    public function test_Create_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $data = [
            "user_id"           => 1,
            "attached_user_id"  => 1
        ];

        $expectedFields = [
            "*" => [
                "user_id", "attached_user_id", "updated_at", "created_at"
            ]
        ];

        $expectedJson = [
            [
                "user_id"          => 1,
                "attached_user_id" => 1,
            ]
        ];

        $response = $this->postJson("/api/v1/attached-users/create", $data, $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
        $response->assertJson($expectedJson);
    }

    public function test_Destroy_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $data = [
            "user_id"           => 1,
            "attached_user_id"  => 1,
        ];

        $expectedFields = [
            "message"
        ];

        $expectedJson = [
            "message" => "Item has been removed"
        ];

        $this->postJson("/api/v1/attached-users/create", $data, $headers);
        $response = $this->postJson("/api/v1/attached-users/remove", $data, $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
        $response->assertJson($expectedJson);
    }

    public function test_List_ExpectPass()
    {
        $headers = [
            "Authorization" => "Bearer " . $this->getAdminToken()
        ];

        $expectedFields = [
            "*" => [
                "user_id", "attached_user_id", "created_at", "updated_at"
            ]
        ];

        $createData = [
            "user_id"           => 1,
            "attached_user_id"  => 1
        ];

        $expectedJson = [
            [
                "user_id" => 1,
                "attached_user_id" => 1
            ]
        ];

        $this->postJson("/api/v1/attached-users/create", $createData, $headers);

        $response = $this->postJson("/api/v1/attached-users/list", [], $headers);

        $response->assertStatus(200);
        $response->assertJsonStructure($expectedFields);
        $response->assertJson($expectedJson);
    }
}