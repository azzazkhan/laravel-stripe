<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->unsignedMediumInteger('amount');
            $table->unsignedMediumInteger('received')->default(0);

            $table->string('session')->unique();
            $table->string('secret');

            $table->enum('status', ['pending', 'unpaid', 'cancelled'])->default('pending');
            $table->json('metadata')->nullable();

            $table->foreignId('user_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
