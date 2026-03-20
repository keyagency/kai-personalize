<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('kai_personalize_visitor_attributes', function (Blueprint $table) {
            $table->index(
                ['visitor_id', 'attribute_key', 'session_id'],
                'kai_visitor_attr_session_idx'
            );
        });
    }

    public function down()
    {
        Schema::table('kai_personalize_visitor_attributes', function (Blueprint $table) {
            $table->dropIndex('kai_visitor_attr_session_idx');
        });
    }
};
