<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
     * Seed a test user with a Sanctum API token.
     */
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
            ],
        );

        $user->tokens()->delete();

        $token = $user->createToken('test-token')->plainTextToken;

        $this->command->info('  Test user seeded:');
        $this->command->info("  email: {$user->email}");
        $this->command->info('  password: password');
        $this->command->info("  token: {$token}");
    }
}
