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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            // 🔐 SaaS OWNERSHIP (CRITICAL)
            $table->unsignedBigInteger('owner_id');

            $table->foreignId('customer_id')->constrained()->onDelete('cascade');

            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // creator (cashier/admin)

            $table->foreignId('shop_id')->constrained()->onDelete('cascade'); // shop

            $table->string('invoice_number')->unique();
            $table->date('invoice_date')->default(now());

            $table->text('goods'); // JSON items list

            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2);

            // ⭐ PAYMENT FIELDS
            $table->enum('payment_type', ['full', 'part'])->default('full');
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->decimal('balance', 10, 2)->default(0);
            $table->enum('payment_status', ['paid', 'owing'])->default('paid');

            $table->timestamps();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
