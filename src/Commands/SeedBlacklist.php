<?php

namespace KeyAgency\KaiPersonalize\Commands;

use Illuminate\Console\Command;
use KeyAgency\KaiPersonalize\Database\Seeders\BlacklistSeeder;

class SeedBlacklist extends Command
{
    protected $signature = 'kai:seed-blacklist';

    protected $description = 'Seed the blacklist database with default entries';

    public function handle(): int
    {
        $this->info('Seeding blacklist entries...');

        $seeder = new BlacklistSeeder();
        $seeder->run();

        $this->info('Blacklist entries seeded successfully!');

        return self::SUCCESS;
    }
}
