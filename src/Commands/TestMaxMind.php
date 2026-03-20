<?php

declare(strict_types=1);

namespace KeyAgency\KaiPersonalize\Commands;

use Illuminate\Console\Command;
use KeyAgency\KaiPersonalize\Services\MaxMindService;

class TestMaxMind extends Command
{
    protected $signature = 'kai:maxmind:test
                          {ip? : IP address to lookup (default: 95.97.1.234)}
                          {--info : Show database information only}';

    protected $description = 'Test MaxMind GeoIP2 database lookup';

    public function handle(): int
    {
        $service = new MaxMindService;

        $this->info('MaxMind GeoIP2 Database Test');
        $this->line('=============================');
        $this->line('');

        // Check availability
        if (! $service->isAvailable()) {
            $this->error('MaxMind databases are not available!');
            $this->line('');
            $this->line('Please download the databases using:');
            $this->line('  php artisan kai:download-maxmind --license=YOUR_LICENSE_KEY');
            $this->line('');

            $errors = $service->getErrors();
            if (! empty($errors)) {
                $this->warn('Errors:');
                foreach ($errors as $error) {
                    $this->line('  - '.$error);
                }
            }

            return self::FAILURE;
        }

        // Show database info
        $this->showDatabaseInfo($service);

        if ($this->option('info')) {
            return self::SUCCESS;
        }

        $this->line('');

        // Lookup IP
        $ip = $this->argument('ip') ?? '95.97.1.234';

        $this->info("Looking up IP: {$ip}");
        $this->line('');

        $result = $service->lookup($ip);

        if ($result === null) {
            $this->warn('No data found for this IP address.');
            $this->line('');
            $this->line('This could mean:');
            $this->line('  - The IP is a private/local address');
            $this->line('  - The IP is not in the database');

            return self::FAILURE;
        }

        $this->displayResults($result);

        return self::SUCCESS;
    }

    protected function showDatabaseInfo(MaxMindService $service): void
    {
        $info = $service->getDatabaseInfo();

        $this->info('Available Databases:');

        $headers = ['Database', 'Type', 'Build Date', 'IP Version'];
        $rows = [];

        foreach ($info as $name => $meta) {
            $rows[] = [
                strtoupper($name),
                $meta['type'],
                $meta['build_date'],
                'IPv'.$meta['ip_version'],
            ];
        }

        $this->table($headers, $rows);
    }

    protected function displayResults(array $result): void
    {
        $this->info('Location Data:');

        $rows = [];

        // Group data for display
        $fields = [
            'ip' => 'IP Address',
            'country' => 'Country',
            'country_code' => 'Country Code',
            'region' => 'Region',
            'region_code' => 'Region Code',
            'city' => 'City',
            'postal_code' => 'Postal Code',
            'continent' => 'Continent',
            'continent_code' => 'Continent Code',
            'timezone' => 'Timezone',
            'latitude' => 'Latitude',
            'longitude' => 'Longitude',
            'is_eu' => 'EU Member',
            'asn' => 'ASN',
            'isp' => 'ISP',
        ];

        foreach ($fields as $key => $label) {
            if (isset($result[$key]) && $result[$key] !== null) {
                $value = $result[$key];

                // Format boolean values
                if (is_bool($value)) {
                    $value = $value ? 'Yes' : 'No';
                }

                $rows[] = [$label, $value];
            }
        }

        $this->table(['Field', 'Value'], $rows);

        // Show Google Maps link if coordinates available
        if (! empty($result['latitude']) && ! empty($result['longitude'])) {
            $this->line('');
            $this->line('Google Maps: https://www.google.com/maps?q='.$result['latitude'].','.$result['longitude']);
        }
    }
}
