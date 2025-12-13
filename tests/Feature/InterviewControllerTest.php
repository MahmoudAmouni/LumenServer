<?php

namespace Tests\Feature;

use App\Models\Interview;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;
use App\Models\Candidate;
use App\Models\User;
use App\Models\UserType;
use App\Models\CompanyName;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InterviewControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;
    protected $userType;
    protected $company;
    protected $candidate;
    protected $interviewer;
    protected $interview;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userType = UserType::factory()->create();
        $this->company = CompanyName::factory()->create();

        $this->user = User::factory()->create([
            'email' => 'testuser@example.com',
            'type_id' => $this->userType->id,
            'company_id' => $this->company->id,
        ]);

        $this->interviewer = User::factory()->create([
            'email' => 'interviewer@example.com',
            'type_id' => $this->userType->id,
            'company_id' => $this->company->id,
        ]);
        $recruiter = User::factory()->create([
            'email' => 'recruiter@example.com',
            'type_id' => $this->userType->id,
            'company_id' => $this->company->id,
        ]);

        $this->candidate = Candidate::create([
            'user_id' => '1',
            'full_name' => 'John Doe',
            'email' => 'candidate@example.com',
            'level' => 'senior',
            'recruiter_id' => $recruiter->id,
        ]);

        $this->interview = Interview::create([
            'candidate_id' => $this->candidate->id,
            'interviewer_id' => $this->interviewer->id,
            'interview_type_id' => 1,
            'scheduled_at' => now()->addDays(1),
            'status' => 'scheduled',
        ]);

        $this->token = JWTAuth::fromUser($this->user);
    }

    protected function authHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ];
    }

    public function test_update_interview_success()
    {
        $payload = [
            'status' => 'completed',
            'duration' => 60,
        ];

        $response = $this->postJson(
            '/api/v1/update/' . $this->interview->id,
            $payload,
            $this->authHeaders()
        );

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ])
            ->assertJsonStructure([
                'status',
                'payload' => [
                    'id',
                    'candidate_id',
                    'interviewer_id',
                    'status',
                    'duration',
                ]
            ]);

        $this->assertDatabaseHas('interviews', [
            'id' => $this->interview->id,
            'status' => 'completed',
            'duration' => 60,
        ]);
    }

    public function test_update_interview_not_found()
    {
        $payload = [
            'status' => 'completed',
        ];

        $response = $this->postJson(
            '/api/v1/update/99999',
            $payload,
            $this->authHeaders()
        );

        $response->assertStatus(404);
    }
}

