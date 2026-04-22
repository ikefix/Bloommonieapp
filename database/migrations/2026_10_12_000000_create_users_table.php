<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // 🔑 ownership (SUPER IMPORTANT for SaaS isolation)
            $table->unsignedBigInteger('owner_id')->nullable();

            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();

            // 🏪 shop relation
            $table->unsignedBigInteger('shop_id')->nullable();

            // 👤 roles
            $table->enum('role', ['superadmin', 'admin', 'manager', 'cashier'])
                  ->default('cashier');


            // 🧾 subscription fields (for admin SaaS)
            $table->string('plan')->nullable();          // free_trial, monthly, yearly
            $table->string('plan_duration')->nullable(); // 1_month, 1_year
            $table->date('plan_start')->nullable();
            $table->date('plan_end')->nullable();

            $table->boolean('is_activated')->default(false);
            $table->date('activated_at')->nullable();

            $table->string('product_key')->nullable()->unique();

            // foreign key
            $table->foreign('shop_id')
                  ->references('id')
                  ->on('shops')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};