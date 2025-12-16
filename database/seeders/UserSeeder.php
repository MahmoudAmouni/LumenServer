<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed admin users
        $adminUsers = [
            [
                'name' => 'Omar',
                'email' => 'omar@gmail.com',
                'password' => 'omar1234',
                'type_id' => 1, // Admin
            ],
            [
                'name' => 'Mahmoud',
                'email' => 'mahmoud@gmail.com',
                'password' => 'mahmoud1234',
                'type_id' => 1, // Admin
            ],
            [
                'name' => 'Mohammad',
                'email' => 'mohammad@gmail.com',
                'password' => 'mohammad1234',
                'type_id' => 1, // Admin
            ],
        ];

        foreach ($adminUsers as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make($userData['password']),
                    'type_id' => $userData['type_id'],
                    'company_id' => null, // Admins don't need company_id
                ]
            );
        }
    }
}
