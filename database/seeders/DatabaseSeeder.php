<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\UserType;
use App\Models\CompanyName;
use App\Models\Skill;
use App\Models\Stage;
use App\Models\Job;
use App\Models\JobSkill;
use App\Models\Pipeline;
use App\Models\PipelineStages;
use App\Models\Candidate;
use App\Models\CandidateJob;
use App\Models\CandidatePipelineStage;
use App\Models\Interview;
use App\Models\ScoreLabel;
use App\Models\Scorecard;
use App\Models\Offer;
use App\Models\CopilotQuery;
use App\Models\Document;
use App\Models\DocumentChunk;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Disable foreign key checks temporarily
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Truncate all tables in reverse dependency order
        DocumentChunk::truncate();
        Document::truncate();
        CopilotQuery::truncate();
        Offer::truncate();
        Scorecard::truncate();
        ScoreLabel::truncate();
        Interview::truncate();
        CandidatePipelineStage::truncate();
        CandidateJob::truncate();
        Candidate::truncate();
        PipelineStages::truncate();
        Pipeline::truncate();
        JobSkill::truncate();
        Job::truncate();
        Skill::truncate();
        Stage::truncate();
        User::truncate();
        CompanyName::truncate();
        UserType::truncate();
        
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Seed User Types
        $this->seedUserTypes();
        
        // 2. Seed Companies
        $companies = $this->seedCompanies();
        
        // 3. Seed Users (Admins + Recruiters + Interviewers)
        $users = $this->seedUsers($companies);
        
        // 4. Seed Skills
        $skills = $this->seedSkills();
        
        // 5. Seed Stages
        $stages = $this->seedStages();
        
        // 6. Seed Jobs (3 jobs per company = 9 jobs total)
        $jobs = $this->seedJobs($companies, $users['recruiters']);
        
        // 7. Seed Job Skills
        $this->seedJobSkills($jobs, $skills);
        
        // 8. Seed Pipelines
        $pipelines = $this->seedPipelines($jobs);
        
        // 9. Seed Pipeline Stages
        $this->seedPipelineStages($pipelines, $stages);
        
        // 10. Seed Candidates
        $candidates = $this->seedCandidates($users['recruiters']);
        
        // 11. Seed Candidate Jobs
        $this->seedCandidateJobs($candidates, $jobs, $users['recruiters']);
        
        // 12. Seed Candidate Pipeline Stages
        $this->seedCandidatePipelineStages($candidates, $jobs, $pipelines, $stages);
        
        // 13. Seed Interviews
        $interviews = $this->seedInterviews($candidates, $users['interviewers']);
        
        // 14. Seed Score Labels
        $scoreLabels = $this->seedScoreLabels();
        
        // 15. Seed Scorecards
        $this->seedScorecards($candidates, $interviews, $jobs, $scoreLabels);
        
        // 16. Seed Offers
        $this->seedOffers($candidates, $jobs, $users['recruiters']);
        
        // 17. Seed Copilot Queries
        $this->seedCopilotQueries($candidates, $jobs, $users['recruiters']);
        
        // 18. Seed Documents
        $documents = $this->seedDocuments($candidates);
        
        // 19. Seed Document Chunks
        $this->seedDocumentChunks($documents);

        $this->command->info('Database seeded successfully!');
        $this->command->info('Created:');
        $this->command->info('- ' . UserType::count() . ' user types');
        $this->command->info('- ' . CompanyName::count() . ' companies');
        $this->command->info('- ' . User::count() . ' users');
        $this->command->info('- ' . Job::count() . ' jobs');
        $this->command->info('- ' . Candidate::count() . ' candidates');
    }

    private function seedUserTypes(): void
    {
        $userTypes = [
            ['id' => 1, 'name' => 'Admin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Recruiter', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Interviewer', 'created_at' => now(), 'updated_at' => now()],
        ];

        foreach ($userTypes as $type) {
            UserType::updateOrCreate(
                ['id' => $type['id']],
                ['name' => $type['name']]
            );
        }
        }

    private function seedCompanies(): array
    {
        $companies = [
            ['id' => 1, 'name' => 'TechCorp Solutions', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'InnovateLabs Inc.', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Digital Dynamics', 'created_at' => now(), 'updated_at' => now()],
        ];

        foreach ($companies as $company) {
            CompanyName::updateOrCreate(
                ['id' => $company['id']],
                ['name' => $company['name']]
            );
        }

        return CompanyName::all()->keyBy('id')->toArray();
    }

    private function seedUsers(array $companies): array
    {
        $users = [
            // Admins
            [
                'name' => 'Omar',
                'email' => 'omar@gmail.com',
                'password' => Hash::make('omar1234'),
                'type_id' => 1,
                'company_id' => null,
            ],
            [
                'name' => 'Mahmoud',
                'email' => 'mahmoud@gmail.com',
                'password' => Hash::make('mahmoud1234'),
                'type_id' => 1,
                'company_id' => null,
            ],
            [
                'name' => 'Mohammad',
                'email' => 'mohammad@gmail.com',
                'password' => Hash::make('mohammad1234'),
                'type_id' => 1,
                'company_id' => null,
            ],
            // Recruiters for Company 1
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah.johnson@techcorp.com',
                'password' => Hash::make('password123'),
                'type_id' => 2,
                'company_id' => 1,
            ],
            [
                'name' => 'Michael Chen',
                'email' => 'michael.chen@techcorp.com',
                'password' => Hash::make('password123'),
                'type_id' => 2,
                'company_id' => 1,
            ],
            // Recruiters for Company 2
            [
                'name' => 'Emily Rodriguez',
                'email' => 'emily.rodriguez@innovatelabs.com',
                'password' => Hash::make('password123'),
                'type_id' => 2,
                'company_id' => 2,
            ],
            [
                'name' => 'David Kim',
                'email' => 'david.kim@innovatelabs.com',
                'password' => Hash::make('password123'),
                'type_id' => 2,
                'company_id' => 2,
            ],
            // Recruiters for Company 3
            [
                'name' => 'Jessica Williams',
                'email' => 'jessica.williams@digitaldynamics.com',
                'password' => Hash::make('password123'),
                'type_id' => 2,
                'company_id' => 3,
            ],
            [
                'name' => 'Robert Taylor',
                'email' => 'robert.taylor@digitaldynamics.com',
                'password' => Hash::make('password123'),
                'type_id' => 2,
                'company_id' => 3,
            ],
            // Interviewers
            [
                'name' => 'Alex Thompson',
                'email' => 'alex.thompson@techcorp.com',
                'password' => Hash::make('password123'),
                'type_id' => 3,
                'company_id' => 1,
            ],
            [
                'name' => 'Lisa Anderson',
                'email' => 'lisa.anderson@techcorp.com',
            'password' => Hash::make('password123'),
                'type_id' => 3,
                'company_id' => 1,
            ],
            [
                'name' => 'James Martinez',
                'email' => 'james.martinez@innovatelabs.com',
                'password' => Hash::make('password123'),
                'type_id' => 3,
                'company_id' => 2,
            ],
            [
                'name' => 'Patricia Brown',
                'email' => 'patricia.brown@digitaldynamics.com',
                'password' => Hash::make('password123'),
                'type_id' => 3,
                'company_id' => 3,
            ],
        ];

        $createdUsers = [];
        $recruiters = [];
        $interviewers = [];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
            $createdUsers[] = $user;
            
            if ($userData['type_id'] == 2) {
                $recruiters[] = $user;
            } elseif ($userData['type_id'] == 3) {
                $interviewers[] = $user;
            }
        }

        return [
            'all' => $createdUsers,
            'recruiters' => $recruiters,
            'interviewers' => $interviewers,
        ];
        }

    private function seedSkills(): array
    {
        $skills = [
            ['name' => 'PHP', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Laravel', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'JavaScript', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'React', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Node.js', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Python', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Django', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'MySQL', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'PostgreSQL', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'MongoDB', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Vue.js', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Angular', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'TypeScript', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Docker', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'AWS', 'created_at' => now(), 'updated_at' => now()],
        ];

        $createdSkills = [];
        foreach ($skills as $skillData) {
            $skill = Skill::updateOrCreate(
                ['name' => $skillData['name']],
                $skillData
            );
            $createdSkills[] = $skill;
        }

        return $createdSkills;
    }

    private function seedStages(): array
    {
        $stages = [
            ['name' => 'Applied', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Screening', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Interview', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Offer', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Hired', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rejected', 'created_at' => now(), 'updated_at' => now()],
        ];

        $createdStages = [];
        foreach ($stages as $stageData) {
            $stage = Stage::updateOrCreate(
                ['name' => $stageData['name']],
                $stageData
            );
            $createdStages[] = $stage;
        }

        return $createdStages;
    }

    private function seedJobs(array $companies, array $recruiters): array
    {
        $jobs = [];
        $jobTemplates = [
            // Company 1 - TechCorp Solutions
            [
                'title' => 'Senior PHP Developer',
                'description' => 'We are looking for an experienced PHP developer with Laravel expertise to join our backend team.',
                'location' => 'Remote',
                'employment_type' => 'Full-time',
                'level' => 'Senior',
                'status' => 'open',
            ],
            [
                'title' => 'Frontend React Developer',
                'description' => 'Join our team as a React developer to build amazing user interfaces and modern web applications.',
                'location' => 'New York, NY',
                'employment_type' => 'Full-time',
                'level' => 'Mid-level',
                'status' => 'open',
            ],
            [
                'title' => 'Full Stack Developer',
                'description' => 'We need a full stack developer with Node.js and React experience to work on our platform.',
                'location' => 'San Francisco, CA',
                'employment_type' => 'Full-time',
                'level' => 'Senior',
                'status' => 'open',
            ],
            // Company 2 - InnovateLabs Inc.
            [
                'title' => 'Python Django Developer',
                'description' => 'Looking for a Python developer with Django framework experience to build scalable web applications.',
                'location' => 'Austin, TX',
                'employment_type' => 'Full-time',
                'level' => 'Mid-level',
                'status' => 'open',
            ],
            [
                'title' => 'JavaScript Developer',
                'description' => 'We are seeking a skilled JavaScript developer proficient in modern frameworks and libraries.',
                'location' => 'Seattle, WA',
                'employment_type' => 'Full-time',
                'level' => 'Junior',
                'status' => 'open',
            ],
            [
                'title' => 'DevOps Engineer',
                'description' => 'Join our DevOps team to manage infrastructure, CI/CD pipelines, and cloud deployments.',
                'location' => 'Remote',
                'employment_type' => 'Full-time',
                'level' => 'Senior',
                'status' => 'open',
            ],
            // Company 3 - Digital Dynamics
            [
                'title' => 'Vue.js Frontend Developer',
                'description' => 'We need a Vue.js developer to build responsive and interactive user interfaces.',
                'location' => 'Chicago, IL',
                'employment_type' => 'Full-time',
                'level' => 'Mid-level',
                'status' => 'open',
            ],
            [
                'title' => 'Backend API Developer',
                'description' => 'Looking for an API developer with experience in RESTful services and microservices architecture.',
                'location' => 'Boston, MA',
                'employment_type' => 'Full-time',
                'level' => 'Senior',
                'status' => 'open',
            ],
            [
                'title' => 'Mobile App Developer',
                'description' => 'We are seeking a mobile app developer with React Native or Flutter experience.',
                'location' => 'Los Angeles, CA',
                'employment_type' => 'Full-time',
                'level' => 'Mid-level',
                'status' => 'open',
            ],
        ];

        $recruiterIndex = 0;
        $companyIndex = 1;

        foreach ($jobTemplates as $index => $jobData) {
            if ($index > 0 && $index % 3 == 0) {
                $companyIndex++;
                $recruiterIndex = ($recruiterIndex + 2) % count($recruiters);
            }

                $job = Job::create([
                'recruiter_id' => $recruiters[$recruiterIndex]->id,
                'company_id' => $companyIndex,
                'title' => $jobData['title'],
                'description' => $jobData['description'],
                'location' => $jobData['location'],
                'employment_type' => $jobData['employment_type'],
                'level' => $jobData['level'],
                'status' => $jobData['status'],
                ]);

                $jobs[] = $job;
            $recruiterIndex = ($recruiterIndex + 1) % count($recruiters);
        }

        return $jobs;
    }

    private function seedJobSkills(array $jobs, array $skills): void
    {
        $skillMap = [];
        foreach ($skills as $skill) {
            $skillMap[strtolower($skill->name)] = $skill->id;
        }

        $jobSkillMappings = [
            // PHP Developer job
            0 => [['PHP', 1], ['Laravel', 1], ['MySQL', 1], ['JavaScript', 2]],
            // React Developer job
            1 => [['React', 1], ['JavaScript', 1], ['TypeScript', 2], ['Vue.js', 2]],
            // Full Stack Developer job
            2 => [['Node.js', 1], ['React', 1], ['JavaScript', 1], ['MongoDB', 2]],
            // Python Django Developer job
            3 => [['Python', 1], ['Django', 1], ['PostgreSQL', 1], ['AWS', 2]],
            // JavaScript Developer job
            4 => [['JavaScript', 1], ['React', 2], ['Node.js', 2]],
            // DevOps Engineer job
            5 => [['Docker', 1], ['AWS', 1], ['MySQL', 2], ['PostgreSQL', 2]],
            // Vue.js Developer job
            6 => [['Vue.js', 1], ['JavaScript', 1], ['TypeScript', 2]],
            // Backend API Developer job
            7 => [['Node.js', 1], ['Python', 1], ['PostgreSQL', 1], ['Docker', 2]],
            // Mobile App Developer job
            8 => [['React', 1], ['JavaScript', 1], ['Node.js', 2]],
        ];

        foreach ($jobSkillMappings as $jobIndex => $skillList) {
            if (!isset($jobs[$jobIndex])) continue;

            foreach ($skillList as [$skillName, $type]) {
                $skillId = $skillMap[strtolower($skillName)] ?? null;
                if ($skillId) {
                    JobSkill::create([
                        'job_id' => $jobs[$jobIndex]->id,
                        'skill_id' => $skillId,
                        'type' => $type,
                        ]);
                    }
                }
            }
        }

    private function seedPipelines(array $jobs): array
    {
        $pipelines = [];
        foreach ($jobs as $job) {
            $pipeline = Pipeline::create([
                'name' => $job->title . ' Pipeline',
                'job_id' => $job->id,
            ]);
            $pipelines[] = $pipeline;
        }
        return $pipelines;
    }

    private function seedPipelineStages(array $pipelines, array $stages): void
    {
        $stageMap = [];
        foreach ($stages as $stage) {
            $stageMap[strtolower($stage->name)] = $stage->id;
        }

        $stageOrder = ['applied', 'screening', 'interview', 'offer', 'hired', 'rejected'];
        
        foreach ($pipelines as $pipeline) {
            $order = 1;
            foreach ($stageOrder as $stageName) {
                $stageId = $stageMap[strtolower($stageName)] ?? null;
                if ($stageId) {
                PipelineStages::create([
                    'pipeline_id' => $pipeline->id,
                        'stage_id' => $stageId,
                    'order' => $order++,
                ]);
            }
        }
        }
    }

    private function seedCandidates(array $recruiters): array
    {
        $candidates = [
            [
                'full_name' => 'Alice Johnson',
                'age' => 28,
                'email' => 'alice.johnson@example.com',
                'phone_number' => '+1-555-0101',
                'location' => 'New York, NY',
                'level' => 'Senior',
                'github_url' => 'https://github.com/alicejohnson',
                'linkedin_url' => 'https://linkedin.com/in/alicejohnson',
                'cv_path' => '/storage/cvs/alice_johnson.pdf',
                'recruiter_id' => $recruiters[0]->id,
            ],
            [
                'full_name' => 'Bob Smith',
                'age' => 32,
                'email' => 'bob.smith@example.com',
                'phone_number' => '+1-555-0102',
                'location' => 'San Francisco, CA',
                'level' => 'Mid-level',
                'github_url' => 'https://github.com/bobsmith',
                'linkedin_url' => 'https://linkedin.com/in/bobsmith',
                'cv_path' => '/storage/cvs/bob_smith.pdf',
                'recruiter_id' => $recruiters[0]->id,
            ],
            [
                'full_name' => 'Charlie Brown',
                'age' => 25,
                'email' => 'charlie.brown@example.com',
                'phone_number' => '+1-555-0103',
                'location' => 'Austin, TX',
                'level' => 'Junior',
                'github_url' => 'https://github.com/charliebrown',
                'linkedin_url' => 'https://linkedin.com/in/charliebrown',
                'cv_path' => '/storage/cvs/charlie_brown.pdf',
                'recruiter_id' => $recruiters[1]->id,
            ],
            [
                'full_name' => 'Diana Prince',
                'age' => 30,
                'email' => 'diana.prince@example.com',
                'phone_number' => '+1-555-0104',
                'location' => 'Seattle, WA',
                'level' => 'Senior',
                'github_url' => 'https://github.com/dianaprince',
                'linkedin_url' => 'https://linkedin.com/in/dianaprince',
                'cv_path' => '/storage/cvs/diana_prince.pdf',
                'recruiter_id' => $recruiters[2]->id,
            ],
            [
                'full_name' => 'Edward Norton',
                'age' => 27,
                'email' => 'edward.norton@example.com',
                'phone_number' => '+1-555-0105',
                'location' => 'Chicago, IL',
                'level' => 'Mid-level',
                'github_url' => 'https://github.com/edwardnorton',
                'linkedin_url' => 'https://linkedin.com/in/edwardnorton',
                'cv_path' => '/storage/cvs/edward_norton.pdf',
                'recruiter_id' => $recruiters[3]->id,
            ],
            [
                'full_name' => 'Fiona Green',
                'age' => 29,
                'email' => 'fiona.green@example.com',
                'phone_number' => '+1-555-0106',
                'location' => 'Boston, MA',
                'level' => 'Senior',
                'github_url' => 'https://github.com/fionagreen',
                'linkedin_url' => 'https://linkedin.com/in/fionagreen',
                'cv_path' => '/storage/cvs/fiona_green.pdf',
                'recruiter_id' => $recruiters[4]->id,
            ],
            [
                'full_name' => 'George Wilson',
                'age' => 26,
                'email' => 'george.wilson@example.com',
                'phone_number' => '+1-555-0107',
                'location' => 'Los Angeles, CA',
                'level' => 'Mid-level',
                'github_url' => 'https://github.com/georgewilson',
                'linkedin_url' => 'https://linkedin.com/in/georgewilson',
                'cv_path' => '/storage/cvs/george_wilson.pdf',
                'recruiter_id' => $recruiters[5]->id,
            ],
            [
                'full_name' => 'Hannah Martinez',
                'age' => 31,
                'email' => 'hannah.martinez@example.com',
                'phone_number' => '+1-555-0108',
                'location' => 'Denver, CO',
                'level' => 'Senior',
                'github_url' => 'https://github.com/hannahmartinez',
                'linkedin_url' => 'https://linkedin.com/in/hannahmartinez',
                'cv_path' => '/storage/cvs/hannah_martinez.pdf',
                'recruiter_id' => $recruiters[0]->id,
            ],
            [
                'full_name' => 'Ian Thompson',
                'age' => 24,
                'email' => 'ian.thompson@example.com',
                'phone_number' => '+1-555-0109',
                'location' => 'Portland, OR',
                'level' => 'Junior',
                'github_url' => 'https://github.com/ianthompson',
                'linkedin_url' => 'https://linkedin.com/in/ianthompson',
                'cv_path' => '/storage/cvs/ian_thompson.pdf',
                'recruiter_id' => $recruiters[1]->id,
            ],
            [
                'full_name' => 'Julia Davis',
                'age' => 33,
                'email' => 'julia.davis@example.com',
                'phone_number' => '+1-555-0110',
                'location' => 'Miami, FL',
                'level' => 'Senior',
                'github_url' => 'https://github.com/juliadavis',
                'linkedin_url' => 'https://linkedin.com/in/juliadavis',
                'cv_path' => '/storage/cvs/julia_davis.pdf',
                'recruiter_id' => $recruiters[2]->id,
            ],
        ];

        $createdCandidates = [];
        foreach ($candidates as $candidateData) {
            $candidate = Candidate::create($candidateData);
            $createdCandidates[] = $candidate;
        }

        return $createdCandidates;
    }

    private function seedCandidateJobs(array $candidates, array $jobs, array $recruiters): void
    {
        $sources = ['LinkedIn', 'Referral', 'Job Board', 'Company Website', 'Recruiter Contact'];
        
        // Assign candidates to various jobs
        $assignments = [
            [0, 0, $recruiters[0]->id, 'LinkedIn'], // Alice -> PHP Developer
            [1, 0, $recruiters[0]->id, 'Job Board'], // Bob -> PHP Developer
            [2, 1, $recruiters[1]->id, 'Referral'], // Charlie -> React Developer
            [3, 1, $recruiters[2]->id, 'LinkedIn'], // Diana -> React Developer
            [4, 2, $recruiters[3]->id, 'Company Website'], // Edward -> Full Stack
            [5, 3, $recruiters[4]->id, 'LinkedIn'], // Fiona -> Python Django
            [6, 4, $recruiters[5]->id, 'Job Board'], // George -> JavaScript
            [7, 5, $recruiters[0]->id, 'Referral'], // Hannah -> DevOps
            [8, 6, $recruiters[1]->id, 'LinkedIn'], // Ian -> Vue.js
            [9, 7, $recruiters[2]->id, 'Company Website'], // Julia -> Backend API
        ];

        foreach ($assignments as [$candidateIndex, $jobIndex, $recruiterId, $source]) {
            if (isset($candidates[$candidateIndex]) && isset($jobs[$jobIndex])) {
                CandidateJob::create([
                    'candidate_id' => $candidates[$candidateIndex]->id,
                    'job_id' => $jobs[$jobIndex]->id,
                    'source' => $source,
                    'recruiter_id' => $recruiterId,
                ]);
            }
        }
    }

    private function seedCandidatePipelineStages(array $candidates, array $jobs, array $pipelines, array $stages): void
    {
        $stageMap = [];
        foreach ($stages as $stage) {
            $stageMap[strtolower($stage->name)] = $stage->id;
        }

        // Get pipeline stages for each pipeline
        $pipelineStageMap = [];
        foreach ($pipelines as $pipeline) {
            $pipelineStages = PipelineStages::where('pipeline_id', $pipeline->id)
                ->orderBy('order')
                ->get();
            $pipelineStageMap[$pipeline->id] = $pipelineStages->pluck('id', 'order')->toArray();
        }

        $assignments = [
            [0, 0, 'interview', 'Strong technical background, passed screening'],
            [1, 0, 'screening', 'Initial screening completed'],
            [2, 1, 'applied', 'New application received'],
            [3, 1, 'interview', 'Technical interview scheduled'],
            [4, 2, 'offer', 'Ready to make offer'],
            [5, 3, 'screening', 'Resume review in progress'],
            [6, 4, 'applied', 'Application submitted'],
            [7, 5, 'interview', 'DevOps assessment completed'],
            [8, 6, 'screening', 'Skills assessment pending'],
            [9, 7, 'interview', 'API design review scheduled'],
        ];

        foreach ($assignments as [$candidateIndex, $jobIndex, $stageName, $notes]) {
            if (!isset($candidates[$candidateIndex]) || !isset($jobs[$jobIndex])) continue;
            
            $pipeline = $pipelines[$jobIndex] ?? null;
            if (!$pipeline) continue;

            $stageId = $stageMap[strtolower($stageName)] ?? null;
            if (!$stageId) continue;

            // Find pipeline_stage_id
            $pipelineStages = $pipelineStageMap[$pipeline->id] ?? [];
            $pipelineStageId = null;
            foreach ($pipelineStages as $order => $psId) {
                $ps = PipelineStages::find($psId);
                if ($ps && $ps->stage_id == $stageId) {
                    $pipelineStageId = $psId;
                    break;
                }
            }

            if ($pipelineStageId) {
                        CandidatePipelineStage::create([
                    'candidate_id' => $candidates[$candidateIndex]->id,
                    'pipeline_stage_id' => $pipelineStageId,
                    'job_id' => $jobs[$jobIndex]->id,
                    'notes' => $notes,
                            'moved_at' => now()->subDays(rand(1, 30)),
                        ]);
                    }
                }
    }

    private function seedInterviews(array $candidates, array $interviewers): array
    {
        $interviews = [];
        
        // Create interviews for some candidates
        $interviewData = [
            [0, 0, 'Technical interview focusing on PHP and Laravel expertise', 60, now()->addDays(2), 'scheduled'],
            [0, 1, 'Second round interview with team lead', 45, now()->addDays(5), 'scheduled'],
            [3, 0, 'React and frontend architecture discussion', 60, now()->addDays(3), 'scheduled'],
            [4, 1, 'Full stack development assessment', 90, now()->addDays(1), 'scheduled'],
            [7, 1, 'DevOps infrastructure and CI/CD discussion', 60, now()->addDays(4), 'scheduled'],
        ];

        foreach ($interviewData as [$candidateIndex, $interviewerIndex, $notes, $duration, $scheduledAt, $status]) {
            if (isset($candidates[$candidateIndex]) && isset($interviewers[$interviewerIndex])) {
                $interview = Interview::create([
                    'candidate_id' => $candidates[$candidateIndex]->id,
                    'interviewer_id' => $interviewers[$interviewerIndex]->id,
                    'notes' => $notes,
                    'duration' => $duration,
                    'scheduled_at' => $scheduledAt,
                    'status' => $status,
                ]);
                $interviews[] = $interview;
            }
        }

        return $interviews;
    }

    private function seedScoreLabels(): array
    {
        $scoreLabels = [
            ['name' => 'Technical Skills'],
            ['name' => 'Communication', ], 
            ['name' => 'Problem Solving', ], 
            ['name' => 'Cultural Fit', ], 
            ['name' => 'Experience', ], 
        ];

        $createdLabels = [];
        foreach ($scoreLabels as $labelData) {
            $label = ScoreLabel::updateOrCreate(
                ['name' => $labelData['name']],
                ['name' => $labelData['name']]
            );
            $createdLabels[] = $label;
        }

        return $createdLabels;
    }

    private function seedScorecards(array $candidates, array $interviews, array $jobs, array $scoreLabels): void
    {
        if (empty($interviews) || empty($scoreLabels)) return;

        $labelMap = [];
        foreach ($scoreLabels as $label) {
            $labelMap[strtolower($label->name)] = $label->id;
        }

        // Create scorecards for interviews
        foreach ($interviews as $index => $interview) {
            $candidate = $candidates[$interview->candidate_id - 1] ?? null;
            if (!$candidate) continue;

            // Find job for this candidate
            $candidateJob = CandidateJob::where('candidate_id', $candidate->id)->first();
            if (!$candidateJob) continue;

            $job = $jobs[$candidateJob->job_id - 1] ?? null;
            if (!$job) continue;

            // Create 2-3 scorecards per interview
            $scorecardLabels = ['Technical Skills', 'Communication', 'Problem Solving'];
            foreach ($scorecardLabels as $labelName) {
                $labelId = $labelMap[strtolower($labelName)] ?? null;
                if ($labelId) {
                    Scorecard::create([
                        'candidate_id' => $candidate->id,
                        'interview_id' => $interview->id,
                        'status' => 'completed',
                        'score_rate' => rand(6, 10),
                        'job_id' => $job->id,
                        'scorelabel_id' => $labelId,
                    ]);
                }
            }
        }
    }

    private function seedOffers(array $candidates, array $jobs, array $recruiters): void
    {
        $offerData = [
            [4, 2, 120000.00, now()->addMonths(1), 'draft', 'Full-time', $recruiters[3]->id],
            [3, 1, 95000.00, now()->addMonths(2), 'sent', 'Full-time', $recruiters[2]->id],
        ];

        foreach ($offerData as [$candidateIndex, $jobIndex, $salary, $startDate, $status, $contractType, $recruiterId]) {
            if (isset($candidates[$candidateIndex]) && isset($jobs[$jobIndex])) {
                    Offer::create([
                    'candidate_id' => $candidates[$candidateIndex]->id,
                    'job_id' => $jobs[$jobIndex]->id,
                    'salary' => $salary,
                    'start_date' => $startDate,
                    'status' => $status,
                    'contract_type' => $contractType,
                    'offer_letter_template' => 'Standard offer letter template',
                    'recruiter_id' => $recruiterId,
                ]);
            }
        }
    }

    private function seedCopilotQueries(array $candidates, array $jobs, array $recruiters): void
    {
        $queries = [
            [0, 0, 'What are the candidate\'s main strengths?', 'The candidate has strong PHP and Laravel experience with 5+ years in the field.', $recruiters[0]->id, 'Based on CV and interview notes', 1],
            [1, 0, 'Compare this candidate with others', 'This candidate has good fundamentals but less experience than candidate 1.', $recruiters[0]->id, 'Based on screening notes', 2],
        ];

        foreach ($queries as [$candidateIndex, $jobIndex, $queryText, $responseText, $recruiterId, $citationText, $source]) {
            if (isset($candidates[$candidateIndex]) && isset($jobs[$jobIndex])) {
                CopilotQuery::create([
                    'candidate_id' => $candidates[$candidateIndex]->id,
                    'job_id' => $jobs[$jobIndex]->id,
                    'query_text' => $queryText,
                    'response_text' => $responseText,
                    'recruiter_id' => $recruiterId,
                    'citation_text' => $citationText,
                    'source' => $source,
                    ]);
                }
            }
        }

    private function seedDocuments(array $candidates): array
    {
        $documents = [];
        foreach ($candidates as $candidate) {
            $document = Document::create([
                'candidate_id' => $candidate->id,
                'file_path' => '/storage/documents/candidate_' . $candidate->id . '_cv.pdf',
            ]);
            $documents[] = $document;
        }
        return $documents;
    }

    private function seedDocumentChunks(array $documents): void
    {
        foreach ($documents as $index => $document) {
            DocumentChunk::create([
                'document_id' => $document->id,
                'chunk_text' => "Candidate CV content chunk " . ($index + 1) . " - Professional experience and skills summary.",
                'embedding' => null,
                'chunk_index' => 0,
                'page_number' => 1,
                'section' => 'Introduction',
                'token_count' => 150,
            ]);
        }
    }
}
