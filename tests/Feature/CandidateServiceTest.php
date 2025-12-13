<?php

namespace Tests\Feature;

use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;
use App\Services\CandidateService;
use App\Models\CandidatePipelineStage;
use App\Models\Candidate;
use App\Models\Job;
use App\Models\CompanyName;
use App\Models\Scorecard;
use App\Models\ScoreLabel;
use App\Models\Stage;
use App\Models\Interview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CandidateServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;
    protected CandidateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'email' => 'test@example.com' 
        ]);
        $this->token = JWTAuth::fromUser($this->user);
        $this->service = new CandidateService();
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
        // Arrange
        $company = CompanyName::factory()->create(['name' => 'Test Company']);
        $job = Job::factory()->create([
            'title' => 'Software Engineer',
            'description' => 'Test job description',
            'company_id' => $company->id
        ]);
        
        $stage = Stage::factory()->create([
            'name' => 'Applied'
        ]);
        
        $recruiter = User::factory()->create(['email' => 'recruiter@example.com']);
        
        $candidate1 = Candidate::factory()->create([
            'recruiter_id' => $recruiter->id,
            'full_name' => 'John Doe',
            'email' => 'john@example.com',
            'level' => 'Senior'
        ]);
        
        $candidate2 = Candidate::factory()->create([
            'recruiter_id' => $recruiter->id,
            'full_name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'level' => 'Mid'
        ]);
        
        $evaluator = User::factory()->create(['email' => 'evaluator@example.com']);
        
        $candidatePipelineStage1 = CandidatePipelineStage::factory()->create([
            'candidate_id' => $candidate1->id,
            'pipeline_stage_id' => $stage->id,
            'job_id' => $job->id,
            'moved_at' => now()->subDays(2),
            'notes' => 'First candidate notes'
        ]);
        
        $candidatePipelineStage2 = CandidatePipelineStage::factory()->create([
            'candidate_id' => $candidate2->id,
            'pipeline_stage_id' => $stage->id,
            'job_id' => $job->id,
            'moved_at' => now()->subDays(1),
            'notes' => 'Second candidate notes'
        ]);
        

        // Act
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/candidates/job/' . $job->id . '/pipeline-stage/' . $stage->id);

        // Assert
        $response->assertJson(['status' => 'success'])
            ->assertJsonStructure([
                'status',
                'payload' => [
                    '*' => [
                        'candidate_pipeline_stage_id',
                        'candidate' => ['id', 'name', 'email'],
                        'pipeline_stage' => ['id', 'name'],
                        'scorecards',
                        'moved_at',
                        'notes'
                    ]
                ]
            ]);
        
        $payload = $response->json('payload');
        $this->assertCount(2, $payload);
        
        $firstCandidate = $payload[0];
        $this->assertEquals($candidatePipelineStage2->id, $firstCandidate['candidate_pipeline_stage_id']);
        $this->assertEquals($candidate2->id, $firstCandidate['candidate']['id']);
        $this->assertEquals('Jane Smith', $firstCandidate['candidate']['name']);
        $this->assertEquals('jane@example.com', $firstCandidate['candidate']['email']);
        $this->assertEquals($stage->id, $firstCandidate['pipeline_stage']['id']);
        $this->assertEquals('Applied', $firstCandidate['pipeline_stage']['name']);
        $this->assertEquals('Second candidate notes', $firstCandidate['notes']);
        $this->assertIsArray($firstCandidate['scorecards']);
        
        $secondCandidate = $payload[1];
        $this->assertEquals($candidatePipelineStage1->id, $secondCandidate['candidate_pipeline_stage_id']);
        $this->assertEquals($candidate1->id, $secondCandidate['candidate']['id']);
        $this->assertEquals('John Doe', $secondCandidate['candidate']['name']);
        $this->assertEquals('john@example.com', $secondCandidate['candidate']['email']);
        $this->assertEquals('First candidate notes', $secondCandidate['notes']);
        $this->assertIsArray($secondCandidate['scorecards']);
    }

    public function test_get_candidates_by_job_id_and_pipeline_stage_returns_empty_when_no_candidates()
    {
        // Arrange
        $company = CompanyName::factory()->create(['name' => 'Test Company']);
        $job = Job::factory()->create([
            'title' => 'Software Engineer',
            'description' => 'Test job description',
            'company_id' => $company->id
        ]);
        
        $stage = Stage::factory()->create([
            'name' => 'Applied'
        ]);

        // Act
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/candidates/job/' . $job->id . '/pipeline-stage/' . $stage->id);

        // Assert
        $response->assertJson(['status' => 'success'])
            ->assertJson(['payload' => []]);
        
        $payload = $response->json('payload');
        $this->assertCount(0, $payload);
        $this->assertIsArray($payload);
    }

    public function test_get_candidates_by_job_id_and_pipeline_stage_filters_by_job_id()
    {
        // Arrange
        $company = CompanyName::factory()->create(['name' => 'Test Company']);
        $job1 = Job::factory()->create([
            'title' => 'Software Engineer',
            'company_id' => $company->id
        ]);
        
        $job2 = Job::factory()->create([
            'title' => 'Product Manager',
            'company_id' => $company->id
        ]);
        
        $stage = Stage::factory()->create(['name' => 'Applied']);
        $recruiter = User::factory()->create();
        
        $candidate = Candidate::factory()->create([
            'recruiter_id' => $recruiter->id,
            'full_name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
        
        CandidatePipelineStage::factory()->create([
            'candidate_id' => $candidate->id,
            'pipeline_stage_id' => $stage->id,
            'job_id' => $job1->id
        ]);
        
        CandidatePipelineStage::factory()->create([
            'candidate_id' => $candidate->id,
            'pipeline_stage_id' => $stage->id,
            'job_id' => $job2->id
        ]);

        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/candidates/job/' . $job1->id . '/pipeline-stage/' . $stage->id);

        $response->assertJson(['status' => 'success']);
        
        $payload = $response->json('payload');
        $this->assertCount(1, $payload);
        $this->assertEquals($job1->id, $payload[0]['candidate_pipeline_stage_id']);
    }

   

    public function test_get_candidate_profile_success()
    {
        // Arrange
        $company = CompanyName::factory()->create(['name' => 'Tech Company']);
        $recruiter = User::factory()->create([
            'name' => 'John Recruiter',
            'email' => 'recruiter@example.com'
        ]);
        
        $candidate = Candidate::factory()->create([
            'recruiter_id' => $recruiter->id,
            'full_name' => 'Omar Khalil',
            'email' => 'omar.khalil@example.com',
            'phone_number' => '+961 3 123 456',
            'age' => 28,
            'location' => 'Beirut, Lebanon',
            'level' => 'Senior',
            'github_url' => 'https://github.com/omarkhalil',
            'linkedin_url' => 'https://linkedin.com/in/omarkhalil',
            'cv_path' => '/storage/cvs/omar_khalil_cv.pdf'
        ]);
        
        $job = Job::factory()->create([
            'title' => 'Senior Full-Stack Engineer',
            'description' => 'We are looking for...',
            'location' => 'Beirut, Lebanon',
            'employment_type' => 'Full-time',
            'level' => 'Senior',
            'company_id' => $company->id
        ]);
        
        $stage = Stage::factory()->create(['name' => 'Applied']);
        
        $candidatePipelineStage = CandidatePipelineStage::factory()->create([
            'candidate_id' => $candidate->id,
            'pipeline_stage_id' => $stage->id,
            'job_id' => $job->id,
            'moved_at' => now(),
            'notes' => 'Strong candidate with excellent skills'
        ]);

        // Act
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/candidates/' . $candidate->id . '/profile');

       
        
        $payload = $response->json('payload');
        $this->assertEquals($candidate->id, $payload['id']);
        $this->assertEquals('Omar Khalil', $payload['full_name']);
        $this->assertEquals('omar.khalil@example.com', $payload['email']);
        $this->assertEquals(28, $payload['age']);
        $this->assertEquals('Beirut, Lebanon', $payload['location']);
        $this->assertEquals('Senior', $payload['level']);
        $this->assertNotNull($payload['recruiter']);
        $this->assertEquals('John Recruiter', $payload['recruiter']['name']);
        $this->assertNotNull($payload['current_application']);
        $this->assertEquals('Senior Full-Stack Engineer', $payload['current_application']['job']['title']);
    }


}

