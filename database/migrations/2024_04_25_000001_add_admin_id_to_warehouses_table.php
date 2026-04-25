<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table): void {
            $table->foreignId('admin_id')->nullable()->after('id')->constrained('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table): void {
            $table->dropForeign(['admin_id']);
            $table->dropColumn('admin_id');
        });
    }
};
