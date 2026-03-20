<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kai_personalize_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_id')->constrained('kai_personalize_visitors')->onDelete('cascade');
            $table->foreignId('session_id')->constrained('kai_personalize_visitor_sessions')->onDelete('cascade');
            $table->string('event_type')->index(); // scroll, click, visibility, form, video, etc.
            $table->json('event_data');
            $table->timestamp('created_at')->index();

            $table->index(['visitor_id', 'event_type']);
            $table->index(['session_id', 'created_at']);
            $table->index(['event_type', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('kai_personalize_events');
    }
};
