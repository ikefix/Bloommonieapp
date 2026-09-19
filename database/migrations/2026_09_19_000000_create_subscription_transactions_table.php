<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->string('plan');                       // basic / lite / business
            $table->string('billing');                    // monthly / yearly
            $table->unsignedBigInteger('amount');         // in kobo
            $table->string('status')->default('pending'); // pending / success
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_transactions');
    }
};