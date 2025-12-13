<?php

namespace Tests\Feature;

use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'testuser@example.com'
        ]);

        $this->token = JWTAuth::fromUser($this->user);
    }

    public function test_register_success()
    {
        $payload = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'type_id' => 1,
            'company_id' => 1
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success'
            ])
            ->assertJsonStructure([
                'status',
                'payload' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'user_type' => [
                            'id',
                        ],
                    ],
                    'token'
                ]
            ]);
    }

    public function test_login_success()
    {
        User::factory()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('password123')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success'
            ])
            ->assertJsonStructure([
                'status',
                'payload' => [
                    'user' => ['id', 'name', 'email'],
                    'token'
                ]
            ]);
    }

    public function test_login_invalid_credentials_failure()
    {
        User::factory()->create([
            'email' => 'wrong@example.com',
            'password' => Hash::make('correctpass')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'wrong@example.com',
            'password' => 'incorrect'
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'status',
                'payload' => [
                    'email'
                ]
            ])
            ->assertJsonPath('payload.email.0', 'The provided credentials are incorrect.');
    }

    public function test_display_error()
    {
        $response = $this->getJson('/api/error');

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'failure',
                'payload' => 'Unauthorized'
            ]);
    }
}
