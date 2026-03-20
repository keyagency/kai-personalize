<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kai_personalize_visitor_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_id')->constrained('kai_personalize_visitors')->onDelete('cascade');
            $table->string('session_id')->unique()->index();
            $table->text('ip_address')->nullable(); // Encrypted
            $table->text('user_agent')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('page_views')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('kai_personalize_visitor_sessions');
    }
};
