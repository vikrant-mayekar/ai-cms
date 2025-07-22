<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin User
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@cms.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        // Create Author User
        User::create([
            'name' => 'Author User',
            'email' => 'author@cms.com',
            'password' => Hash::make('password123'),
            'role' => 'author',
        ]);

        // Create additional test users
        User::create([
            'name' => 'John Doe',
            'email' => 'john@cms.com',
            'password' => Hash::make('password123'),
            'role' => 'author',
        ]);

        User::create([
            'name' => 'Jane Smith',
            'email' => 'jane@cms.com',
            'password' => Hash::make('password123'),
            'role' => 'author',
        ]);
    }
} 