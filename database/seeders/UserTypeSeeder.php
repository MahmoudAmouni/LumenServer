<?php

namespace Database\Seeders;

use App\Models\UserType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing user types
        DB::table('user_types')->truncate();

        // Seed user types
        $userTypes = [
            ['id' => 1, 'name' => 'Admin'],
            ['id' => 2, 'name' => 'Recruiter'],
            ['id' => 3, 'name' => 'Interviewer'],
        ];

        foreach ($userTypes as $type) {
            UserType::updateOrCreate(
                ['id' => $type['id']],
                ['name' => $type['name']]
            );
        }
    }
}
