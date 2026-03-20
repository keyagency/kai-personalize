<?php

namespace KeyAgency\KaiPersonalize\Tags;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Statamic\Tags\Tags;

class KaiApi extends Tags
{
    // Note: This class is instantiated internally by the Kai tag class

    /**
     * {{ kai:api url="https://api.example.com/data" method="GET" cache="600" }}
     */
    public function api(): array
    {
        $url = $this->params->get('url');
        $method = $this->params->get('method', 'GET');
        $cacheDuration = $this->params->get('cache', 300);

        if (! $url) {
            return [];
        }

        // Get params
        $params = [];
        foreach ($this->params->all() as $key => $value) {
            if (str_starts_with($key, 'params:')) {
                $paramKey = substr($key, 7);
                $params[$paramKey] = $value;
            }
        }

        // Generate cache key
        $cacheKey = 'kai_api_'.md5($url.json_encode($params));

        // Try to get from cache
        if ($cacheDuration > 0 && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            // Make request
            $response = match (strtoupper($method)) {
                'GET' => Http::timeout(30)->get($url, $params),
                'POST' => Http::timeout(30)->post($url, $params),
                'PUT' => Http::timeout(30)->put($url, $params),
                'DELETE' => Http::timeout(30)->delete($url, $params),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
            };

            $data = $response->json() ?? [];

            // Cache the result
            if ($cacheDuration > 0) {
                Cache::put($cacheKey, $data, $cacheDuration);
            }

            return $data;

        } catch (\Exception $e) {
            \Log::error('KaiApi tag error', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
