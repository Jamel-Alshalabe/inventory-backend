<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->text('value');
            $table->string('type')->default('string'); // string, json, number
            $table->timestamps();
        });

        // Insert default theme settings
        DB::table('settings')->insert([
            [
                'key' => 'background_color',
                'value' => '#08081a',
                'type' => 'string',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'surface_color',
                'value' => '#1a1a2e',
                'type' => 'string',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'text_color',
                'value' => '#e0e0e0',
                'type' => 'string',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'primary_action_color',
                'value' => '#1a56db',
                'type' => 'string',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'secondary_action_color',
                'value' => '#0ea5e9',
                'type' => 'string',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'font_family',
                'value' => 'Noto Kufi Arabic',
                'type' => 'string',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
