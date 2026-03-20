<?php

namespace KeyAgency\KaiPersonalize\Services\Api;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use KeyAgency\KaiPersonalize\Models\ApiCache;
use KeyAgency\KaiPersonalize\Models\ApiConnection;
use KeyAgency\KaiPersonalize\Models\ApiLog;

abstract class BaseApiService
{
    protected ApiConnection $connection;

    protected float $requestStartTime;

    public function __construct(ApiConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Fetch data from API
     */
    abstract public function fetch(array $params): array;

    /**
     * Transform API response
     */
    abstract public function transform(array $response): array;

    /**
     * Get cached data
     */
    public function getCached(string $cacheKey, array $params): ?array
    {
        if (! config('kai-personalize.performance.cache_enabled')) {
            return null;
        }

        return ApiCache::getCached($this->connection->id, $cacheKey);
    }

    /**
     * Set cached data
     */
    public function setCached(string $cacheKey, array $data, int $ttl): void
    {
        if (! config('kai-personalize.performance.cache_enabled')) {
            return;
        }

        ApiCache::store(
            $this->connection->id,
            $cacheKey,
            [],
            $data,
            $ttl
        );
    }

    /**
     * Make HTTP request
     */
    public function makeRequest(string $endpoint, array $params = [], string $method = 'GET'): array
    {
        $this->requestStartTime = microtime(true);

        try {
            $url = rtrim($this->connection->api_url, '/').'/'.ltrim($endpoint, '/');

            $http = Http::timeout($this->connection->timeout)
                ->withHeaders($this->buildHeaders());

            // Add authentication
            $http = $this->addAuthentication($http);

            // Make request
            $response = match (strtoupper($method)) {
                'GET' => $http->get($url, $params),
                'POST' => $http->post($url, $params),
                'PUT' => $http->put($url, $params),
                'DELETE' => $http->delete($url, $params),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
            };

            $duration = intval(round((microtime(true) - $this->requestStartTime) * 1000));

            // Log successful request
            $this->logRequest(
                $url,
                $method,
                $params,
                $response->status(),
                $response->json(),
                null,
                $duration
            );

            // Mark connection as used
            $this->connection->markAsUsed();

            return $response->json() ?? [];

        } catch (\Exception $e) {
            $duration = intval(round((microtime(true) - $this->requestStartTime) * 1000));

            // Log failed request
            $this->logRequest(
                $url ?? $endpoint,
                $method,
                $params,
                null,
                null,
                $e->getMessage(),
                $duration
            );

            Log::error('API request failed', [
                'connection' => $this->connection->name,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Build request headers
     */
    protected function buildHeaders(): array
    {
        $headers = $this->connection->headers ?? [];

        // Add default headers
        $headers['Accept'] = $headers['Accept'] ?? 'application/json';
        $headers['User-Agent'] = $headers['User-Agent'] ?? 'KaiPersonalize/1.0';

        return $headers;
    }

    /**
     * Add authentication to request
     */
    protected function addAuthentication($http)
    {
        $authConfig = $this->connection->auth_config ?? [];

        switch ($this->connection->auth_type) {
            case 'api_key':
                $keyName = $authConfig['key_name'] ?? 'api_key';
                $keyLocation = $authConfig['key_location'] ?? 'query';

                if ($keyLocation === 'header') {
                    $http = $http->withHeader($keyName, $this->connection->api_key);
                }
                break;

            case 'bearer':
                $token = $authConfig['token'] ?? $this->connection->api_key;
                $http = $http->withToken($token);
                break;

            case 'basic':
                $username = $authConfig['username'] ?? '';
                $password = $authConfig['password'] ?? $this->connection->api_key;
                $http = $http->withBasicAuth($username, $password);
                break;

            case 'custom':
                foreach ($authConfig as $header => $value) {
                    $http = $http->withHeader($header, $value);
                }
                break;
        }

        return $http;
    }

    /**
     * Log API request
     */
    public function logRequest(
        string $url,
        string $method,
        ?array $params,
        ?int $status,
        ?array $response,
        ?string $error,
        int $duration
    ): void {
        ApiLog::createEntry(
            $this->connection->id,
            $url,
            $method,
            $params,
            $status,
            $response,
            $error,
            $duration
        );
    }

    /**
     * Handle rate limiting
     */
    public function handleRateLimit(): void
    {
        // TODO: Implement rate limiting logic
        // This would check the rate_limit field on the connection
        // and delay requests if necessary
    }
}
