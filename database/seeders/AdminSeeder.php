<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@binus.ac.id'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('Admin123'),
                'role' => 'admin',
            ]
        );
    }
}