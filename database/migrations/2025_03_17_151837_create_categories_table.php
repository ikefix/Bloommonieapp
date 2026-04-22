<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            // 🔐 SaaS OWNERSHIP (IMPORTANT)
            $table->unsignedBigInteger('owner_id');

            $table->string('name');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('categories');
    }
};