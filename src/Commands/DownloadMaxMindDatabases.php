<?php

declare(strict_types=1);

namespace KeyAgency\KaiPersonalize\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use PharData;
use Throwable;

class DownloadMaxMindDatabases extends Command
{
    protected $signature = 'kai:maxmind:download
                          {--license= : MaxMind license key (or use MAXMIND_LICENSE_KEY env variable)}
                          {--database= : Specific database to download (city, country, asn) or leave empty for all}';

    protected $description = 'Download MaxMind GeoLite2 databases for IP geolocation';

    /**
     * Available MaxMind GeoLite2 databases.
     */
    protected array $databases = [
        'city' => 'GeoLite2-City',
        'country' => 'GeoLite2-Country',
        'asn' => 'GeoLite2-ASN',
    ];

    /**
     * MaxMind download base URL.
     */
    protected string $downloadBaseUrl = 'https://download.maxmind.com/app/geoip_download';

    public function handle(): int
    {
        $licenseKey = $this->getLicenseKey();

        if (empty($licenseKey)) {
            $this->error('MaxMind license key is required.');
            $this->line('');
            $this->line('You can provide it via:');
            $this->line('  1. The --license option: php artisan kai-personalize:download-maxmind --license=YOUR_KEY');
            $this->line('  2. Environment variable: MAXMIND_LICENSE_KEY=YOUR_KEY');
            $this->line('');
            $this->line('Get a free license key at: https://www.maxmind.com/en/geolite2/signup');

            return self::FAILURE;
        }

        $databasesToDownload = $this->getDatabasesToDownload();

        if (empty($databasesToDownload)) {
            $this->error('Invalid database specified. Available options: city, country, asn');

            return self::FAILURE;
        }

        $storagePath = $this->ensureStorageDirectory();

        $this->info('MaxMind GeoLite2 Database Downloader');
        $this->line('=====================================');
        $this->line('');
        $this->line('Storage path: '.$storagePath);
        $this->line('Databases to download: '.implode(', ', array_keys($databasesToDownload)));
        $this->line('');

        $successCount = 0;
        $failCount = 0;

        foreach ($databasesToDownload as $key => $editionId) {
            $this->line('');
            $this->info("Downloading {$editionId}...");

            try {
                $this->downloadDatabase($editionId, $licenseKey, $storagePath);
                $successCount++;
                $this->info("Successfully downloaded {$editionId}.mmdb");
            } catch (Throwable $e) {
                $failCount++;
                $this->error("Failed to download {$editionId}: ".$e->getMessage());
            }
        }

        $this->line('');
        $this->line('=====================================');

        if ($failCount === 0) {
            $this->info("All {$successCount} database(s) downloaded successfully!");

            return self::SUCCESS;
        }

        if ($successCount > 0) {
            $this->warn("{$successCount} database(s) downloaded, {$failCount} failed.");

            return self::FAILURE;
        }

        $this->error("All {$failCount} database(s) failed to download.");

        return self::FAILURE;
    }

    /**
     * Get the MaxMind license key from option or environment.
     */
    protected function getLicenseKey(): ?string
    {
        $licenseKey = $this->option('license');

        if (empty($licenseKey)) {
            $licenseKey = config('kai-personalize.maxmind.license_key')
                ?? env('MAXMIND_LICENSE_KEY');
        }

        return $licenseKey ?: null;
    }

    /**
     * Get the databases to download based on the --database option.
     */
    protected function getDatabasesToDownload(): array
    {
        $database = $this->option('database');

        if (empty($database)) {
            return $this->databases;
        }

        $database = strtolower($database);

        if (! isset($this->databases[$database])) {
            return [];
        }

        return [$database => $this->databases[$database]];
    }

    /**
     * Ensure the storage directory exists.
     */
    protected function ensureStorageDirectory(): string
    {
        $path = storage_path('app/geoip');

        if (! File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
            $this->line("Created directory: {$path}");
        }

        return $path;
    }

    /**
     * Download and extract a MaxMind database.
     */
    protected function downloadDatabase(string $editionId, string $licenseKey, string $storagePath): void
    {
        $url = $this->buildDownloadUrl($editionId, $licenseKey);

        $this->line('  Fetching from MaxMind...');

        $response = Http::timeout(300)
            ->withOptions([
                'sink' => $tempTarGz = $storagePath.'/'.$editionId.'.tar.gz',
            ])
            ->get($url);

        if (! $response->successful()) {
            $statusCode = $response->status();
            $body = $response->body();

            // Clean up the temp file
            if (File::exists($tempTarGz)) {
                File::delete($tempTarGz);
            }

            if ($statusCode === 401) {
                throw new \RuntimeException('Invalid license key or unauthorized access.');
            }

            if ($statusCode === 404) {
                throw new \RuntimeException('Database not found. Ensure your account has access to GeoLite2 databases.');
            }

            throw new \RuntimeException("HTTP error {$statusCode}: {$body}");
        }

        $this->line('  Download complete. Extracting...');

        try {
            $this->extractDatabase($tempTarGz, $editionId, $storagePath);
        } finally {
            // Always clean up the tar.gz file
            if (File::exists($tempTarGz)) {
                File::delete($tempTarGz);
            }
        }
    }

    /**
     * Build the MaxMind download URL.
     */
    protected function buildDownloadUrl(string $editionId, string $licenseKey): string
    {
        return $this->downloadBaseUrl.'?'.http_build_query([
            'edition_id' => $editionId,
            'license_key' => $licenseKey,
            'suffix' => 'tar.gz',
        ]);
    }

    /**
     * Extract the .mmdb file from the tar.gz archive.
     */
    protected function extractDatabase(string $tarGzPath, string $editionId, string $storagePath): void
    {
        if (! File::exists($tarGzPath)) {
            throw new \RuntimeException('Downloaded file not found.');
        }

        $tempDir = $storagePath.'/temp_'.uniqid();
        File::makeDirectory($tempDir, 0755, true);

        try {
            // Use shell tar command for reliable extraction
            $command = sprintf(
                'tar -xzf %s -C %s 2>&1',
                escapeshellarg($tarGzPath),
                escapeshellarg($tempDir)
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                // Fallback to PharData if tar command fails
                $this->extractWithPharData($tarGzPath, $tempDir, $storagePath);

                return;
            }

            // Find the .mmdb file in the extracted contents
            $mmdbFile = $this->findMmdbFile($tempDir);

            if (! $mmdbFile) {
                throw new \RuntimeException('Could not find .mmdb file in archive.');
            }

            // Move the .mmdb file to the final location
            $finalPath = $storagePath.'/'.$editionId.'.mmdb';

            // Remove existing file if present
            if (File::exists($finalPath)) {
                File::delete($finalPath);
            }

            File::move($mmdbFile, $finalPath);

            $this->line('  Extracted to: '.$finalPath);

        } finally {
            // Clean up temp directory
            if (File::isDirectory($tempDir)) {
                File::deleteDirectory($tempDir);
            }
        }
    }

    /**
     * Fallback extraction using PharData.
     */
    protected function extractWithPharData(string $tarGzPath, string $tempDir, string $storagePath): void
    {
        $this->line('  Using PharData fallback...');

        $phar = new PharData($tarGzPath);
        $phar->decompress();

        $decompressedTarPath = str_replace('.tar.gz', '.tar', $tarGzPath);

        if (! File::exists($decompressedTarPath)) {
            throw new \RuntimeException('Failed to decompress tar.gz file.');
        }

        try {
            $tarPhar = new PharData($decompressedTarPath);
            $tarPhar->extractTo($tempDir);

            $mmdbFile = $this->findMmdbFile($tempDir);

            if (! $mmdbFile) {
                throw new \RuntimeException('Could not find .mmdb file in archive.');
            }

            $editionId = pathinfo($mmdbFile, PATHINFO_FILENAME);
            $finalPath = $storagePath.'/'.$editionId.'.mmdb';

            if (File::exists($finalPath)) {
                File::delete($finalPath);
            }

            File::move($mmdbFile, $finalPath);

            $this->line('  Extracted to: '.$finalPath);

        } finally {
            if (File::exists($decompressedTarPath)) {
                File::delete($decompressedTarPath);
            }
        }
    }

    /**
     * Recursively find the .mmdb file in the extracted directory.
     */
    protected function findMmdbFile(string $directory): ?string
    {
        $files = File::allFiles($directory);

        foreach ($files as $file) {
            if ($file->getExtension() === 'mmdb') {
                return $file->getPathname();
            }
        }

        return null;
    }
}
