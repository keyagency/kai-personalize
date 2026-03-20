<?php

namespace KeyAgency\KaiPersonalize\Commands;

use Illuminate\Console\Command;
use KeyAgency\KaiPersonalize\Models\ApiConnection;
use KeyAgency\KaiPersonalize\Services\Api\ApiManager;

class TestApiConnection extends Command
{
    protected $signature = 'kai:test-api {connection : The name of the API connection to test}';

    protected $description = 'Test an API connection';

    public function handle()
    {
        $connectionName = $this->argument('connection');

        $this->info("Testing API connection: {$connectionName}");

        try {
            $connection = ApiConnection::where('name', $connectionName)->firstOrFail();

            $this->info('Connection found:');
            $this->line("  Provider: {$connection->provider}");
            $this->line("  URL: {$connection->api_url}");
            $this->line("  Auth Type: {$connection->auth_type}");
            $this->line('  Active: '.($connection->is_active ? 'Yes' : 'No'));

            if (! $connection->is_active) {
                $this->warn('Connection is not active!');

                return 1;
            }

            $this->info("\nMaking test request...");

            $apiManager = new ApiManager;
            $result = $apiManager->connection($connectionName)->fetch([]);

            $this->info('✓ Connection successful!');
            $this->line("\nResponse preview:");
            $this->line(json_encode($result, JSON_PRETTY_PRINT));

            return 0;

        } catch (\Exception $e) {
            $this->error('✗ Connection failed: '.$e->getMessage());

            return 1;
        }
    }
}
