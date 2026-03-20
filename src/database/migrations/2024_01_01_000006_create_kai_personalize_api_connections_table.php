<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kai_personalize_api_connections', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('provider'); // e.g., 'weather', 'news', 'custom'
            $table->text('api_url');
            $table->text('api_key')->nullable(); // Encrypted
            $table->enum('auth_type', ['none', 'api_key', 'bearer', 'basic', 'oauth2', 'custom'])->default('none');
            $table->json('auth_config')->nullable(); // Additional auth parameters
            $table->json('headers')->nullable(); // Custom headers
            $table->unsignedInteger('rate_limit')->nullable(); // Requests per minute
            $table->unsignedInteger('timeout')->default(30); // Seconds
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('cache_duration')->default(300); // Seconds
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('kai_personalize_api_connections');
    }
};
