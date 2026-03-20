<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kai_personalize_api_cache', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connection_id')->constrained('kai_personalize_api_connections')->onDelete('cascade');
            $table->string('cache_key')->index();
            $table->json('request_params')->nullable();
            $table->json('response_data');
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->index(['connection_id', 'cache_key'], 'kai_api_cache_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('kai_personalize_api_cache');
    }
};
