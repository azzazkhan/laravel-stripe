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
            $table->ulid('ulid')->unique(); // Transaction ID

            $table->unsignedMediumInteger('amount'); // Amount requested
            $table->unsignedMediumInteger('received')->default(0); // Amount received
            $table->unsignedMediumInteger('fee')->default(0); // Stripe TAX

            $table->string('session')->unique(); // Stripe Checkout session ID

            $table->enum('status', ['pending', 'successful', 'cancelled'])->default('pending');
            $table->json('metadata')->nullable();

            $table->foreignId('user_id')->constrained();
            $table->foreignUlid('package_id')->constrained();

            // When this Stripe checkout session will expire, this will be
            // provided after the checkout session has been created
            $table->timestamp('expires_at')->nullable();
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
