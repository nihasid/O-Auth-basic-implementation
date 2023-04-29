<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testRequiredFieldsForRegistration()
    {
        $this->json('POST', 'api/register', ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJson([
                "message" => "The given data was invalid.",
                "errors" => [
                    "name" => ["The name field is required."],
                    "email" => ["The email field is required."],
                    "password" => ["The password field is required."],
                ]
            ]);
    }

    // public function testRepeatPassword()
    // {
    //     $userData = [
    //         "name" => "John Doe",
    //         "email" => "doe@example.com",
    //         "password" => "demo12345"
    //     ];

    //     $this->json('POST', 'api/register', $userData, ['Accept' => 'application/json'])
    //         ->assertStatus(422)
    //         ->assertJson([
    //             "message" => "The given data was invalid.",
    //             "errors" => [
    //                 "password" => ["The password confirmation does not match."]
    //             ]
    //         ]);
    // }

    public function testSuccessfulRegistration()
    {
        $userData = [
            "name" => "John Doe",
            "email" => "doe@example.com",
            "password" => "demo12345"
            // "password_confirmation" => "demo12345"
        ];

        $this->json('POST', 'api/register', $userData, ['Accept' => 'application/json'])
            ->assertStatus(201)
            ->assertJsonStructure([
                "user" => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at',
                ],
                "access_token",
                "message"
            ]);
    }
}
