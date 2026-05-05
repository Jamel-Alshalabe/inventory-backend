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
        // Clear existing data to avoid duplicates
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('permission_role')->truncate();
        DB::table('permission_user')->truncate();
        DB::table('role_user')->truncate();
        DB::table('roles')->truncate();
        DB::table('permissions')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Define full permission set
        $permissionGroups = [
            'users' => [
                'view-users' => ['عرض المستخدمين', 'القدرة على عرض قائمة المستخدمين'],
                'create-user' => ['إنشاء مستخدم', 'القدرة على إنشاء مستخدمين جدد'],
                'edit-user' => ['تعديل مستخدم', 'القدرة على تعديل بيانات المستخدمين'],
                'delete-user' => ['حذف مستخدم', 'القدرة على حذف المستخدمين'],
            ],
            'subscriptions' => [
                'view-subscriptions' => ['عرض الاشتراكات', 'القدرة على عرض قائمة الاشتراكات'],
                'manage-subscriptions' => ['إدارة الاشتراكات', 'القدرة على تعديل الاشتراكات'],
            ],
            'products' => [
                'view-products' => ['عرض المنتجات', 'القدرة على عرض قائمة المنتجات'],
                'create-product' => ['إضافة منتج', 'القدرة على إضافة منتجات جديدة'],
                'edit-product' => ['تعديل منتج', 'القدرة على تعديل بيانات المنتجات'],
                'delete-product' => ['حذف منتج', 'القدرة على حذف المنتجات'],
                'import-products' => ['استيراد منتجات', 'القدرة على استيراد المنتجات من ملفات إكسيل'],
            ],
            'warehouses' => [
                'view-warehouses' => ['عرض المخازن', 'القدرة على عرض قائمة المخازن'],
                'create-warehouse' => ['إضافة مخزن', 'القدرة على إضافة مخازن جديدة'],
                'edit-warehouse' => ['تعديل مخزن', 'القدرة على تعديل بيانات المخازن'],
                'delete-warehouse' => ['حذف مخزن', 'القدرة على حذف المخازن'],
                'export-warehouse' => ['تصدير مخزن', 'القدرة على تصدير بيانات المخازن'],
            ],
            'movements' => [
                'view-movements' => ['عرض حركات المخزون', 'القدرة على عرض حركات المخزون'],
                'create-movement' => ['إضافة حركة', 'القدرة على إضافة حركات مخزون جديدة'],
            ],
            'invoices' => [
                'view-invoices' => ['عرض الفواتير', 'القدرة على عرض قائمة الفواتير'],
                'create-invoice' => ['إنشاء فاتورة', 'القدرة على إنشاء فواتير جديدة'],
                'edit-invoice' => ['تعديل فاتورة', 'القدرة على تعديل الفواتير'],
                'delete-invoice' => ['حذف فاتورة', 'القدرة على حذف الفواتير'],
                'print-invoice' => ['طباعة فاتورة', 'القدرة على طباعة الفواتير'],
            ],
            'reports' => [
                'view-reports' => ['عرض التقارير', 'القدرة على عرض التقارير المالية والمخزنية'],
            ],
            'settings' => [
                'manage-settings' => ['إدارة الإعدادات', 'القدرة على تعديل إعدادات النظام'],
            ],
            'clients' => [
                'view-clients' => ['عرض العملاء', 'القدرة على عرض قائمة العملاء'],
                'create-client' => ['إضافة عميل', 'القدرة على إضافة عملاء جدد'],
                'edit-client' => ['تعديل عميل', 'القدرة على تعديل بيانات العملاء'],
                'delete-client' => ['حذف عميل', 'القدرة على حذف العملاء'],
            ],
            'suppliers' => [
                'view-suppliers' => ['عرض الموردين', 'القدرة على عرض قائمة الموردين'],
                'create-supplier' => ['إضافة مورد', 'القدرة على إضافة موردين جدد'],
                'edit-supplier' => ['تعديل مورد', 'القدرة على تعديل بيانات الموردين'],
                'delete-supplier' => ['حذف مورد', 'القدرة على حذف الموردين'],
            ],
            'logs' => [
                'view-logs' => ['عرض السجلات', 'القدرة على عرض سجلات النشاط'],
            ],
            'dashboard' => [
                'view-dashboard' => ['عرض لوحة التحكم', 'القدرة على عرض لوحة تحكم الإحصائيات'],
            ],
        ];

        $allCreatedPermissions = [];
        foreach ($permissionGroups as $group => $perms) {
            foreach ($perms as $name => $details) {
                $allCreatedPermissions[$name] = Permission::create([
                    'name' => $name,
                    'display_name' => $details[0],
                    'description' => $details[1],
                ]);
            }
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
            'description' => 'صلاحيات كاملة على مستوى المؤسسة',
        ]);

        $auditorRole = Role::create([
            'name' => 'auditor',
            'display_name' => 'مراجع',
            'description' => 'صلاحيات العرض فقط لجميع أجزاء النظام',
        ]);

        $userRole = Role::create([
            'name' => 'user',
            'display_name' => 'موظف',
            'description' => 'موظف بصلاحيات محددة',
        ]);

     
        
        // Super Admin - All platform management permissions
        $superAdminPermissions = Permission::whereIn('name', [
            'view-users', 'create-user', 'edit-user', 'delete-user',
            'view-subscriptions', 'manage-subscriptions',
            'manage-settings'
        ])->get();
        $superAdminRole->syncPermissions($superAdminPermissions);

        // Admin - Everything except subscriptions management (usually managed by platform owner)
        $adminPermissions = Permission::whereNotIn('name', [
            'view-subscriptions', 'manage-subscriptions'
        ])->get();
        $adminRole->syncPermissions($adminPermissions);

        
        
    }
}
