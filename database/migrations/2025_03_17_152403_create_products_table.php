<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // 🔐 SaaS OWNERSHIP
            $table->unsignedBigInteger('owner_id');

            // 🔗 Foreign Keys
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('shop_id');

            // 📦 Product Fields
            $table->string('name');
            $table->string('barcode')->nullable()->unique();

            $table->decimal('price', 10, 2);
            $table->decimal('cost_price', 10, 2);

            $table->decimal('stock_quantity', 15, 2)->default(0);
            $table->integer('stock_limit')->nullable()->default(0);

            /*
            |--------------------------------------------------------------------------
            | Manufacturing Fields (Optional)
            |--------------------------------------------------------------------------
            */

            // pcs, kg, litres, bags, cartons, trays, etc.
            $table->string('stock_unit')->nullable();

            // Conversion factor
            // Example:
            // Feed Bag = stock_unit(bags), unit_size = 50
            // meaning 1 bag = 50kg
            $table->decimal('unit_size', 15, 2)->nullable();

            $table->timestamps();

            // 🔐 Constraints
            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->onDelete('cascade');

            $table->foreign('shop_id')
                ->references('id')
                ->on('shops')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
};