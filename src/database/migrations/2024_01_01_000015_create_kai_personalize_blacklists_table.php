<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kai_personalize_blacklists', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['bot_name', 'user_agent']);
            $table->string('pattern');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('hit_count')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index('pattern');
        });
    }

    public function down()
    {
        Schema::dropIfExists('kai_personalize_blacklists');
    }
};
