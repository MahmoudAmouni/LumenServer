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
        
        $scorelabel = ScoreLabel::factory()->create(['name' => 'Excellent', 'max_score' => 10]);
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
        
        $interview1 = Interview::factory()->create([
            'candidate_id' => $candidate1->id,
            'interviewer_id' => $evaluator->id,
        ]);
        
        $interview2 = Interview::factory()->create([
            'candidate_id' => $candidate2->id,
            'interviewer_id' => $evaluator->id,
        ]);
        
        $scorecard1 = Scorecard::factory()->create([
            'candidate_id' => $candidate1->id,
            'job_id' => $job->id,
            'scorelabel_id' => $scorelabel->id,
            'scorerate_id' => 8,
            'interview_id' => $interview1->id,
            'status' => 'completed'
        ]);
        
        $scorecard2 = Scorecard::factory()->create([
            'candidate_id' => $candidate2->id,
            'job_id' => $job->id,
            'scorelabel_id' => $scorelabel->id,
            'scorerate_id' => 9,
            'interview_id' => $interview2->id,
            'status' => 'completed'
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
                        'scorecards' => [
                            '*' => ['scorerate_id', 'scorelabel', 'max_score']
                        ],
                        'moved_at',
                        'notes'
                    ]
                ]
            ]);
        
        $payload = $response->json('payload');
        $this->assertCount(2, $payload);
        
        // Check first candidate (should be second in order due to moved_at desc)
        $firstCandidate = $payload[0];
        $this->assertEquals($candidatePipelineStage2->id, $firstCandidate['candidate_pipeline_stage_id']);
        $this->assertEquals($candidate2->id, $firstCandidate['candidate']['id']);
        $this->assertEquals('Jane Smith', $firstCandidate['candidate']['name']);
        $this->assertEquals('jane@example.com', $firstCandidate['candidate']['email']);
        $this->assertEquals($stage->id, $firstCandidate['pipeline_stage']['id']);
        $this->assertEquals('Applied', $firstCandidate['pipeline_stage']['name']);
        $this->assertEquals('Second candidate notes', $firstCandidate['notes']);
        $this->assertCount(1, $firstCandidate['scorecards']);
        $this->assertEquals(9, $firstCandidate['scorecards'][0]['scorerate_id']);
        $this->assertEquals('Excellent', $firstCandidate['scorecards'][0]['scorelabel']);
        $this->assertEquals(10, $firstCandidate['scorecards'][0]['max_score']);
        
        // Check second candidate
        $secondCandidate = $payload[1];
        $this->assertEquals($candidatePipelineStage1->id, $secondCandidate['candidate_pipeline_stage_id']);
        $this->assertEquals($candidate1->id, $secondCandidate['candidate']['id']);
        $this->assertEquals('John Doe', $secondCandidate['candidate']['name']);
        $this->assertEquals('john@example.com', $secondCandidate['candidate']['email']);
        $this->assertEquals('First candidate notes', $secondCandidate['notes']);
        $this->assertCount(1, $secondCandidate['scorecards']);
        $this->assertEquals(8, $secondCandidate['scorecards'][0]['scorerate_id']);
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
        
        // Create candidate pipeline stage for job1
        CandidatePipelineStage::factory()->create([
            'candidate_id' => $candidate->id,
            'pipeline_stage_id' => $stage->id,
            'job_id' => $job1->id
        ]);
        
        // Create candidate pipeline stage for job2
        CandidatePipelineStage::factory()->create([
            'candidate_id' => $candidate->id,
            'pipeline_stage_id' => $stage->id,
            'job_id' => $job2->id
        ]);

        // Act
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/candidates/job/' . $job1->id . '/pipeline-stage/' . $stage->id);

        // Assert
        $response->assertJson(['status' => 'success']);
        
        $payload = $response->json('payload');
        $this->assertCount(1, $payload);
        $this->assertEquals($job1->id, $payload[0]['candidate_pipeline_stage_id']);
    }

    public function test_get_candidates_by_job_id_and_pipeline_stage_filters_by_stage_id()
    {
        // Arrange
        $company = CompanyName::factory()->create(['name' => 'Test Company']);
        $job = Job::factory()->create([
            'title' => 'Software Engineer',
            'company_id' => $company->id
        ]);
        
        $stage1 = Stage::factory()->create(['name' => 'Applied']);
        $stage2 = Stage::factory()->create(['name' => 'Interview']);
        
        $recruiter = User::factory()->create();
        
        $candidate = Candidate::factory()->create([
            'recruiter_id' => $recruiter->id,
            'full_name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
        
        // Create candidate pipeline stage for stage1
        CandidatePipelineStage::factory()->create([
            'candidate_id' => $candidate->id,
            'pipeline_stage_id' => $stage1->id,
            'job_id' => $job->id
        ]);
        
        // Create candidate pipeline stage for stage2
        CandidatePipelineStage::factory()->create([
            'candidate_id' => $candidate->id,
            'pipeline_stage_id' => $stage2->id,
            'job_id' => $job->id
        ]);

        // Act
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/candidates/job/' . $job->id . '/pipeline-stage/' . $stage1->id);

        // Assert
        $response->assertJson(['status' => 'success']);
        
        $payload = $response->json('payload');
        $this->assertCount(1, $payload);
        $this->assertEquals($stage1->id, $payload[0]['pipeline_stage']['id']);
        $this->assertEquals('Applied', $payload[0]['pipeline_stage']['name']);
    }

    public function test_get_candidates_by_job_id_and_pipeline_stage_orders_by_moved_at_desc()
    {
        // Arrange
        $company = CompanyName::factory()->create(['name' => 'Test Company']);
        $job = Job::factory()->create([
            'title' => 'Software Engineer',
            'company_id' => $company->id
        ]);
        
        $stage = Stage::factory()->create(['name' => 'Applied']);
        $recruiter = User::factory()->create();
        
        $candidate1 = Candidate::factory()->create([
            'recruiter_id' => $recruiter->id,
            'full_name' => 'First Candidate',
            'email' => 'first@example.com'
        ]);
        
        $candidate2 = Candidate::factory()->create([
            'recruiter_id' => $recruiter->id,
            'full_name' => 'Second Candidate',
            'email' => 'second@example.com'
        ]);
        
        $candidate3 = Candidate::factory()->create([
            'recruiter_id' => $recruiter->id,
            'full_name' => 'Third Candidate',
            'email' => 'third@example.com'
        ]);
        
        // Create with different moved_at dates
        CandidatePipelineStage::factory()->create([
            'candidate_id' => $candidate1->id,
            'pipeline_stage_id' => $stage->id,
            'job_id' => $job->id,
            'moved_at' => now()->subDays(3) // Oldest
        ]);
        
        CandidatePipelineStage::factory()->create([
            'candidate_id' => $candidate2->id,
            'pipeline_stage_id' => $stage->id,
            'job_id' => $job->id,
            'moved_at' => now()->subDays(1) // Most recent
        ]);
        
        CandidatePipelineStage::factory()->create([
            'candidate_id' => $candidate3->id,
            'pipeline_stage_id' => $stage->id,
            'job_id' => $job->id,
            'moved_at' => now()->subDays(2) // Middle
        ]);

        // Act
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/candidates/job/' . $job->id . '/pipeline-stage/' . $stage->id);

        // Assert
        $response->assertJson(['status' => 'success']);
        
        $payload = $response->json('payload');
        $this->assertCount(3, $payload);
        // Should be ordered by moved_at desc (most recent first)
        $this->assertEquals('Second Candidate', $payload[0]['candidate']['name']);
        $this->assertEquals('Third Candidate', $payload[1]['candidate']['name']);
        $this->assertEquals('First Candidate', $payload[2]['candidate']['name']);
    }

    public function test_get_candidates_by_job_id_and_pipeline_stage_handles_candidates_without_scorecards()
    {
        // Arrange
        $company = CompanyName::factory()->create(['name' => 'Test Company']);
        $job = Job::factory()->create([
            'title' => 'Software Engineer',
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
            'job_id' => $job->id
        ]);
        
        // Don't create any scorecards for this candidate

        // Act
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/candidates/job/' . $job->id . '/pipeline-stage/' . $stage->id);

        // Assert
        $response->assertJson(['status' => 'success']);
        
        $payload = $response->json('payload');
        $this->assertCount(1, $payload);
        $this->assertEquals($candidate->id, $payload[0]['candidate']['id']);
        $this->assertCount(0, $payload[0]['scorecards']);
    }

    public function test_get_candidates_by_job_id_and_pipeline_stage_requires_authentication()
    {
        // Arrange
        $company = CompanyName::factory()->create(['name' => 'Test Company']);
        $job = Job::factory()->create([
            'title' => 'Software Engineer',
            'company_id' => $company->id
        ]);
        
        $stage = Stage::factory()->create(['name' => 'Applied']);

        // Act
        $response = $this->getJson('/api/v1/candidates/job/' . $job->id . '/pipeline-stage/' . $stage->id);

        // Assert
        $response->assertStatus(401);
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

        // Assert
        $response->assertJson(['status' => 'success'])
            ->assertJsonStructure([
                'status',
                'payload' => [
                    'id',
                    'full_name',
                    'email',
                    'phone_number',
                    'age',
                    'location',
                    'level',
                    'github_url',
                    'linkedin_url',
                    'cv_path',
                    'recruiter' => ['id', 'name', 'email'],
                    'current_application' => [
                        'candidate_pipeline_stage_id',
                        'job' => ['id', 'title', 'description', 'location', 'employment_type', 'level', 'company'],
                        'moved_at',
                        'notes'
                    ],
                    'interviews',
                    'scorecards'
                ]
            ]);
        
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

    public function test_get_candidate_profile_with_job_id_filter()
    {
        // Arrange
        $company = CompanyName::factory()->create(['name' => 'Tech Company']);
        $recruiter = User::factory()->create();
        
        $candidate = Candidate::factory()->create([
            'recruiter_id' => $recruiter->id,
            'full_name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
        
        $job1 = Job::factory()->create([
            'title' => 'Software Engineer',
            'company_id' => $company->id
        ]);
        
        $job2 = Job::factory()->create([
            'title' => 'Product Manager',
            'company_id' => $company->id
        ]);
        
        $stage = Stage::factory()->create(['name' => 'Applied']);
        
        CandidatePipelineStage::factory()->create([
            'candidate_id' => $candidate->id,
            'pipeline_stage_id' => $stage->id,
            'job_id' => $job1->id,
            'moved_at' => now()->subDays(2)
        ]);
        
        CandidatePipelineStage::factory()->create([
            'candidate_id' => $candidate->id,
            'pipeline_stage_id' => $stage->id,
            'job_id' => $job2->id,
            'moved_at' => now()
        ]);

        // Act
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/candidates/' . $candidate->id . '/profile?job_id=' . $job1->id);

        // Assert
        $response->assertJson(['status' => 'success']);
        
        $payload = $response->json('payload');
        $this->assertEquals($job1->id, $payload['current_application']['job']['id']);
        $this->assertEquals('Software Engineer', $payload['current_application']['job']['title']);
    }

    public function test_get_candidate_profile_not_found()
    {
        // Act
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/candidates/99999/profile');

        // Assert
        $response->assertStatus(404)
            ->assertJson(['status' => 'failure']);
    }

    public function test_get_candidate_profile_with_interviews_and_scorecards()
    {
        // Arrange
        $company = CompanyName::factory()->create(['name' => 'Tech Company']);
        $recruiter = User::factory()->create();
        $evaluator = User::factory()->create(['email' => 'evaluator@example.com']);
        
        $candidate = Candidate::factory()->create([
            'recruiter_id' => $recruiter->id,
            'full_name' => 'Jane Smith',
            'email' => 'jane@example.com'
        ]);
        
        $job = Job::factory()->create([
            'title' => 'Software Engineer',
            'company_id' => $company->id
        ]);
        
        $stage = Stage::factory()->create(['name' => 'Interview']);
        $scorelabel = ScoreLabel::factory()->create(['name' => 'Excellent', 'max_score' => 10]);
        
        CandidatePipelineStage::factory()->create([
            'candidate_id' => $candidate->id,
            'pipeline_stage_id' => $stage->id,
            'job_id' => $job->id
        ]);
        
        $interview = Interview::factory()->create([
            'candidate_id' => $candidate->id,
            'interviewer_id' => $evaluator->id,
            'scheduled_at' => now()->addDays(1),
            'status' => 'scheduled',
            'duration' => 60,
            'notes' => 'Technical interview'
        ]);
        
        Scorecard::factory()->create([
            'candidate_id' => $candidate->id,
            'job_id' => $job->id,
            'scorelabel_id' => $scorelabel->id,
            'scorerate_id' => 9,
            'interview_id' => $interview->id,
            'status' => 'completed'
        ]);

        // Act
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/candidates/' . $candidate->id . '/profile?job_id=' . $job->id);

        // Assert
        $response->assertJson(['status' => 'success']);
        
        $payload = $response->json('payload');
        $this->assertCount(1, $payload['interviews']);
        $this->assertEquals('scheduled', $payload['interviews'][0]['status']);
        $this->assertEquals('Technical interview', $payload['interviews'][0]['notes']);
        $this->assertCount(1, $payload['scorecards']);
        $this->assertEquals(9, $payload['scorecards'][0]['scorerate_id']);
        $this->assertEquals('Excellent', $payload['scorecards'][0]['scorelabel']['name']);
    }

    public function test_get_candidate_profile_requires_authentication()
    {
        // Act
        $response = $this->getJson('/api/v1/candidates/1/profile');

        // Assert
        $response->assertStatus(401);
    }

    public function test_get_candidate_profile_without_current_application()
    {
        // Arrange
        $recruiter = User::factory()->create();
        
        $candidate = Candidate::factory()->create([
            'recruiter_id' => $recruiter->id,
            'full_name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        // Act
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/candidates/' . $candidate->id . '/profile');

        // Assert
        $response->assertJson(['status' => 'success']);
        
        $payload = $response->json('payload');
        $this->assertNull($payload['current_application']);
        $this->assertIsArray($payload['interviews']);
        $this->assertIsArray($payload['scorecards']);
    }
}

