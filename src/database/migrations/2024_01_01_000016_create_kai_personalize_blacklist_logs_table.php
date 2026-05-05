<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kai_personalize_blacklist_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blacklist_id')->nullable()->constrained('kai_personalize_blacklists')->nullOnDelete();
            $table->string('bot_name')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('url')->nullable();
            $table->timestamps();

            $table->index('blacklist_id');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('kai_personalize_blacklist_logs');
    }
};
