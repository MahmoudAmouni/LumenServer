<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Candidate;
use App\Models\Job;
use App\Models\CompanyName;
use App\Models\User;
use App\Models\CandidatePipelineStage;
use App\Models\Stage;
use App\Models\Offer;
use App\Services\OfferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class OfferTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;
    protected OfferService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Force load Offer model - ensures autoloader has it before service uses it
        if (!class_exists(Offer::class)) {
            require_once app_path('Models/Offer.php');
        }
        
        $this->user = User::factory()->create([
            'email' => 'test@example.com' 
        ]);
        
        $this->token = JWTAuth::fromUser($this->user);
        $this->service = new OfferService();
    }

    protected function getAuthHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ];
    }

    public function test_send_offers_returns_no_candidates_when_empty()
    {
        // Arrange
        $company = CompanyName::factory()->create(['name' => 'Test Company']);
        $job = Job::factory()->create([
            'title' => 'Software Engineer',
            'company_id' => $company->id
        ]);
        
        $offerStage = Stage::factory()->create(['name' => 'Offer']);

        // Act
        $result = $this->service->sendOffersToCandidatesInOfferStage($job->id, $offerStage->id);

        // Assert
        $this->assertEquals('no_candidates', $result['status']);
        $this->assertEquals('No candidates found in the specified stage', $result['message']);
        $this->assertEmpty($result['results']);
    }

    public function test_send_offers_returns_not_offer_stage_when_stage_is_not_offer()
    {
        // Arrange
        $company = CompanyName::factory()->create(['name' => 'Test Company']);
        $job = Job::factory()->create([
            'title' => 'Software Engineer',
            'company_id' => $company->id
        ]);
        
        $recruiter = User::factory()->create(['email' => 'recruiter@example.com']);
        $candidate = Candidate::factory()->create([
            'recruiter_id' => $recruiter->id,
            'full_name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
        
        $appliedStage = Stage::factory()->create(['name' => 'Applied']);
        
        CandidatePipelineStage::factory()->create([
            'candidate_id' => $candidate->id,
            'pipeline_stage_id' => $appliedStage->id,
            'job_id' => $job->id,
            'moved_at' => now()
        ]);

        // Act
        $result = $this->service->sendOffersToCandidatesInOfferStage($job->id, $appliedStage->id);

        // Assert
        $this->assertEquals('not_offer_stage', $result['status']);
        $this->assertEquals('The specified stage is not an "offer" stage', $result['message']);
        $this->assertEmpty($result['results']);
    }

    public function test_send_offers_filters_by_job_id_correctly()
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
        
        $recruiter = User::factory()->create(['email' => 'recruiter@example.com']);
        $candidate = Candidate::factory()->create([
            'recruiter_id' => $recruiter->id,
            'full_name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
        
        $appliedStage = Stage::factory()->create(['name' => 'Applied']);
        
        // Create candidate pipeline stage for job1 only
        CandidatePipelineStage::factory()->create([
            'candidate_id' => $candidate->id,
            'pipeline_stage_id' => $appliedStage->id,
            'job_id' => $job1->id,
            'moved_at' => now()
        ]);

        // Act - Query for job1
        $result1 = $this->service->sendOffersToCandidatesInOfferStage($job1->id, $appliedStage->id);
        
        // Act - Query for job2 (should return no candidates)
        $result2 = $this->service->sendOffersToCandidatesInOfferStage($job2->id, $appliedStage->id);

        // Assert - Both should return not_offer_stage since it's not an offer stage
        // But job2 should have no candidates, so it returns no_candidates first
        $this->assertEquals('not_offer_stage', $result1['status']);
        $this->assertEquals('no_candidates', $result2['status']);
    }

}

