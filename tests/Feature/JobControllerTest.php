<?php

namespace Tests\Feature;

use App\Models\Job;
use App\Models\Skill;
use App\Models\ScoreLabel;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;
use App\Models\User;
use App\Models\UserType;
use App\Models\CompanyName;
use Illuminate\Foundation\Testing\RefreshDatabase;

class JobControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;
    protected $userType;
    protected $company;
    protected $job;
    protected $recruiter;

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

        $this->recruiter = User::factory()->create([
            'email' => 'recruiter@example.com',
            'type_id' => $this->userType->id,
            'company_id' => $this->company->id,
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

        $this->token = JWTAuth::fromUser($this->user);
    }

    protected function authHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ];
    }

    public function test_get_all_jobs_success()
    {
        $response = $this->getJson(
            '/api/v1/jobs',
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
                        'recruiter_id',
                        'company_id',
                        'title',
                        'description',
                        'location',
                        'employment_type',
                        'level',
                        'status',
                    ]
                ]
            ]);
    }

    public function test_get_job_by_id_success()
    {
        $response = $this->getJson(
            '/api/v1/jobs/' . $this->job->id,
            $this->authHeaders()
        );

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'payload' => [
                    'id' => $this->job->id,
                    'title' => 'Senior Backend Developer',
                ]
            ])
            ->assertJsonStructure([
                'status',
                'payload' => [
                    'id',
                    'recruiter_id',
                    'company_id',
                    'title',
                    'description',
                    'recruiter',
                    'company',
                ]
            ]);
    }

    public function test_get_job_by_id_not_found()
    {
        $response = $this->getJson(
            '/api/v1/jobs/99999',
            $this->authHeaders()
        );

        $response->assertStatus(400);
    }

    public function test_create_job_success()
    {
        $payload = [
            'recruiter_id' => $this->recruiter->id,
            'company_id' => $this->company->id,
            'title' => 'Frontend Developer',
            'description' => 'Looking for a skilled frontend developer',
            'location' => 'New York',
            'employment_type' => 'Full-time',
            'level' => 'Mid',
            'status' => 'open',
        ];

        $response = $this->postJson(
            '/api/v1/jobs/add',
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
                    'recruiter_id',
                    'company_id',
                    'title',
                    'description',
                ]
            ]);

        $this->assertDatabaseHas('jobs', [
            'title' => 'Frontend Developer',
            'recruiter_id' => $this->recruiter->id,
            'company_id' => $this->company->id,
        ]);
    }

    public function test_create_job_with_skills_success()
    {
        $payload = [
            'recruiter_id' => $this->recruiter->id,
            'company_id' => $this->company->id,
            'title' => 'Full Stack Developer',
            'description' => 'Looking for a full stack developer',
            'location' => 'Remote',
            'employment_type' => 'Full-time',
            'level' => 'Senior',
            'status' => 'open',
            'skills' => [
                ['name' => 'PHP', 'type' => 1],
                ['name' => 'Laravel', 'type' => 1],
                ['name' => 'Vue.js', 'type' => 2],
            ],
        ];

        $response = $this->postJson(
            '/api/v1/jobs/add',
            $payload,
            $this->authHeaders()
        );

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success'
            ]);

        $this->assertDatabaseHas('jobs', [
            'title' => 'Full Stack Developer',
        ]);

        $this->assertDatabaseHas('skills', [
            'name' => 'PHP',
        ]);

        $this->assertDatabaseHas('skills', [
            'name' => 'Laravel',
        ]);

        $this->assertDatabaseHas('skills', [
            'name' => 'Vue.js',
        ]);

        $job = Job::where('title', 'Full Stack Developer')->first();
        $this->assertDatabaseHas('job_skills', [
            'job_id' => $job->id,
            'Type' => 1,
        ]);
    }

    public function test_create_job_with_pipeline_stages_success()
    {
        $payload = [
            'recruiter_id' => $this->recruiter->id,
            'company_id' => $this->company->id,
            'title' => 'DevOps Engineer',
            'description' => 'Looking for a DevOps engineer',
            'location' => 'San Francisco',
            'employment_type' => 'Full-time',
            'level' => 'Senior',
            'status' => 'open',
            'pipeline_stages' => [
                ['name' => 'Phone Screen'],
                ['name' => 'Technical Interview'],
                ['name' => 'System Design'],
            ],
        ];

        $response = $this->postJson(
            '/api/v1/jobs/add',
            $payload,
            $this->authHeaders()
        );

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success'
            ]);

        $this->assertDatabaseHas('jobs', [
            'title' => 'DevOps Engineer',
        ]);

        $job = Job::where('title', 'DevOps Engineer')->first();
        $this->assertDatabaseHas('pipelines', [
            'job_id' => $job->id,
            'name' => 'DevOps Engineer',
        ]);

        $this->assertDatabaseHas('pipeline_stages', [
            'name' => 'Phone Screen',
        ]);

        $this->assertDatabaseHas('pipeline_stages', [
            'name' => 'Technical Interview',
        ]);
    }

    public function test_create_job_with_score_labels_success()
    {
        $payload = [
            'recruiter_id' => $this->recruiter->id,
            'company_id' => $this->company->id,
            'title' => 'Data Scientist',
            'description' => 'Looking for a data scientist',
            'location' => 'Boston',
            'employment_type' => 'Full-time',
            'level' => 'Senior',
            'status' => 'open',
            'score_labels' => [
                ['name' => 'Technical Skills', 'max_score' => 10],
                ['name' => 'Communication', 'max_score' => 5],
                ['name' => 'Problem Solving', 'max_score' => 10],
            ],
        ];

        $response = $this->postJson(
            '/api/v1/jobs/add',
            $payload,
            $this->authHeaders()
        );

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success'
            ]);

        $this->assertDatabaseHas('jobs', [
            'title' => 'Data Scientist',
        ]);

        $this->assertDatabaseHas('score_labels', [
            'name' => 'Technical Skills',
            'max_score' => 10,
        ]);

        $this->assertDatabaseHas('score_labels', [
            'name' => 'Communication',
            'max_score' => 5,
        ]);

        $this->assertDatabaseHas('score_labels', [
            'name' => 'Problem Solving',
            'max_score' => 10,
        ]);
    }

    public function test_create_job_validation_failure()
    {
        $payload = [
            'recruiter_id' => $this->recruiter->id,
            // Missing required fields
        ];

        $response = $this->postJson(
            '/api/v1/jobs/add',
            $payload,
            $this->authHeaders()
        );

        $response->assertStatus(422);
    }

    public function test_update_job_success()
    {
        $payload = [
            'title' => 'Updated Job Title',
            'status' => 'closed',
        ];

        $response = $this->putJson(
            '/api/v1/jobs/' . $this->job->id,
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
                    'title',
                    'status',
                ]
            ]);

        $this->assertDatabaseHas('jobs', [
            'id' => $this->job->id,
            'title' => 'Updated Job Title',
            'status' => 'closed',
        ]);
    }

    public function test_update_job_not_found()
    {
        $payload = [
            'title' => 'Updated Job Title',
        ];

        $response = $this->putJson(
            '/api/v1/jobs/99999',
            $payload,
            $this->authHeaders()
        );

        $response->assertStatus(400);
    }

    public function test_delete_job_success()
    {
        $response = $this->postJson(
            '/api/v1/jobs/' . $this->job->id . '/delete',
            [],
            $this->authHeaders()
        );

        $response->assertStatus(204);

        $this->assertDatabaseMissing('jobs', [
            'id' => $this->job->id,
        ]);
    }

    public function test_delete_job_not_found()
    {
        $response = $this->postJson(
            '/api/v1/jobs/99999/delete',
            [],
            $this->authHeaders()
        );

        $response->assertStatus(400);
    }

    public function test_get_jobs_filtered_by_recruiter()
    {
        $response = $this->getJson(
            '/api/v1/jobs?recruiter_id=' . $this->recruiter->id,
            $this->authHeaders()
        );

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ]);
    }

    public function test_get_jobs_filtered_by_company()
    {
        $response = $this->getJson(
            '/api/v1/jobs?company_id=' . $this->company->id,
            $this->authHeaders()
        );

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ]);
    }

    public function test_get_jobs_filtered_by_status()
    {
        $response = $this->getJson(
            '/api/v1/jobs?status=open',
            $this->authHeaders()
        );

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success'
            ]);
    }
}