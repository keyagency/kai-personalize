<?php

namespace KeyAgency\KaiPersonalize\Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed the blacklist entries
        $this->call(BlacklistSeeder::class);
    }
}
