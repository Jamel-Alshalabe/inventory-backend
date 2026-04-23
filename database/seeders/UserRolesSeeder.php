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
        // Create dedicated user accounts for each role
        
        // Super Admin user
        $superAdmin = User::firstOrCreate(
            ['username' => 'superadmin'],
            [
                'password' => bcrypt('Pass@123'),
            ]
        );
        $superAdmin->syncRoles(['super_admin']);
        $this->command->info('✅ Super Admin user created: username "superadmin", password "Pass@123"');
        
        // Admin user
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'password' => bcrypt('Pass@123'),
            ]
        );
        $admin->syncRoles(['admin']);
        $this->command->info('✅ Admin user created: username "admin", password "Pass@123"');

       

       
        
    }
}
