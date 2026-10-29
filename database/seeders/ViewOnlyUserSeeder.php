<?php

namespace Database\Seeders;

use App\Models\UsersAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ViewOnlyUserSeeder extends Seeder
{
    public function run(): void
    {
        UsersAccount::updateOrCreate(
            ['email' => 'user@idms.com'],
            [
                'fullname' => 'View Only User',
                'password' => Hash::make('user'),
                'role' => 'viewer',
            ],
        );
    }
}
