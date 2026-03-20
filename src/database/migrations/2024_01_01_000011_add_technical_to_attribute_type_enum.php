<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Modify the enum to include 'technical'
        DB::statement("ALTER TABLE kai_personalize_visitor_attributes MODIFY COLUMN attribute_type ENUM('personal', 'computed', 'external', 'technical') DEFAULT 'personal'");
    }

    public function down()
    {
        // Remove 'technical' from enum (data may be lost)
        DB::statement("ALTER TABLE kai_personalize_visitor_attributes MODIFY COLUMN attribute_type ENUM('personal', 'computed', 'external') DEFAULT 'personal'");
    }
};
