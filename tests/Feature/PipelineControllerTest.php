<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Job;
use App\Models\Pipeline;
use App\Models\Stage;
use App\Models\PipelineStages;

class PipelineControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_pipeline_by_job_id_success(): void
    {
        $job = Job::create([
            'recruiter_id' => 1,
            'company_id' => 1,
            'title' => 'Backend Developer',
            'description' => 'Demo description',
            'location' => 'Remote',
            'employment_type' => 'full-time',
            'level' => 'mid',
            'status' => 'open',
        ]);

        $pipeline = Pipeline::create([
            'name' => 'Backend Developer',
            'job_id' => $job->id,
        ]);

        $stage1 = Stage::create(['name' => 'applied']);
        $stage2 = Stage::create(['name' => 'interview']);

        PipelineStages::create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage1->id,
            'order' => 1,
        ]);

        PipelineStages::create([
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage2->id,
            'order' => 2,
        ]);

        $response = $this->getJson("/api/pipelineStages/{$job->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonStructure([
                'status',
                'payload' => [
                    'id',
                    'name',
                    'job_id',
                    'pipeline_stages' => [
                        '*' => ['id', 'stage_id', 'pipeline_id', 'order']
                    ],
                ],
            ]);
    }

    public function test_get_pipeline_by_job_id_failure_not_found(): void
    {
        $response = $this->getJson("/api/pipelineStages/99999");

        $response->assertStatus(404)
            ->assertJson([
                'payload' => 'NotFound',
            ]);
    }
}
