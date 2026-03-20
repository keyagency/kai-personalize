<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kai_personalize_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_id')->constrained('kai_personalize_visitors')->onDelete('cascade');
            $table->foreignId('rule_id')->nullable()->constrained('kai_personalize_rules')->onDelete('set null');
            $table->json('matched_conditions')->nullable();
            $table->json('content_shown')->nullable();
            $table->timestamp('created_at')->index();

            $table->index(['visitor_id', 'created_at'], 'kai_log_visitor_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('kai_personalize_logs');
    }
};
