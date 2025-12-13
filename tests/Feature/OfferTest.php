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
        $company = CompanyName::factory()->create(['name' => 'Test Company']);
        $job = Job::factory()->create([
            'title' => 'Software Engineer',
            'company_id' => $company->id
        ]);
        
        $offerStage = Stage::factory()->create(['name' => 'Offer']);

        $result = $this->service->sendOffersToCandidatesInOfferStage($job->id, $offerStage->id);

        $this->assertEquals('no_candidates', $result['status']);
        $this->assertEquals('No candidates found in the specified stage', $result['message']);
        $this->assertEmpty($result['results']);
    }

    public function test_send_offers_returns_not_offer_stage_when_stage_is_not_offer()
    {
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

        $result = $this->service->sendOffersToCandidatesInOfferStage($job->id, $appliedStage->id);

        $this->assertEquals('not_offer_stage', $result['status']);
        $this->assertEquals('The specified stage is not an "offer" stage', $result['message']);
        $this->assertEmpty($result['results']);
    }

    public function test_send_offers_filters_by_job_id_correctly()
    {
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
        
        CandidatePipelineStage::factory()->create([
            'candidate_id' => $candidate->id,
            'pipeline_stage_id' => $appliedStage->id,
            'job_id' => $job1->id,
            'moved_at' => now()
        ]);

        $result1 = $this->service->sendOffersToCandidatesInOfferStage($job1->id, $appliedStage->id);
        
        $result2 = $this->service->sendOffersToCandidatesInOfferStage($job2->id, $appliedStage->id);

  
        $this->assertEquals('not_offer_stage', $result1['status']);
        $this->assertEquals('no_candidates', $result2['status']);
    }

}

