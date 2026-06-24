<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;              
use Illuminate\Support\Facades\Hash;  

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@demo.com',
            'password' => Hash::make('123456'),
            'role' => 'admin',
        ]);
    }
}