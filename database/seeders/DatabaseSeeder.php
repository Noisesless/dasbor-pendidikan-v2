<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $email = (string) env('SEED_ADMIN_EMAIL', 'admin@banjarnegara.go.id');
        $password = (string) (env('SEED_ADMIN_PASSWORD') ?: Str::random(24));

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrator',
                'password' => Hash::make($password),
            ],
        );
    }
}
