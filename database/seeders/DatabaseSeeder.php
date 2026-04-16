<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(10)->create();

        User::updateOrCreate(
            ['email' => 'zainalrtf@gmail.com'],
            [
                'name' => 'Zainal Admin',
                'password' => Hash::make('password'),
                'role' => 'super admin',
                'position' => 'IT Manager',
                'department' => 'IT Department',
                'phone' => '081234567890',
            ]
        );

        // Create test user for face recognition testing
        User::updateOrCreate(
            ['email' => 'testuser@example.com'],
            [
                'name' => 'Test User Face',
                'password' => Hash::make('password'),
                'role' => 'user',
                'position' => 'Staff',
                'department' => 'IT Department',
                'phone' => '081234567891',
            ]
        );

        // Data dummy for company with FACE RECOGNITION enabled
        \App\Models\Company::create([
            'name' => 'PT. Sejahtera Selamanya',
            'email' => 'sejahtera@gmail.com',
            'address' => 'Jl. Raya Kedung Turi No. 20, Sleman, DIY',
            'latitude' => '-7.747033',
            'longitude' => '110.355398',
            'radius_km' => '0.5',
            'time_in' => '08:00',
            'time_out' => '17:00',
            'attendance_type' => 'face', // Enable face recognition attendance
        ]);

        $this->call([
            AttendanceSeeder::class,
            PermissionSeeder::class,
        ]);
    }
}
