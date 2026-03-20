<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kai_personalize_visitors', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint_hash')->unique()->index();
            $table->string('session_id')->index()->nullable();
            $table->timestamp('first_visit_at');
            $table->timestamp('last_visit_at');
            $table->unsignedInteger('visit_count')->default(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('kai_personalize_visitors');
    }
};
