<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $main = Warehouse::firstOrCreate(['name' => 'المخزن الرئيسي']);
        Warehouse::firstOrCreate(['name' => 'مخزن قطع الغيار']);

        User::firstOrCreate(
            ['username' => 'admin'],
            ['password' => 'admin123', 'role' => Role::Admin, 'assigned_warehouse_id' => null],
        );
        User::firstOrCreate(
            ['username' => 'user'],
            ['password' => 'user123', 'role' => Role::User, 'assigned_warehouse_id' => $main->id],
        );
        User::firstOrCreate(
            ['username' => 'auditor'],
            ['password' => 'auditor123', 'role' => Role::Auditor, 'assigned_warehouse_id' => null],
        );

        foreach ([
            'companyName'    => 'شركة سنك لقطع غيار السيارات',
            'companyPhone'   => '+20 100 000 0000',
            'companyAddress' => 'القاهرة، مصر',
            'currency'       => 'ج.م',
        ] as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $samples = [
            ['name' => 'فلتر زيت تويوتا',        'code' => 'TY-OIL-001', 'buy_price' => 80,   'sell_price' => 120,  'quantity' => 45],
            ['name' => 'فلتر هواء هيونداي',       'code' => 'HY-AIR-002', 'buy_price' => 60,   'sell_price' => 95,   'quantity' => 32],
            ['name' => 'بوجيهات NGK',            'code' => 'NGK-SP-003', 'buy_price' => 45,   'sell_price' => 75,   'quantity' => 120],
            ['name' => 'زيت محرك موبيل 5W30',    'code' => 'MOB-5W30',   'buy_price' => 350,  'sell_price' => 480,  'quantity' => 24],
            ['name' => 'تيل فرامل أمامي',         'code' => 'BRK-FRT-005','buy_price' => 180,  'sell_price' => 260,  'quantity' => 18],
            ['name' => 'بطارية 70 أمبير',         'code' => 'BAT-70A',    'buy_price' => 1400, 'sell_price' => 1800, 'quantity' => 8],
            ['name' => 'كاوتش ميشلان 195/65',     'code' => 'MICH-195',   'buy_price' => 2200, 'sell_price' => 2750, 'quantity' => 16],
            ['name' => 'صدامات أمامية كيا',       'code' => 'KIA-BMP-008','buy_price' => 850,  'sell_price' => 1100, 'quantity' => 4],
        ];

        foreach ($samples as $row) {
            Product::firstOrCreate(
                ['code' => $row['code'], 'warehouse_id' => $main->id],
                $row + ['warehouse_id' => $main->id],
            );
        }
    }
}
