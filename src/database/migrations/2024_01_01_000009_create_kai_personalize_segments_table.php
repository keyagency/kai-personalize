<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kai_personalize_segments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('criteria'); // JSON array of conditions to match visitors
            $table->boolean('is_active')->default(true)->index();
            $table->integer('visitor_count')->default(0); // Cached count for performance
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('kai_personalize_segments');
    }
};
