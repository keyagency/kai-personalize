<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kai_personalize_segment_visitor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('segment_id')->constrained('kai_personalize_segments')->onDelete('cascade');
            $table->foreignId('visitor_id')->constrained('kai_personalize_visitors')->onDelete('cascade');
            $table->timestamp('assigned_at')->useCurrent();

            // Ensure a visitor can only be in a segment once
            $table->unique(['segment_id', 'visitor_id']);

            // Indexes for performance
            $table->index('segment_id');
            $table->index('visitor_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('kai_personalize_segment_visitor');
    }
};
