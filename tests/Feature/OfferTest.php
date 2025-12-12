<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Offer;
use App\Models\Candidate;
use App\Models\Job;
use App\Models\CompanyName;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class OfferTest extends TestCase
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

    public function test_get_offer_by_id_not_found_failure()
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/v1/offers/99999');

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'failure'
            ]);
    }

    public function test_create_offer_success()
    {
        $company = CompanyName::factory()->create(['name' => 'Test Company']);
        $job = Job::factory()->create([
            'title' => 'Software Engineer',
            'description' => 'Test job description',
            'company_id' => $company->id
        ]);
        
        $recruiter = User::factory()->create(['email' => 'recruiter@example.com']);
        $candidate = Candidate::factory()->create([
            'recruiter_id' => (string) $recruiter->id,
            'full_name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        $requestData = [
            'candidate_id' => $candidate->id,
            'job_id' => $job->id,
            'salary' => 55000.00,
            'start_date' => '2025-02-01',
            'contract_type' => 'Full-time',
            'offer_letter_template' => 'Welcome to our team!',
            'status' => 'draft',
            'recruiter_id' => $recruiter->id
        ];

        $response = $this->withHeaders($this->getAuthHeaders())
            ->postJson('/api/v1/offers/add', $requestData);

        $response->assertStatus(200)
            ->assertJson(['status' => 'success'])
            ->assertJson([
                'payload' => [
                    'candidate_id' => $candidate->id,
                    'job_id' => $job->id,
                    'salary' => '55000.00',
                    'status' => 'draft',
                    'contract_type' => 'Full-time'
                ]
            ]);

        $this->assertDatabaseHas('offers', [
            'candidate_id' => $candidate->id,
            'job_id' => $job->id,
            'salary' => 55000.00,
            'status' => 'draft'
        ]);
    }

}

