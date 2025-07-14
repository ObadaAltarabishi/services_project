<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create(['email' => 'abd@gmail.com', 'name' => 'abd', 'password' => 123456789, 'phone_number' => 123, 'description' => 'test', 'experience_year' => 5, 'age' => 22, 'location' => 'damascus', 'picture' => 'dfsa', 'role' => 'admin']);
    }
}
