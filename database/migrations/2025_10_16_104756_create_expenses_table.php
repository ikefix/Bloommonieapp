<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();

            // 🔐 SaaS OWNERSHIP (CRITICAL)
            $table->unsignedBigInteger('owner_id');

            $table->unsignedBigInteger('shop_id')->nullable(); // still useful for branch-level tracking

            $table->string('title'); // Fuel, Maintenance
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();

            $table->date('date')->default(now());

            // better than string (optional upgrade)
            $table->unsignedBigInteger('added_by')->nullable(); // user_id (cashier/admin)

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
