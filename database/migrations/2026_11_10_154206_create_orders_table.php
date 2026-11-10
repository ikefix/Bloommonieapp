
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // Customer
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('order_number')->unique();

            $table->string('full_name');
            $table->string('email');
            $table->string('phone');
            $table->string('alternate_phone')->nullable();
            $table->string('company_name')->nullable();

            // Delivery
            $table->text('address');
            $table->string('apartment')->nullable();
            $table->string('city');
            $table->string('state');
            $table->string('postal_code')->nullable();
            $table->string('country')->default('Nigeria');

            $table->text('delivery_instructions')->nullable();
            $table->text('order_note')->nullable();

            // Financial details (NGN)
            $table->decimal('subtotal', 15, 2);
            $table->decimal('delivery_fee', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total', 15, 2);

            $table->string('currency', 3)->default('NGN');

            // Payment
            $table->string('payment_method')->default('paystack');
            $table->string('payment_status')->default('pending');
            $table->string('payment_reference')->nullable()->unique();
            $table->text('payment_url')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Order fulfillment
            $table->string('order_status')->default('pending');

            $table->timestamps();

            $table->index('email');
            $table->index('payment_status');
            $table->index('order_status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
};
