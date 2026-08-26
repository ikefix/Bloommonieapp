<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();

            // 🔐 SaaS OWNERSHIP (VERY IMPORTANT)
            $table->unsignedBigInteger('owner_id');

            $table->string('transaction_id')->nullable();

                // 📴 OFFLINE SYNC
            // Device-generated UUID for the whole offline basket. Used as the
            // idempotency key when replaying offline sales through the sync
            // endpoint — not unique at the column level since every item in one
            // offline basket shares the same client_sale_id.
            $table->string('client_sale_id')->nullable()->index();
        
            // True when this row was created via the offline sync endpoint
            // rather than the normal online store() flow.
            $table->boolean('synced_offline')->default(false);

            // 🧍 Customer info
            $table->string('customer_name')->nullable();
            $table->string('customer_phone', 20)->nullable();

            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('shop_id')->nullable();

            $table->integer('quantity');
            $table->decimal('total_price', 10, 2);

            $table->unsignedBigInteger('cashier_id')->nullable();

            // 🆕 Discount
            $table->enum('discount_type', ['none', 'percentage', 'flat'])->default('none');
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);

            $table->enum('payment_method', ['cash', 'card', 'transfer'])->default('cash');

            // ⭐ SALE TYPE
            $table->enum('sale_type', ['invoice', 'cashier'])->default('cashier');

            $table->timestamps();

            // 🔗 Foreign keys
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');

            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('set null');
    });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_items');
    }
};
