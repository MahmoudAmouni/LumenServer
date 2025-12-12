<?php

namespace Tests\Feature;

use App\Models\Scorecard;
use App\Models\ScoreLabel;
use App\Models\Interview;
use App\Models\Job;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;
use App\Models\Candidate;
use App\Models\User;
use App\Models\UserType;
use App\Models\CompanyName;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ScorecardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;
    protected $userType;
    protected $company;
    protected $candidate;
    protected $interviewer;
    protected $recruiter;
    protected $interview;
    protected $job;
    protected $scoreLabel;
    protected $scorecard;

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

        $this->recruiter = User::factory()->create([
            'email' => 'recruiter@example.com',
            'type_id' => $this->userType->id,
            'company_id' => $this->company->id,
        ]);

        $this->candidate = Candidate::create([
            'recruiter_id' => $this->recruiter->id,
            'full_name' => 'John Doe',
            'email' => 'candidate@example.com',
            'level' => 'senior',
        ]);

        $this->job = Job::create([
            'recruiter_id' => $this->recruiter->id,
            'company_id' => $this->company->id,
            'title' => 'Senior Backend Developer',
            'description' => 'Looking for an experienced backend developer',
            'location' => 'Remote',
            'employment_type' => 'Full-time',
            'level' => 'Senior',
            'status' => 'open',
        ]);

        $this->interview = Interview::create([
            'candidate_id' => $this->candidate->id,
            'interviewer_id' => $this->interviewer->id,
            'scheduled_at' => now()->addDays(1),
            'status' => 'scheduled',
        ]);

        $this->scoreLabel = ScoreLabel::create([
            'name' => 'Technical Skills',
            'max_score' => 10,
        ]);

        $this->scorecard = Scorecard::create([
            'candidate_id' => $this->candidate->id,
            'interview_id' => $this->interview->id,
            'job_id' => $this->job->id,
            'scorelabel_id' => $this->scoreLabel->id,
            'scorerate_id' => 8,
            'status' => 'pending',
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

    public function test_get_all_scorecards_success()
    {
        $response = $this->getJson(
            '/api/v1/scorecards',
            $this->authHeaders()
        );

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ])
            ->assertJsonStructure([
                'status',
                'payload' => [
                    '*' => [
                        'id',
                        'candidate_id',
                        'interview_id',
                        'job_id',
                        'scorelabel_id',
                        'scorerate_id',
                        'status',
                    ]
                ]
            ]);
    }

    public function test_get_scorecard_by_id_success()
    {
        $response = $this->getJson(
            '/api/v1/scorecards/' . $this->scorecard->id,
            $this->authHeaders()
        );

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'payload' => [
                    'id' => $this->scorecard->id,
                    'candidate_id' => $this->candidate->id,
                    'interview_id' => $this->interview->id,
                ]
            ])
            ->assertJsonStructure([
                'status',
                'payload' => [
                    'id',
                    'candidate_id',
                    'interview_id',
                    'job_id',
                    'scorelabel_id',
                    'scorerate_id',
                    'status',
                    'candidate',
                    'interview',
                    'scorelabel',
                ]
            ]);
    }

    public function test_get_scorecard_by_id_not_found()
    {
        $response = $this->getJson(
            '/api/v1/scorecards/99999',
            $this->authHeaders()
        );

        $response->assertStatus(400);
    }

    public function test_create_scorecard_success()
    {
        $payload = [
            'candidate_id' => $this->candidate->id,
            'interview_id' => $this->interview->id,
            'job_id' => $this->job->id,
            'scorelabel_id' => $this->scoreLabel->id,
            'scorerate_id' => 9,
            'status' => 'completed',
        ];

        $response = $this->postJson(
            '/api/v1/scorecards/add',
            $payload,
            $this->authHeaders()
        );

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success'
            ])
            ->assertJsonStructure([
                'status',
                'payload' => [
                    'id',
                    'candidate_id',
                    'interview_id',
                    'job_id',
                    'scorelabel_id',
                    'scorerate_id',
                    'status',
                ]
            ]);

        $this->assertDatabaseHas('scorecards', [
            'candidate_id' => $this->candidate->id,
            'interview_id' => $this->interview->id,
            'job_id' => $this->job->id,
            'scorerate_id' => 9,
            'status' => 'completed',
        ]);
    }

    public function test_create_scorecard_with_new_label_success()
    {
        $payload = [
            'candidate_id' => $this->candidate->id,
            'interview_id' => $this->interview->id,
            'job_id' => $this->job->id,
            'scorelabel_name' => 'Communication Skills',
            'scorelabel_max_score' => 5,
            'scorerate_id' => 4,
            'status' => 'completed',
        ];

        $response = $this->postJson(
            '/api/v1/scorecards/add',
            $payload,
            $this->authHeaders()
        );

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success'
            ]);

        $this->assertDatabaseHas('score_labels', [
            'name' => 'Communication Skills',
            'max_score' => 5,
        ]);

        $this->assertDatabaseHas('scorecards', [
            'candidate_id' => $this->candidate->id,
            'scorerate_id' => 4,
            'status' => 'completed',
        ]);
    }

    public function test_create_scorecard_validation_failure()
    {
        $payload = [
            'candidate_id' => $this->candidate->id,
            // Missing required fields
        ];

        $response = $this->postJson(
            '/api/v1/scorecards/add',
            $payload,
            $this->authHeaders()
        );

        $response->assertStatus(422);
    }

    public function test_update_scorecard_success()
    {
        $payload = [
            'scorerate_id' => 10,
            'status' => 'completed',
        ];

        $response = $this->putJson(
            '/api/v1/scorecards/' . $this->scorecard->id,
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
                    'scorerate_id',
                    'status',
                ]
            ]);

        $this->assertDatabaseHas('scorecards', [
            'id' => $this->scorecard->id,
            'scorerate_id' => 10,
            'status' => 'completed',
        ]);
    }

    public function test_update_scorecard_not_found()
    {
        $payload = [
            'scorerate_id' => 10,
        ];

        $response = $this->putJson(
            '/api/v1/scorecards/99999',
            $payload,
            $this->authHeaders()
        );

        $response->assertStatus(400);
    }

    public function test_get_scorecards_filtered_by_candidate()
    {
        $response = $this->getJson(
            '/api/v1/scorecards?candidate_id=' . $this->candidate->id,
            $this->authHeaders()
        );

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ]);
    }

    public function test_get_scorecards_filtered_by_interview()
    {
        $response = $this->getJson(
            '/api/v1/scorecards?interview_id=' . $this->interview->id,
            $this->authHeaders()
        );

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ]);
    }

    public function test_get_scorecards_filtered_by_job()
    {
        $response = $this->getJson(
            '/api/v1/scorecards?job_id=' . $this->job->id,
            $this->authHeaders()
        );

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ]);
    }

    public function test_get_scorecards_filtered_by_status()
    {
        $response = $this->getJson(
            '/api/v1/scorecards?status=pending',
            $this->authHeaders()
        );

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ]);
    }

    public function test_get_scorecards_with_multiple_filters()
    {
        $response = $this->getJson(
            '/api/v1/scorecards?candidate_id=' . $this->candidate->id . '&job_id=' . $this->job->id . '&status=pending',
            $this->authHeaders()
        );

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ]);
    }
}