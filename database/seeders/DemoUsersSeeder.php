<?php
// database/seeders/DemoUsersSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'MAPO Officer',
                'email' => 'mapo@example.com',
                'password' => Hash::make('password'),
                'role' => 'MAPO',
                'organization' => 'MAPO Department',
            ],
            [
                'name' => 'FGV Staff Member',
                'email' => 'fgv@example.com',
                'password' => Hash::make('password'),
                'role' => 'FGV',
                'organization' => 'FGV Holdings',
            ],
            [
                'name' => 'Plantation Owner',
                'email' => 'owner@example.com',
                'password' => Hash::make('password'),
                'role' => 'PLANTATION_OWNER',
                'organization' => 'Private Plantation Sdn Bhd',
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }
}
