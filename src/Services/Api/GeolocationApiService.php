<?php

namespace KeyAgency\KaiPersonalize\Services\Api;

class GeolocationApiService extends BaseApiService
{
    public function fetch(array $params): array
    {
        $ip = $params['ip'] ?? request()->ip();

        $cacheKey = "geolocation_{$ip}";

        // Try to get from cache
        if ($cached = $this->getCached($cacheKey, $params)) {
            return $cached;
        }

        // Make API request based on provider
        $response = match (config('kai-personalize.apis.geolocation.provider')) {
            'ipapi' => $this->fetchIpApi($ip),
            'maxmind' => $this->fetchMaxMind($ip),
            'ip2location' => $this->fetchIp2Location($ip),
            'ipstack' => $this->fetchIpStack($ip),
            'ipgeolocation' => $this->fetchIpGeolocation($ip),
            default => throw new \Exception('Unsupported geolocation provider'),
        };

        // Transform response
        $transformed = $this->transform($response);

        // Cache the result
        $this->setCached($cacheKey, $transformed, $this->connection->cache_duration);

        return $transformed;
    }

    protected function fetchIpApi(string $ip): array
    {
        return $this->makeRequest("/{$ip}/json");
    }

    protected function fetchMaxMind(string $ip): array
    {
        return $this->makeRequest("/geoip/v2.1/city/{$ip}", [
            'account_id' => $this->connection->auth_config['account_id'] ?? '',
        ]);
    }

    protected function fetchIp2Location(string $ip): array
    {
        return $this->makeRequest('/', [
            'ip' => $ip,
            'key' => $this->connection->api_key,
            'package' => 'WS10',
        ]);
    }

    protected function fetchIpStack(string $ip): array
    {
        return $this->makeRequest("/{$ip}", [
            'access_key' => $this->connection->api_key,
        ]);
    }

    protected function fetchIpGeolocation(string $ip): array
    {
        return $this->makeRequest('/ipgeo', [
            'apiKey' => $this->connection->api_key,
            'ip' => $ip,
        ]);
    }

    public function transform(array $response): array
    {
        $provider = config('kai-personalize.apis.geolocation.provider');

        return match ($provider) {
            'ipapi' => $this->transformIpApi($response),
            'maxmind' => $this->transformMaxMind($response),
            'ip2location' => $this->transformIp2Location($response),
            'ipstack' => $this->transformIpStack($response),
            'ipgeolocation' => $this->transformIpGeolocation($response),
            default => $response,
        };
    }

    protected function transformIpApi(array $response): array
    {
        return [
            'ip' => $response['query'] ?? null,
            'country' => $response['country'] ?? null,
            'country_code' => $response['countryCode'] ?? null,
            'region' => $response['regionName'] ?? null,
            'region_code' => $response['region'] ?? null,
            'city' => $response['city'] ?? null,
            'zip' => $response['zip'] ?? null,
            'latitude' => $response['lat'] ?? null,
            'longitude' => $response['lon'] ?? null,
            'timezone' => $response['timezone'] ?? null,
            'isp' => $response['isp'] ?? null,
        ];
    }

    protected function transformMaxMind(array $response): array
    {
        return [
            'ip' => $response['traits']['ip_address'] ?? null,
            'country' => $response['country']['names']['en'] ?? null,
            'country_code' => $response['country']['iso_code'] ?? null,
            'region' => $response['subdivisions'][0]['names']['en'] ?? null,
            'region_code' => $response['subdivisions'][0]['iso_code'] ?? null,
            'city' => $response['city']['names']['en'] ?? null,
            'zip' => $response['postal']['code'] ?? null,
            'latitude' => $response['location']['latitude'] ?? null,
            'longitude' => $response['location']['longitude'] ?? null,
            'timezone' => $response['location']['time_zone'] ?? null,
        ];
    }

    protected function transformIp2Location(array $response): array
    {
        return [
            'ip' => $response['ip'] ?? null,
            'country' => $response['country_name'] ?? null,
            'country_code' => $response['country_code'] ?? null,
            'region' => $response['region_name'] ?? null,
            'city' => $response['city_name'] ?? null,
            'zip' => $response['zip_code'] ?? null,
            'latitude' => $response['latitude'] ?? null,
            'longitude' => $response['longitude'] ?? null,
            'timezone' => $response['time_zone'] ?? null,
        ];
    }

    protected function transformIpStack(array $response): array
    {
        return [
            'ip' => $response['ip'] ?? null,
            'country' => $response['country_name'] ?? null,
            'country_code' => $response['country_code'] ?? null,
            'region' => $response['region_name'] ?? null,
            'region_code' => $response['region_code'] ?? null,
            'city' => $response['city'] ?? null,
            'zip' => $response['zip'] ?? null,
            'latitude' => $response['latitude'] ?? null,
            'longitude' => $response['longitude'] ?? null,
            'timezone' => $response['time_zone']['id'] ?? null,
        ];
    }

    protected function transformIpGeolocation(array $response): array
    {
        return [
            'ip' => $response['ip'] ?? null,
            'country' => $response['country_name'] ?? null,
            'country_code' => $response['country_code2'] ?? null,
            'region' => $response['state_prov'] ?? null,
            'region_code' => $response['state_code'] ?? null,
            'city' => $response['city'] ?? null,
            'zip' => $response['zipcode'] ?? null,
            'latitude' => $response['latitude'] ?? null,
            'longitude' => $response['longitude'] ?? null,
            'timezone' => $response['time_zone']['name'] ?? null,
            'isp' => $response['isp'] ?? null,
            'organization' => $response['organization'] ?? null,
            'continent' => $response['continent_name'] ?? null,
            'currency' => $response['currency']['code'] ?? null,
            'languages' => $response['languages'] ?? null,
        ];
    }
}
