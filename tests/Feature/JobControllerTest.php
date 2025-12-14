<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\CompanyName;
use App\Models\Job;

class JobControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function authHeaders(): array
    {
        return ['Accept' => 'application/json'];
    }

    public function test_get_jobs_by_company_id_success(): void
    {
        $user = User::factory()->create(['type_id' => 2]);
        $company = CompanyName::factory()->create();
        Job::factory()->count(3)->create([
            'company_id' => $company->id,
            'recruiter_id' => $user->id,
        ]);

        $response = $this->getJson("/api/companyJobs/{$company->id}", $this->authHeaders());

        $response->assertStatus(200)
            ->assertJson(['status' => 'success'])
            ->assertJsonStructure([
                'status',
                'payload' => [
                    'data' => [
                        '*' => ['id', 'title', 'description', 'company_id', 'recruiter_id']
                    ]
                ]
            ]);

        $this->assertCount(3, $response->json('payload.data'));
    }

    public function test_get_jobs_by_company_id_not_found(): void
    {
        $response = $this->getJson("/api/companyJobs/99999", $this->authHeaders());

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'failure',
                'payload' => 'Company or jobs not found'
            ]);
    }

    public function test_create_job_success(): void
    {
        $user = User::factory()->create(['type_id' => 2]);
        $company = CompanyName::factory()->create();

        $payload = [
            'recruiter_id' => $user->id,
            'company_id' => $company->id,
            'jobTitle' => 'Frontend Engineer',
            'jobDescription' => 'Build React apps',
            'jobLocation' => 'Beirut',
            'employmentType' => 'Full-time',
            'jobLevel' => 'Mid',
            'status' => 'open',
            'skills' => [
                ['name' => 'React', 'type' => 1],
            ],
            'pipeline' => [
                ['name' => 'Tech Screen'],
            ],
            'criteria' => [
                ['name' => 'UI Implementation'],
            ],
        ];

        $response = $this->postJson('/api/addJob', $payload, $this->authHeaders());

        $response->assertStatus(201)
            ->assertJson(['status' => 'success'])
            ->assertJsonStructure([
                'status',
                'payload' => [
                    'id',
                    'title',
                    'company_id',
                    'recruiter_id',
                ]
            ]);

        $this->assertDatabaseHas('jobs', ['title' => 'Frontend Engineer']);
        $this->assertDatabaseHas('skills', ['name' => 'React']);
        $this->assertDatabaseHas('score_labels', ['name' => 'UI Implementation']);
        $this->assertDatabaseHas('stages', ['name' => 'applied']);
        $this->assertDatabaseHas('stages', ['name' => 'interview']);
    }

    public function test_create_job_validation_fails_missing_required_fields(): void
    {
        $response = $this->postJson('/api/addJob', [], $this->authHeaders());

        $response->assertStatus(422)
            ->assertJson(['status' => 'Validation failed']);
    }

    public function test_update_job_success(): void
    {
        $user = User::factory()->create(['type_id' => 2]);
        $company = CompanyName::factory()->create();
        $job = Job::factory()->create([
            'company_id' => $company->id,
            'recruiter_id' => $user->id,
            'title' => 'Old Title',
            'status' => 'open',
        ]);

        $payload = ['status' => 'paused'];

        $response = $this->postJson("/api/updateJob/{$job->id}", $payload, $this->authHeaders());

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('jobs', [
            'id' => $job->id,
            'status' => 'paused',
        ]);
    }

    public function test_update_job_not_found(): void
    {
        $payload = ['status' => 'paused'];

        $response = $this->postJson('/api/updateJob/99999', $payload, $this->authHeaders());

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'failure',
                'payload' => 'Job not found'
            ]);
    }

    public function test_delete_job_success(): void
    {
        $user = User::factory()->create(['type_id' => 2]);
        $company = CompanyName::factory()->create();
        $job = Job::factory()->create([
            'company_id' => $company->id,
            'recruiter_id' => $user->id,
        ]);

        $response = $this->postJson("/api/deleteJob/{$job->id}", [], $this->authHeaders());

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'payload' => ['message' => 'Job deleted successfully']
            ]);

        $this->assertDatabaseMissing('jobs', ['id' => $job->id]);
    }

    public function test_delete_job_not_found(): void
    {
        $response = $this->postJson('/api/deleteJob/99999', [], $this->authHeaders());

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'failure',
                'payload' => 'Job not found'
            ]);
    }
}