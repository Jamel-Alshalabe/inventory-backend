<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movements', function (Blueprint $table): void {
            $table->id();
            $table->enum('type', ['in', 'out']);
            $table->string('product_code');
            $table->string('product_name');
            $table->integer('quantity');
            $table->double('price');
            $table->double('total');
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->index(['warehouse_id', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index('product_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movements');
    }
};
