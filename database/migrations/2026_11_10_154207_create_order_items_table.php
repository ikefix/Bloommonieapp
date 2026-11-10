
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            // Keep the product ID for reference.
            // Nullable so order history survives product deletion.
            $table->unsignedBigInteger('product_id')->nullable();

            // Snapshot of product information at purchase time
            $table->string('product_name');
            $table->text('product_description')->nullable();
            $table->string('product_image')->nullable();

            $table->decimal('unit_price', 15, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('total_price', 15, 2);

            $table->timestamps();

            $table->index('product_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_items');
    }
};
