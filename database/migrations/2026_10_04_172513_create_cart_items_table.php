<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cart_id')
                ->constrained('carts')
                ->onDelete('cascade');

            $table->foreignId('hardware_product_id')
                ->constrained('hardware_products')
                ->onDelete('cascade');

            $table->integer('quantity')->default(1);

            $table->decimal('price', 12, 2);

            $table->timestamps();

            $table->unique([
                'cart_id',
                'hardware_product_id'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};