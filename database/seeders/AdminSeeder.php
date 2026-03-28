<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class AdminSeeder extends Seeder
{
    public function run(): void
    {
        UserModel::updateOrCreate(
            ['email' => 'admin@banchimbocau.vn'],
            [
                'name'              => 'Admin',
                'password'          => Hash::make('password123!'),
                'email_verified_at' => now(),
            ],
        );
    }
}
