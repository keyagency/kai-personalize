<?php

namespace KeyAgency\KaiPersonalize\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use KeyAgency\KaiPersonalize\Edition;
use KeyAgency\KaiPersonalize\Models\ApiConnection;
use Statamic\Http\Controllers\CP\CpController;

class ApiConnectionsController extends CpController
{
    public function index()
    {
        $connections = ApiConnection::orderBy('name')->get();

        $stats = [
            'total' => $connections->count(),
            'active' => $connections->where('is_active', true)->count(),
            'inactive' => $connections->where('is_active', false)->count(),
        ];

        return view('kai-personalize::api-connections.index', [
            'title' => __('kai-personalize::messages.api_connections.title'),
            'connections' => $connections,
            'stats' => $stats,
        ]);
    }

    public function create()
    {
        return view('kai-personalize::api-connections.create', [
            'title' => __('kai-personalize::messages.api_connections.create'),
            'connection' => null,
        ]);
    }

    public function store(Request $request)
    {
        // Check API connection limit for lite edition
        if (Edition::isLite() && $request->has('is_active')) {
            $maxConnections = Edition::getLimit('max_api_connections');
            $activeConnectionCount = ApiConnection::where('is_active', true)->count();

            if ($activeConnectionCount >= $maxConnections) {
                return back()
                    ->withInput()
                    ->with('error', __('kai-personalize::messages.api_connections.limit_reached', [
                        'max' => $maxConnections,
                    ]));
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:kai_personalize_api_connections,name',
            'provider' => 'required|string|max:255',
            'api_url' => 'required|url|max:500',
            'api_key' => 'nullable|string',
            'auth_type' => 'required|in:none,api_key,bearer,basic,oauth2,custom',
            'auth_config' => 'nullable|json',
            'headers' => 'nullable|json',
            'rate_limit' => 'nullable|integer|min:1',
            'timeout' => 'required|integer|min:1|max:120',
            'is_active' => 'boolean',
            'cache_duration' => 'required|integer|min:0',
        ]);

        // Decode JSON fields
        if ($validated['auth_config'] ?? null) {
            $validated['auth_config'] = json_decode($validated['auth_config'], true);
        }
        if ($validated['headers'] ?? null) {
            $validated['headers'] = json_decode($validated['headers'], true);
        }

        $validated['is_active'] = $request->has('is_active');

        $connection = ApiConnection::create($validated);

        return redirect()
            ->route('statamic.cp.kai-personalize.api-connections.show', $connection->id)
            ->with('success', __('kai-personalize::messages.api_connections.created'));
    }

    public function show($id)
    {
        $connection = ApiConnection::with(['cacheEntries', 'logs'])->findOrFail($id);

        $stats = [
            'total_requests' => $connection->logs()->count(),
            'requests_today' => $connection->logs()
                ->where('created_at', '>=', now()->startOfDay())
                ->count(),
            'cache_entries' => $connection->cacheEntries()->count(),
            'last_used' => $connection->last_used_at?->diffForHumans(),
            'success_rate' => $this->calculateSuccessRate($connection),
        ];

        $recentLogs = $connection->recentLogs(20);

        return view('kai-personalize::api-connections.show', [
            'title' => $connection->name,
            'connection' => $connection,
            'stats' => $stats,
            'recentLogs' => $recentLogs,
        ]);
    }

    public function edit($id)
    {
        $connection = ApiConnection::findOrFail($id);

        return view('kai-personalize::api-connections.edit', [
            'title' => __('kai-personalize::messages.api_connections.edit'),
            'connection' => $connection,
        ]);
    }

    public function update(Request $request, $id)
    {
        $connection = ApiConnection::findOrFail($id);

        // Check API connection limit for lite edition when activating a connection
        if (Edition::isLite() && $request->has('is_active') && !$connection->is_active) {
            $maxConnections = Edition::getLimit('max_api_connections');
            $activeConnectionCount = ApiConnection::where('is_active', true)->where('id', '!=', $id)->count();

            if ($activeConnectionCount >= $maxConnections) {
                return back()
                    ->withInput()
                    ->with('error', __('kai-personalize::messages.api_connections.limit_reached', [
                        'max' => $maxConnections,
                    ]));
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:kai_personalize_api_connections,name,'.$id,
            'provider' => 'required|string|max:255',
            'api_url' => 'required|url|max:500',
            'api_key' => 'nullable|string',
            'auth_type' => 'required|in:none,api_key,bearer,basic,oauth2,custom',
            'auth_config' => 'nullable|json',
            'headers' => 'nullable|json',
            'rate_limit' => 'nullable|integer|min:1',
            'timeout' => 'required|integer|min:1|max:120',
            'is_active' => 'boolean',
            'cache_duration' => 'required|integer|min:0',
        ]);

        // Decode JSON fields
        if ($validated['auth_config'] ?? null) {
            $validated['auth_config'] = json_decode($validated['auth_config'], true);
        }
        if ($validated['headers'] ?? null) {
            $validated['headers'] = json_decode($validated['headers'], true);
        }

        $validated['is_active'] = $request->has('is_active');

        // Only update API key if provided
        if (empty($validated['api_key'])) {
            unset($validated['api_key']);
        }

        $connection->update($validated);

        return redirect()
            ->route('statamic.cp.kai-personalize.api-connections.show', $connection->id)
            ->with('success', __('kai-personalize::messages.api_connections.updated'));
    }

    public function destroy($id)
    {
        $connection = ApiConnection::findOrFail($id);
        $connection->delete();

        return redirect()
            ->route('statamic.cp.kai-personalize.api-connections.index')
            ->with('success', __('kai-personalize::messages.api_connections.deleted'));
    }

    public function test($id)
    {
        $connection = ApiConnection::findOrFail($id);

        try {
            $request = Http::timeout($connection->timeout);

            // Add headers
            if ($connection->headers) {
                foreach ($connection->headers as $key => $value) {
                    $request->withHeaders([$key => $value]);
                }
            }

            // Add authentication
            switch ($connection->auth_type) {
                case 'api_key':
                    $request->withHeaders(['X-API-Key' => $connection->api_key]);
                    break;
                case 'bearer':
                    $request->withToken($connection->api_key);
                    break;
                case 'basic':
                    // Assuming api_key contains "username:password"
                    if ($connection->api_key && str_contains($connection->api_key, ':')) {
                        [$username, $password] = explode(':', $connection->api_key, 2);
                        $request->withBasicAuth($username, $password);
                    }
                    break;
            }

            // Make the request
            $response = $request->get($connection->api_url);

            if ($response->successful()) {
                $connection->markAsUsed();

                return back()->with('success', 'Connection test successful! Status: '.$response->status());
            } else {
                return back()->with('error', 'Connection test failed! Status: '.$response->status());
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Connection test failed: '.$e->getMessage());
        }
    }

    public function clearCache($id)
    {
        $connection = ApiConnection::findOrFail($id);
        $deleted = $connection->clearCache();

        return back()->with('success', "Cleared {$deleted} cache entries.");
    }

    protected function calculateSuccessRate(ApiConnection $connection): string
    {
        $total = $connection->logs()->count();

        if ($total === 0) {
            return 'N/A';
        }

        $successful = $connection->logs()
            ->where('response_status', '>=', 200)
            ->where('response_status', '<', 300)
            ->count();

        $rate = ($successful / $total) * 100;

        return number_format($rate, 1).'%';
    }
}
