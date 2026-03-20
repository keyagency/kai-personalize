<div class="card p-4">
    <h3 class="font-bold mb-4">Connection Details</h3>

    <div class="grid grid-cols-2 gap-4 mb-4">
        <div>
            <label class="font-bold" for="name">Connection Name *</label>
            <input type="text" name="name" id="name" value="{{ old('name', $connection->name ?? '') }}" class="input-text" required />
            @error('name')
                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label class="font-bold" for="provider">Provider *</label>
            <select name="provider" id="provider" class="input-text" required>
                <option value="">Select Provider</option>
                <option value="weather" {{ old('provider', $connection->provider ?? '') == 'weather' ? 'selected' : '' }}>Weather API</option>
                <option value="geolocation" {{ old('provider', $connection->provider ?? '') == 'geolocation' ? 'selected' : '' }}>Geolocation</option>
                <option value="news" {{ old('provider', $connection->provider ?? '') == 'news' ? 'selected' : '' }}>News API</option>
                <option value="exchange" {{ old('provider', $connection->provider ?? '') == 'exchange' ? 'selected' : '' }}>Exchange Rates</option>
                <option value="custom" {{ old('provider', $connection->provider ?? '') == 'custom' ? 'selected' : '' }}>Custom API</option>
            </select>
            @error('provider')
                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="mb-4">
        <label class="font-bold" for="api_url">API URL *</label>
        <input type="url" name="api_url" id="api_url" value="{{ old('api_url', $connection->api_url ?? '') }}" class="input-text" required />
        <p class="text-xs text-gray-600 mt-1">Full URL including protocol (e.g., https://api.example.com)</p>
        @error('api_url')
            <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div class="grid grid-cols-2 gap-4 mb-4">
        <div>
            <label class="font-bold" for="auth_type">Authentication Type *</label>
            <select name="auth_type" id="auth_type" class="input-text" required>
                <option value="none" {{ old('auth_type', $connection->auth_type ?? 'none') == 'none' ? 'selected' : '' }}>None</option>
                <option value="api_key" {{ old('auth_type', $connection->auth_type ?? '') == 'api_key' ? 'selected' : '' }}>API Key</option>
                <option value="bearer" {{ old('auth_type', $connection->auth_type ?? '') == 'bearer' ? 'selected' : '' }}>Bearer Token</option>
                <option value="basic" {{ old('auth_type', $connection->auth_type ?? '') == 'basic' ? 'selected' : '' }}>Basic Auth</option>
                <option value="oauth2" {{ old('auth_type', $connection->auth_type ?? '') == 'oauth2' ? 'selected' : '' }}>OAuth2</option>
                <option value="custom" {{ old('auth_type', $connection->auth_type ?? '') == 'custom' ? 'selected' : '' }}>Custom</option>
            </select>
            @error('auth_type')
                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label class="font-bold" for="api_key">API Key / Token</label>
            <input type="password" name="api_key" id="api_key" value="{{ old('api_key', $connection ? '' : '') }}" class="input-text" />
            @if($connection)
                <p class="text-xs text-gray-600 mt-1">Leave empty to keep existing key</p>
            @endif
            @error('api_key')
                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="grid grid-cols-3 gap-4 mb-4">
        <div>
            <label class="font-bold" for="timeout">Timeout (seconds) *</label>
            <input type="number" name="timeout" id="timeout" value="{{ old('timeout', $connection->timeout ?? 30) }}" min="1" max="120" class="input-text" required />
            @error('timeout')
                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label class="font-bold" for="cache_duration">Cache Duration (seconds) *</label>
            <input type="number" name="cache_duration" id="cache_duration" value="{{ old('cache_duration', $connection->cache_duration ?? 300) }}" min="0" class="input-text" required />
            <p class="text-xs text-gray-600 mt-1">0 = no caching</p>
            @error('cache_duration')
                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label class="font-bold" for="rate_limit">Rate Limit (req/min)</label>
            <input type="number" name="rate_limit" id="rate_limit" value="{{ old('rate_limit', $connection->rate_limit ?? '') }}" min="1" class="input-text" />
            <p class="text-xs text-gray-600 mt-1">Optional</p>
            @error('rate_limit')
                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="mb-4">
        <label class="flex items-center">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $connection->is_active ?? true) ? 'checked' : '' }} class="mr-2" />
            <span class="font-bold">Active</span>
        </label>
        <p class="text-xs text-gray-600 mt-1">Only active connections can be used</p>
    </div>
</div>

<div class="card p-4 mt-4">
    <h3 class="font-bold mb-4">Advanced Configuration (Optional)</h3>

    <div class="mb-4">
        <label class="font-bold" for="headers">Custom Headers (JSON)</label>
        <textarea name="headers" id="headers" rows="4" class="input-text font-mono text-sm">{{ old('headers', $connection && $connection->headers ? json_encode($connection->headers, JSON_PRETTY_PRINT) : '') }}</textarea>
        <p class="text-xs text-gray-600 mt-1">Example: {"Content-Type": "application/json", "X-Custom-Header": "value"}</p>
        @error('headers')
            <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4">
        <label class="font-bold" for="auth_config">Auth Configuration (JSON)</label>
        <textarea name="auth_config" id="auth_config" rows="4" class="input-text font-mono text-sm">{{ old('auth_config', $connection && $connection->auth_config ? json_encode($connection->auth_config, JSON_PRETTY_PRINT) : '') }}</textarea>
        <p class="text-xs text-gray-600 mt-1">Additional authentication parameters specific to auth type</p>
        @error('auth_config')
            <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="flex justify-end gap-2 mt-4">
    <a href="{{ cp_route('kai-personalize.api-connections.index') }}" class="btn">Cancel</a>
    <button type="submit" class="btn-primary">
        {{ $connection ? 'Update Connection' : 'Create Connection' }}
    </button>
</div>
