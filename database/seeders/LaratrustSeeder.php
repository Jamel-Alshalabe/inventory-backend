<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Role;
use App\Models\Permission;

class LaratrustSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Clear existing roles and permissions
        DB::table('permission_role')->truncate();
        DB::table('permission_user')->truncate();
        DB::table('role_user')->truncate();
        Role::truncate();
        Permission::truncate();
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Create permissions
        $permissions = [
            // Users Management
            [
                'name' => 'view-users',
                'display_name' => 'إنشاء المستخدمين',
                'description' => 'القدرة على إنشاء مستخدمين جدد',
            ],
        
            [
                'name' => 'view-subscriptions',
                'display_name' => 'عرض الاشتراكات',
                'description' => 'القدرة على عرض قائمة الاشتراكات',
            ],

           
            [
                'name' => 'view-products',
                'display_name' => 'عرض المنتجات',
                'description' => 'القدرة على عرض قائمة المنتجات',
            ],

            [
                'name' => 'view-movements',
                'display_name' => 'عرض حركات المخزون',
                'description' => 'القدرة على عرض حركات المخزون',
            ],

            [
                'name' => 'view-invoices',
                'display_name' => 'عرض الفواتير',
                'description' => 'القدرة على عرض قائمة الفواتير',
            ],

            [
                'name' => 'view-warehouses',
                'display_name' => 'عرض المستودعات',
                'description' => 'القدرة على عرض قائمة المستودعات',
            ],

            // Reports
            [
                'name' => 'view-reports',
                'display_name' => 'عرض التقارير',
                'description' => 'القدرة على عرض التقارير المالية والمخزنية',
            ],
           

            // Settings
            [
                'name' => 'manage-settings',
                'display_name' => 'إدارة الإعدادات',
                'description' => 'القدرة على تعديل إعدادات النظام',
            ],

            // Logs
            [
                'name' => 'view-logs',
                'display_name' => 'عرض السجلات',
                'description' => 'القدرة على عرض سجلات النشاط',
            ],
        

            // Dashboard
            [
                'name' => 'view-dashboard',
                'display_name' => 'عرض لوحة التحكم',
                'description' => 'القدرة على عرض لوحة تحكم الإحصائيات',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        // Create roles
        $superAdminRole = Role::create([
            'name' => 'super_admin',
            'display_name' => 'مدير النظام الرئيسي',
            'description' => 'صلاحيات كاملة على النظام',
        ]);
        $adminRole = Role::create([
            'name' => 'admin',
            'display_name' => 'مدير النظام',
            'description' => 'صلاحيات كاملة على النظام',
        ]);

        $userRole = Role::create([
            'name' => 'user',
            'display_name' => 'موظف',
            'description' => 'موظف بصلاحيات محدودة',
        ]);

       

        $editorRole = Role::create([
            'name' => 'editor',
            'display_name' => 'محرر',
            'description' => 'محرر بصلاحيات متوسطة',
        ]);

        // Assign permissions to roles
        $adminRolePermissions = Permission::whereNotIn('name', [
            'create-subscriptions',
            'edit-subscriptions',
            'delete-subscriptions',
            'view-subscriptions',
        ])->get();
        // Admin - All permissions (Company Owner)
        $adminRole->syncPermissions($adminRolePermissions);

        // Super Admin - Limited permissions (Platform Owner)
        $superAdminPermissions = Permission::whereIn('name', [
            'create-users',
            'edit-users',
            'delete-users',
            'view-users',
            'create-subscriptions',
            'edit-subscriptions',
            'delete-subscriptions',
            'view-subscriptions',
            'manage-settings',
        ])->get();
        $superAdminRole->syncPermissions($superAdminPermissions);

     

        // User - Basic operations
        $userPermissions = Permission::whereIn('name', [
            'view-dashboard',
            'view-products',
            'create-products',
            'edit-products',
            'view-movements',
            'create-movements',
            'view-invoices',
            'create-invoices',
            'view-warehouses',
        ])->get();

        $userRole->syncPermissions($userPermissions);

        // Editor - Extended permissions
        $editorPermissions = Permission::whereIn('name', [
            'view-dashboard',
            'view-products',
            'create-products',
            'edit-products',
            'delete-products',
            'view-movements',
            'create-movements',
            'edit-movements',
            'view-invoices',
            'create-invoices',
            'edit-invoices',
            'view-warehouses',
            'view-reports',
            'export-reports',
        ])->get();
        
        $editorRole->syncPermissions($editorPermissions);

      
        

      
    }
}
