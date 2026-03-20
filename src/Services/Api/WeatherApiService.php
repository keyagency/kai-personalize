<?php

namespace KeyAgency\KaiPersonalize\Services\Api;

class WeatherApiService extends BaseApiService
{
    public function fetch(array $params): array
    {
        $location = $params['location'] ?? 'auto';

        if ($location === 'auto') {
            $location = $this->detectLocation();
        }

        $cacheKey = "weather_{$location}";

        // Try to get from cache
        if ($cached = $this->getCached($cacheKey, $params)) {
            return $cached;
        }

        // Make API request based on provider
        $response = match (config('kai-personalize.apis.weather.provider')) {
            'openweather' => $this->fetchOpenWeather($location, $params),
            'weatherapi' => $this->fetchWeatherApi($location, $params),
            default => throw new \Exception('Unsupported weather provider'),
        };

        // Transform response
        $transformed = $this->transform($response);

        // Cache the result
        $this->setCached($cacheKey, $transformed, $this->connection->cache_duration);

        return $transformed;
    }

    protected function fetchOpenWeather(string $location, array $params): array
    {
        return $this->makeRequest('/weather', [
            'q' => $location,
            'units' => $params['units'] ?? 'metric',
            'appid' => $this->connection->api_key,
        ]);
    }

    protected function fetchWeatherApi(string $location, array $params): array
    {
        return $this->makeRequest('/current.json', [
            'q' => $location,
            'key' => $this->connection->api_key,
        ]);
    }

    public function transform(array $response): array
    {
        $provider = config('kai-personalize.apis.weather.provider');

        return match ($provider) {
            'openweather' => $this->transformOpenWeather($response),
            'weatherapi' => $this->transformWeatherApi($response),
            default => $response,
        };
    }

    protected function transformOpenWeather(array $response): array
    {
        return [
            'temperature' => $response['main']['temp'] ?? null,
            'feels_like' => $response['main']['feels_like'] ?? null,
            'condition' => $response['weather'][0]['main'] ?? null,
            'description' => $response['weather'][0]['description'] ?? null,
            'humidity' => $response['main']['humidity'] ?? null,
            'wind_speed' => $response['wind']['speed'] ?? null,
            'icon' => $response['weather'][0]['icon'] ?? null,
            'location' => $response['name'] ?? null,
        ];
    }

    protected function transformWeatherApi(array $response): array
    {
        return [
            'temperature' => $response['current']['temp_c'] ?? null,
            'feels_like' => $response['current']['feelslike_c'] ?? null,
            'condition' => $response['current']['condition']['text'] ?? null,
            'description' => $response['current']['condition']['text'] ?? null,
            'humidity' => $response['current']['humidity'] ?? null,
            'wind_speed' => $response['current']['wind_kph'] ?? null,
            'icon' => $response['current']['condition']['icon'] ?? null,
            'location' => $response['location']['name'] ?? null,
        ];
    }

    protected function detectLocation(): string
    {
        // Try to detect location from visitor's IP
        // This would integrate with geolocation service
        return 'Amsterdam'; // Default fallback
    }
}
