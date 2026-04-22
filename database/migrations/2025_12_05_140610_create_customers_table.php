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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            // 🔐 SaaS OWNERSHIP (CRITICAL)
            $table->unsignedBigInteger('owner_id');

            $table->string('name');
            
            // ⚠️ IMPORTANT NOTE: remove unique if shared names/emails across admins
            $table->string('email')->nullable();
            
            $table->string('phone');
            $table->text('address')->nullable();
            $table->string('company')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
