<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockTransfersTable extends Migration
{
    public function up()
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();

            // 🔐 SaaS OWNERSHIP (VERY IMPORTANT)
            $table->unsignedBigInteger('owner_id');

            $table->foreignId('product_id')
                ->constrained('products')
                ->onDelete('cascade');

            $table->foreignId('shop_id')
                ->constrained('shops')
                ->onDelete('cascade'); // source shop

            $table->foreignId('to_shop_id')
                ->constrained('shops')
                ->onDelete('cascade'); // destination shop

            $table->integer('quantity');
            $table->decimal('cost_price', 10, 2);
            $table->decimal('selling_price', 10, 2);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_transfers');
    }
}
