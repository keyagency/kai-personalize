<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kai_personalize_visitor_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_id')->constrained('kai_personalize_visitors')->onDelete('cascade');
            $table->foreignId('session_id')->nullable()->constrained('kai_personalize_visitor_sessions')->onDelete('set null');
            $table->string('attribute_key')->index();
            $table->json('attribute_value');
            $table->enum('attribute_type', ['personal', 'computed', 'external', 'technical'])->default('personal');
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->index(['visitor_id', 'attribute_key'], 'kai_visitor_attr_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('kai_personalize_visitor_attributes');
    }
};
