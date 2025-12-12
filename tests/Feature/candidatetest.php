<?php

namespace Tests\Feature;

use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;
use App\Models\Pipeline;
use App\Models\CandidatePipelineStage;
use App\Models\Candidate;
use App\Models\Job;
use App\Models\CompanyName;
use App\Models\Scorecard;
use App\Models\scorelabel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CandidateTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'email' => 'test@example.com' 
        ]);

        $this->token = JWTAuth::fromUser($this->user);
    }

    protected function getAuthHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ];
    }

    public function test_get_candidates_by_job_id_and_pipeline_stage_success()
    {
        $company = CompanyName::factory()->create(['name' => 'Test Company']);
        $job = Job::factory()->create([
            'title' => 'Software Engineer',
            'description' => 'Test job description',
            'company_id' => $company->id
        ]);
        $pipeline = Pipeline::factory()->create(['job_id' => $job->id, 'name' => 'Test Pipeline']);
        $stage = Stage::factory()->create([
            'name' => 'Applied',
            'order' => 1
        ]);
        
        $user = User::factory()->create(['email' => 'candidate@example.com']);
        
        $candidate = Candidate::factory()->create([
            'recruiter_id' => $user->id,
            'full_name' => 'John Doe',
            'email' => 'john@example.com',
            'level' => 'Senior'
        ]);
        
        $scorelabel = scorelabel::factory()->create(['label' => 'Excellent', 'max_score' => 10]);
        $evaluator = User::factory()->create(['email' => 'evaluator@example.com']);
        
        CandidateStage::factory()->create([
            'candidate_id' => $candidate->id,
            'pipeline_stage_id' => $stage->id,
            'job_id' => $job->id
        ]);
        
        Scorecard::factory()->create([
            'candidate_id' => $candidate->id,
            'job_id' => $job->id,
            'scorelabel_id' => $scorelabel->id,
            'evaluator_id' => $evaluator->id,
            'scorerate_id' => 1,
            'interview_id' => 1,
            'status' => 'completed'
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/candidates/job/' . $job->id . '/pipeline-stage/' . $stage->id);

        $response->assertStatus(200)
            ->assertJson(['status' => 'success'])
            ->assertJsonStructure([
                'status',
                'payload' => [
                    '*' => [
                        'candidate_pipeline_stage_id',
                        'candidate' => ['id', 'name', 'email'],
                        'pipeline_stage' => ['id', 'name', 'order'],
                        'scorecards' => [
                            '*' => ['scorerate_id', 'scorelabel', 'max_score']
                        ]
                    ]
                ]
            ]);
        
        $payload = $response->json('payload');
        $this->assertCount(1, $payload);
        $this->assertEquals('Excellent', $payload[0]['scorecards'][0]['scorelabel']);
    }

    public function test_get_candidates_by_job_id_and_pipeline_stage_invalid_job_id_failure()
    {
        $company = CompanyName::factory()->create(['name' => 'Test Company']);
        $job = Job::factory()->create([
            'title' => 'Test Job',
            'description' => 'Test description',
            'company_id' => $company->id
        ]);
        $pipeline = Pipeline::factory()->create(['job_id' => $job->id, 'name' => 'Test Pipeline']);
        $stage = Stage::factory()->create([
            'name' => 'Applied',
            'order' => 1
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/candidates/job/99999/pipeline-stage/' . $stage->id);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'payload' => []
            ]);
    }

    public function test_update_candidate_pipeline_stage_success()
    {
        $company = CompanyName::factory()->create(['name' => 'Test Company']);
        $job = Job::factory()->create([
            'title' => 'Software Engineer',
            'description' => 'Test job description',
            'company_id' => $company->id
        ]);
        $pipeline = Pipeline::factory()->create(['job_id' => $job->id, 'name' => 'Test Pipeline']);
        $stage = Stage::factory()->create([
            'name' => 'Applied',
            'order' => 1
        ]);

        $user = User::factory()->create(['email' => 'candidate@example.com']);

        $candidate = Candidate::factory()->create([
            'recruiter_id' => $user->id,
            'full_name' => 'Update Test Candidate',
            'email' => 'update@example.com',
            'level' => 'Senior'
        ]);

        $candidatePipelineStage = CandidateStage::factory()->create([
            'candidate_id' => $candidate->id,
            'pipeline_stage_id' => $stage->id,
            'job_id' => $job->id,
            'notes' => 'Original notes'
        ]);

        $requestData = [
            'notes' => 'Updated notes after review'
        ];

        $response = $this->withHeaders($this->getAuthHeaders())
            ->putJson('/api/v1/candidate-pipeline-stages/' . $candidatePipelineStage->id, $requestData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'payload' => [
                    'id' => $candidatePipelineStage->id,
                    'notes' => 'Updated notes after review'
                ]
            ]);

        $this->assertDatabaseHas('candidate_pipeline_stages', [
            'id' => $candidatePipelineStage->id,
            'notes' => 'Updated notes after review'
        ]);
    }


    public function test_update_candidate_pipeline_stage_not_found_failure()
    {
        $requestData = ['notes' => 'Updated notes'];

        $response = $this->withHeaders($this->getAuthHeaders())
            ->putJson('/api/v1/candidate-pipeline-stages/99999', $requestData);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'failure'
            ]);
    }


    public function test_delete_candidate_pipeline_stage_success()
    {
        $company = CompanyName::factory()->create(['name' => 'Test Company']);
        $job = Job::factory()->create([
            'title' => 'Software Engineer',
            'description' => 'Test job description',
            'company_id' => $company->id
        ]);
        $pipeline = Pipeline::factory()->create(['job_id' => $job->id, 'name' => 'Test Pipeline']);
        $stage = Stage::factory()->create([
            'name' => 'Applied',
            'order' => 1
        ]);
        
        $user = User::factory()->create(['email' => 'candidate@example.com']);
        
        $candidate = Candidate::factory()->create([
            'recruiter_id' => $user->id,
            'full_name' => 'Bob Johnson',
            'email' => 'bob@example.com',
            'level' => 'Junior'
        ]);
        
        $candidatePipelineStage = CandidateStage::factory()->create([
            'candidate_id' => $candidate->id,
            'pipeline_stage_id' => $stage->id,
            'job_id' => $job->id
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->deleteJson('/api/v1/candidate-pipeline-stages/' . $candidatePipelineStage->id);

        $response->assertStatus(204);
        
        $this->assertDatabaseMissing('candidate_pipeline_stages', [
            'id' => $candidatePipelineStage->id
        ]);
    }

    public function test_delete_candidate_pipeline_stage_not_found_failure()
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/candidate-pipeline-stages/99999/delete');

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'failure'
            ]);
    }

}

