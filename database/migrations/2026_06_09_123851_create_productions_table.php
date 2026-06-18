<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productions', function (Blueprint $table) {

            $table->id();

            $table->foreignId('shop_id')
                ->constrained('shops')
                ->cascadeOnDelete();

            $table->string('batch_no');

            $table->foreignId('production_type_id')
                ->constrained('production_types')
                ->cascadeOnDelete();

            $table->string('title');

            $table->text('description')->nullable();

            $table->date('start_date')->nullable();

            $table->date('end_date')->nullable();

            $table->enum('status', [
                'planned',
                'in_progress',
                'completed',
                'cancelled'
            ])->default('planned');

            $table->unsignedBigInteger('created_by')->nullable();

            $table->unsignedBigInteger('owner_id')->nullable();

            $table->timestamps();
        });
        
    }

    public function down(): void
    {
        Schema::dropIfExists('productions');
    }
};