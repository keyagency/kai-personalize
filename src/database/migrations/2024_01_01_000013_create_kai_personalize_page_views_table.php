<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kai_personalize_page_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_id')->constrained('kai_personalize_visitors')->onDelete('cascade');
            $table->foreignId('session_id')->constrained('kai_personalize_visitor_sessions')->onDelete('cascade');
            $table->string('url_path')->index();
            $table->string('entry_slug')->nullable()->index();
            $table->string('entry_title')->nullable();
            $table->string('collection_handle')->nullable()->index();
            $table->timestamp('viewed_at')->index();

            $table->index(['visitor_id', 'viewed_at']);
            $table->index(['session_id', 'viewed_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('kai_personalize_page_views');
    }
};
