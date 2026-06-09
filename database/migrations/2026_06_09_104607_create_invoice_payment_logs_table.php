<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_payment_logs', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('owner_id');

            $table->foreignId('invoice_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('cashier_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->string('invoice_no');

            $table->string('type')->default('invoice_payment');

            $table->text('message');

            $table->decimal('amount_added', 10, 2);

            $table->decimal('total_paid', 10, 2);

            $table->decimal('balance', 10, 2);

            $table->string('updated_by');

            $table->unsignedBigInteger('updated_by_id');

            $table->timestamp('payment_updated_at');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payment_logs');
    }
};