<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shivamogga = District::where('name', 'Shivamogga')->first();

        // Super Admin
        User::firstOrCreate(
            ['email' => 'admin@krushibaandhava.org'],
            [
                'name' => 'Krushi Baandhava Admin',
                'phone' => '9800000001',
                'role' => User::ROLE_SUPER_ADMIN,
                'preferred_language' => 'kn',
                'district_id' => $shivamogga?->id,
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        // Data Admin
        User::firstOrCreate(
            ['email' => 'data@krushibaandhava.org'],
            [
                'name' => 'Mandi Data Operations',
                'phone' => '9800000002',
                'role' => User::ROLE_DATA_ADMIN,
                'preferred_language' => 'en',
                'district_id' => $shivamogga?->id,
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        // Sample Farmer User
        User::firstOrCreate(
            ['email' => 'farmer@krushibaandhava.org'],
            [
                'name' => 'Manjunath Gowda',
                'phone' => '9800000003',
                'role' => User::ROLE_FARMER,
                'preferred_language' => 'kn',
                'district_id' => $shivamogga?->id,
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
    }
}
