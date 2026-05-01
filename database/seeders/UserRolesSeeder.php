<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        $superAdmin = User::firstOrCreate(
            ['username' => 'superadmin'],
            [
                'password' => bcrypt('Pass@123'),
            ]
        );
        $superAdmin->syncRoles(['super_admin']);
        
    }
}
