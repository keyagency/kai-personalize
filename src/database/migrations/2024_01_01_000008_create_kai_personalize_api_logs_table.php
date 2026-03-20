<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kai_personalize_api_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connection_id')->constrained('kai_personalize_api_connections')->onDelete('cascade');
            $table->text('request_url');
            $table->string('request_method')->default('GET');
            $table->json('request_params')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->json('response_data')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('duration_ms');
            $table->timestamp('created_at')->index();

            $table->index(['connection_id', 'created_at'], 'kai_api_log_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('kai_personalize_api_logs');
    }
};
