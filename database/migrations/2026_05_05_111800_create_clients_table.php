<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('admin_id')->index();
            $table->string('first_name', 191);
            $table->string('last_name', 191)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('phone_number', 64)->nullable();
            $table->string('whatsapp_number', 64)->nullable();
            $table->string('address', 191)->nullable();
            $table->string('company_name', 191)->nullable();
            $table->string('company_address', 191)->nullable();
            $table->timestamps();

            $table->foreign('admin_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
