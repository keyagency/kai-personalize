<?php

namespace KeyAgency\KaiPersonalize\Services\Api;

use KeyAgency\KaiPersonalize\Models\ApiConnection;

class ApiManager
{
    /**
     * Get a connection by name
     */
    public function connection(string $name): BaseApiService
    {
        $connection = ApiConnection::where('name', $name)
            ->active()
            ->firstOrFail();

        return $this->resolveService($connection);
    }

    /**
     * Get weather data
     */
    public function weather(array $params = []): array
    {
        $connection = $this->getConfiguredConnection('weather');
        $service = new WeatherApiService($connection);

        return $service->fetch($params);
    }

    /**
     * Get geolocation data
     */
    public function geolocation(?string $ip = null): array
    {
        $connection = $this->getConfiguredConnection('geolocation');
        $service = new GeolocationApiService($connection);

        return $service->fetch(['ip' => $ip]);
    }

    /**
     * Fetch from custom API connection
     */
    public function custom(string $connectionName, array $params = []): array
    {
        $connection = ApiConnection::where('name', $connectionName)
            ->active()
            ->firstOrFail();

        $service = $this->resolveService($connection);

        return $service->fetch($params);
    }

    /**
     * Resolve the appropriate service for a connection
     */
    protected function resolveService(ApiConnection $connection): BaseApiService
    {
        return match ($connection->provider) {
            'weather' => new WeatherApiService($connection),
            'geolocation' => new GeolocationApiService($connection),
            default => new CustomApiService($connection),
        };
    }

    /**
     * Get connection from config
     */
    protected function getConfiguredConnection(string $provider): ApiConnection
    {
        // Try to find existing connection
        $connection = ApiConnection::where('provider', $provider)
            ->active()
            ->first();

        if ($connection) {
            return $connection;
        }

        // Create from config
        $config = config("kai-personalize.apis.{$provider}");

        if (! $config) {
            throw new \Exception("No configuration found for provider: {$provider}");
        }

        return $this->createFromConfig($provider, $config);
    }

    /**
     * Create connection from config
     */
    protected function createFromConfig(string $provider, array $config): ApiConnection
    {
        $urls = [
            'weather' => match ($config['provider']) {
                'openweather' => 'https://api.openweathermap.org/data/2.5',
                'weatherapi' => 'https://api.weatherapi.com/v1',
                default => '',
            },
            'geolocation' => match ($config['provider']) {
                'ipapi' => 'http://ip-api.com',
                'ipstack' => 'http://api.ipstack.com',
                default => '',
            },
        ];

        return ApiConnection::create([
            'name' => "{$provider}_{$config['provider']}",
            'provider' => $provider,
            'api_url' => $urls[$provider] ?? '',
            'api_key' => $config['api_key'] ?? null,
            'auth_type' => 'api_key',
            'cache_duration' => $config['cache_duration'] ?? 300,
            'is_active' => true,
        ]);
    }
}
