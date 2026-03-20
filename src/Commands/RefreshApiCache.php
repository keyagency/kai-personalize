<?php

namespace KeyAgency\KaiPersonalize\Commands;

use Illuminate\Console\Command;
use KeyAgency\KaiPersonalize\Models\ApiCache;
use KeyAgency\KaiPersonalize\Models\ApiConnection;

class RefreshApiCache extends Command
{
    protected $signature = 'kai:refresh-cache
                          {connection? : Specific connection name to refresh}
                          {--all : Refresh all connections}';

    protected $description = 'Refresh API cache for connections';

    public function handle()
    {
        $connectionName = $this->argument('connection');
        $all = $this->option('all');

        if (! $connectionName && ! $all) {
            $this->error('Please specify a connection name or use --all flag');

            return 1;
        }

        if ($all) {
            $deleted = ApiCache::query()->delete();
            $this->info("Cleared cache for all connections ({$deleted} entries).");

            return 0;
        }

        try {
            $connection = ApiConnection::where('name', $connectionName)->firstOrFail();
            $deleted = $connection->clearCache();

            $this->info("Cleared cache for connection '{$connectionName}' ({$deleted} entries).");

            return 0;

        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());

            return 1;
        }
    }
}
