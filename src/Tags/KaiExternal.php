<?php

namespace KeyAgency\KaiPersonalize\Tags;

use KeyAgency\KaiPersonalize\Services\Api\ApiManager;
use Statamic\Tags\Tags;

class KaiExternal extends Tags
{
    // Note: This class is instantiated internally by the Kai tag class

    protected ApiManager $apiManager;

    public function __construct()
    {
        $this->apiManager = new ApiManager;
    }

    /**
     * {{ kai:external source="weather" }}
     */
    public function external(): array
    {
        $source = $this->params->get('source');

        if (! $source) {
            return [];
        }

        try {
            $data = match ($source) {
                'weather' => $this->fetchWeather(),
                'geolocation' => $this->fetchGeolocation(),
                'news' => $this->fetchNews(),
                'exchange' => $this->fetchExchange(),
                'custom' => $this->fetchCustom(),
                default => [],
            };

            return $data;
        } catch (\Exception $e) {
            \Log::error('KaiExternal tag error', [
                'source' => $source,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Fetch weather data
     */
    protected function fetchWeather(): array
    {
        $location = $this->params->get('location', 'auto');
        $units = $this->params->get('units', 'metric');

        return $this->apiManager->weather([
            'location' => $location,
            'units' => $units,
        ]);
    }

    /**
     * Fetch geolocation data
     */
    protected function fetchGeolocation(): array
    {
        $ip = $this->params->get('ip', request()->ip());

        return $this->apiManager->geolocation($ip);
    }

    /**
     * Fetch news data
     */
    protected function fetchNews(): array
    {
        // TODO: Implement news API integration
        return [];
    }

    /**
     * Fetch exchange rates
     */
    protected function fetchExchange(): array
    {
        // TODO: Implement exchange rate API integration
        return [];
    }

    /**
     * Fetch from custom API connection
     */
    protected function fetchCustom(): array
    {
        $connection = $this->params->get('connection');
        $endpoint = $this->params->get('endpoint', '/');

        if (! $connection) {
            return [];
        }

        // Get all params that start with "params:"
        $params = [];
        foreach ($this->params->all() as $key => $value) {
            if (str_starts_with($key, 'params:')) {
                $paramKey = substr($key, 7);
                $params[$paramKey] = $value;
            }
        }

        $params['endpoint'] = $endpoint;

        return $this->apiManager->custom($connection, $params);
    }
}
