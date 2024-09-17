<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create default user
        User::create([
            'name' => 'Default User',
            'email' => 'default@example.com',
            'password' => Hash::make('password'), // default password
        ]);
    }
}
