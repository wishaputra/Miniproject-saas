<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_company_and_get_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'company_name'          => 'Acme Corp',
            'name'                  => 'Admin User',
            'email'                 => 'admin@acme.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'user' => ['id', 'name', 'email', 'role', 'company_id'],
                         'company' => ['id', 'name'],
                         'token',
                     ],
                     'message',
                 ])
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.user.role', 'admin')
                 ->assertJsonPath('data.company.name', 'Acme Corp');
    }

    public function test_user_can_login_and_receive_token(): void
    {
        // Register dulu
        $this->postJson('/api/v1/auth/register', [
            'company_name'          => 'Acme Corp',
            'name'                  => 'Admin User',
            'email'                 => 'admin@acme.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Then login
        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'admin@acme.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('success', true)
                 ->assertJsonStructure(['data' => ['token']]);
    }

    public function test_login_fails_with_wrong_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
                 ->assertJsonPath('success', false);
    }

    public function test_user_can_logout(): void
    {
        $registerResponse = $this->postJson('/api/v1/auth/register', [
            'company_name'          => 'Acme Corp',
            'name'                  => 'Admin User',
            'email'                 => 'admin@acme.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $token = $registerResponse->json('data.token');

        $response = $this->withToken($token)->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);
    }
}
