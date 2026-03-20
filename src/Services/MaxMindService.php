<?php

namespace KeyAgency\KaiPersonalize\Services;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MaxMindService
{
    protected ?Reader $cityReader = null;

    protected ?Reader $countryReader = null;

    protected ?Reader $asnReader = null;

    protected bool $initialized = false;

    protected array $errors = [];

    /**
     * Initialize the MaxMind database readers
     */
    protected function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->initialized = true;

        // City database (most detailed)
        $cityPath = $this->getDatabasePath('city');
        if ($cityPath && file_exists($cityPath)) {
            try {
                $this->cityReader = new Reader($cityPath);
            } catch (\Exception $e) {
                $this->errors[] = "Failed to load City database: {$e->getMessage()}";
                Log::warning("MaxMind City database error: {$e->getMessage()}");
            }
        }

        // Country database (fallback, smaller)
        $countryPath = $this->getDatabasePath('country');
        if ($countryPath && file_exists($countryPath)) {
            try {
                $this->countryReader = new Reader($countryPath);
            } catch (\Exception $e) {
                $this->errors[] = "Failed to load Country database: {$e->getMessage()}";
                Log::warning("MaxMind Country database error: {$e->getMessage()}");
            }
        }

        // ASN database (optional, for ISP info)
        $asnPath = $this->getDatabasePath('asn');
        if ($asnPath && file_exists($asnPath)) {
            try {
                $this->asnReader = new Reader($asnPath);
            } catch (\Exception $e) {
                // ASN is optional, don't log warning
            }
        }
    }

    /**
     * Get the database file path from config
     */
    protected function getDatabasePath(string $type): ?string
    {
        $configPath = config("kai-personalize.maxmind.database_{$type}");

        if ($configPath) {
            // If it's an absolute path, use it directly
            if (str_starts_with($configPath, '/')) {
                return $configPath;
            }

            // Otherwise, treat as relative to storage path
            return storage_path($configPath);
        }

        // Default paths
        $defaults = [
            'city' => storage_path('app/geoip/GeoLite2-City.mmdb'),
            'country' => storage_path('app/geoip/GeoLite2-Country.mmdb'),
            'asn' => storage_path('app/geoip/GeoLite2-ASN.mmdb'),
        ];

        return $defaults[$type] ?? null;
    }

    /**
     * Check if the service is available (has at least one database)
     */
    public function isAvailable(): bool
    {
        $this->initialize();

        return $this->cityReader !== null || $this->countryReader !== null;
    }

    /**
     * Get location data for an IP address
     */
    public function lookup(string $ip): ?array
    {
        // Skip private/local IPs
        if ($this->isPrivateIp($ip)) {
            return null;
        }

        // Check cache first
        $cacheKey = 'maxmind_'.md5($ip);
        $cacheDuration = config('kai-personalize.maxmind.cache_duration', 86400); // 24 hours default

        if ($cacheDuration > 0) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $this->initialize();

        $data = null;

        // Try City database first (most detailed)
        if ($this->cityReader) {
            $data = $this->lookupCity($ip);
        }

        // Fall back to Country database
        if ($data === null && $this->countryReader) {
            $data = $this->lookupCountry($ip);
        }

        // Add ASN data if available
        if ($data !== null && $this->asnReader) {
            $asnData = $this->lookupAsn($ip);
            if ($asnData) {
                $data = array_merge($data, $asnData);
            }
        }

        // Cache the result
        if ($cacheDuration > 0 && $data !== null) {
            Cache::put($cacheKey, $data, $cacheDuration);
        }

        return $data;
    }

    /**
     * Lookup using City database
     */
    protected function lookupCity(string $ip): ?array
    {
        try {
            $record = $this->cityReader->city($ip);

            return [
                'ip' => $ip,
                'country' => $record->country->name,
                'country_code' => $record->country->isoCode,
                'region' => $record->mostSpecificSubdivision->name,
                'region_code' => $record->mostSpecificSubdivision->isoCode,
                'city' => $record->city->name,
                'postal_code' => $record->postal->code,
                'latitude' => $record->location->latitude,
                'longitude' => $record->location->longitude,
                'timezone' => $record->location->timeZone,
                'continent' => $record->continent->name,
                'continent_code' => $record->continent->code,
                'is_eu' => $record->country->isInEuropeanUnion ?? false,
            ];
        } catch (AddressNotFoundException $e) {
            return null;
        } catch (\Exception $e) {
            Log::error("MaxMind City lookup error for {$ip}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Lookup using Country database
     */
    protected function lookupCountry(string $ip): ?array
    {
        try {
            $record = $this->countryReader->country($ip);

            return [
                'ip' => $ip,
                'country' => $record->country->name,
                'country_code' => $record->country->isoCode,
                'continent' => $record->continent->name,
                'continent_code' => $record->continent->code,
                'is_eu' => $record->country->isInEuropeanUnion ?? false,
                // These are not available in Country database
                'region' => null,
                'region_code' => null,
                'city' => null,
                'postal_code' => null,
                'latitude' => null,
                'longitude' => null,
                'timezone' => null,
            ];
        } catch (AddressNotFoundException $e) {
            return null;
        } catch (\Exception $e) {
            Log::error("MaxMind Country lookup error for {$ip}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Lookup ASN (ISP) information
     */
    protected function lookupAsn(string $ip): ?array
    {
        try {
            $record = $this->asnReader->asn($ip);

            return [
                'asn' => $record->autonomousSystemNumber,
                'isp' => $record->autonomousSystemOrganization,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if an IP is private/local
     */
    protected function isPrivateIp(string $ip): bool
    {
        return ! filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    /**
     * Get database metadata
     */
    public function getDatabaseInfo(): array
    {
        $this->initialize();

        $info = [];

        if ($this->cityReader) {
            $meta = $this->cityReader->metadata();
            $info['city'] = [
                'type' => $meta->databaseType,
                'build_date' => date('Y-m-d H:i:s', $meta->buildEpoch),
                'ip_version' => $meta->ipVersion,
            ];
        }

        if ($this->countryReader) {
            $meta = $this->countryReader->metadata();
            $info['country'] = [
                'type' => $meta->databaseType,
                'build_date' => date('Y-m-d H:i:s', $meta->buildEpoch),
                'ip_version' => $meta->ipVersion,
            ];
        }

        if ($this->asnReader) {
            $meta = $this->asnReader->metadata();
            $info['asn'] = [
                'type' => $meta->databaseType,
                'build_date' => date('Y-m-d H:i:s', $meta->buildEpoch),
                'ip_version' => $meta->ipVersion,
            ];
        }

        return $info;
    }

    /**
     * Get any initialization errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
