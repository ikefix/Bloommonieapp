<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_entries', function (Blueprint $table) {

            $table->id();

            // LINK TO PRODUCTION BATCH
            $table->foreignId('production_id')
                ->constrained()
                ->cascadeOnDelete();

            // input | output | loss
            $table->enum('entry_type', ['input', 'output', 'loss']);

            // ITEM NAME (fallback if not using JSON structure)
            $table->string('item_name')->nullable();

            // QUANTITY (fallback mode)
            $table->decimal('quantity', 15, 2)->default(0);

            // UNIT (kg, bags, pcs, etc)
            $table->string('unit')->nullable();

            // PRICE PER UNIT (NEW — important for costing)
            $table->decimal('price', 15, 2)->default(0);

            // TOTAL (optional stored calc)
            $table->decimal('total', 15, 2)->nullable();

            // JSON STORAGE (THIS IS YOUR NEW POWER FEATURE)
            // stores full row group when using dynamic table UI
            $table->json('meta')->nullable();

            // OPTIONAL NOTES
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->unsignedBigInteger('owner_id')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_entries');
    }
};