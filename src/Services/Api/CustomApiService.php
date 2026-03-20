<?php

namespace KeyAgency\KaiPersonalize\Services\Api;

class CustomApiService extends BaseApiService
{
    public function fetch(array $params): array
    {
        $endpoint = $params['endpoint'] ?? '/';
        $method = $params['method'] ?? 'GET';
        unset($params['endpoint'], $params['method']);

        $cacheKey = 'custom_'.md5($endpoint.json_encode($params));

        // Try to get from cache
        if ($cached = $this->getCached($cacheKey, $params)) {
            return $cached;
        }

        // Make request
        $response = $this->makeRequest($endpoint, $params, $method);

        // Transform response
        $transformed = $this->transform($response);

        // Cache the result
        $this->setCached($cacheKey, $transformed, $this->connection->cache_duration);

        return $transformed;
    }

    public function transform(array $response): array
    {
        // For custom APIs, return response as-is
        // Users can define custom transformations in the connection config
        return $response;
    }
}
