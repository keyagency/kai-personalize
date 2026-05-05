<div class="card p-4">
    <h3 class="font-bold mb-4">{{ $blacklist ? 'Edit' : 'Add' }} Blacklist Entry</h3>

    <div class="grid grid-cols-2 gap-4 mb-4">
        <div>
            <label class="font-bold" for="type">{{ __('kai-personalize::messages.blacklists.type') }} *</label>
            <select name="type" id="type" class="input-text block w-full" required>
                <option value="">{{ __('messages.select_type') }}</option>
                <option value="bot_name" {{ old('type', $blacklist->type ?? '') == 'bot_name' ? 'selected' : '' }}>Bot Name</option>
                <option value="user_agent" {{ old('type', $blacklist->type ?? '') == 'user_agent' ? 'selected' : '' }}>User Agent Pattern</option>
            </select>
            @error('type')
                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label class="font-bold" for="pattern">{{ __('kai-personalize::messages.blacklists.pattern') }} *</label>
            <input type="text" name="pattern" id="pattern" value="{{ old('pattern', $blacklist->pattern ?? '') }}" class="input-text block w-full" required />
            <p class="text-xs text-gray-600 mt-1">Bot name (e.g., "semrush") or pattern (e.g., "scrapy")</p>
            @error('pattern')
                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="mb-4">
        <label class="font-bold" for="description">{{ __('kai-personalize::messages.blacklists.description') }}</label>
        <input type="text" name="description" id="description" value="{{ old('description', $blacklist->description ?? '') }}" class="input-text block w-full" />
        <p class="text-xs text-gray-600 mt-1">Optional description for this entry</p>
        @error('description')
            <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4">
        <label class="flex items-center">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $blacklist->is_active ?? false) ? 'checked' : '' }} class="mr-2" />
            <span class="font-bold">{{ __('kai-personalize::messages.blacklists.is_active') }}</span>
        </label>
        <p class="text-xs text-gray-600 mt-1">Only active entries will block tracking</p>
    </div>
</div>

<div class="flex justify-end gap-2 mt-4">
    <a href="{{ cp_route('kai-personalize.blacklists.index') }}" class="btn">{{ __('messages.cancel') }}</a>
    <button type="submit" class="btn btn-primary">
        {{ $blacklist ? __('messages.update') : __('messages.create') }}
    </button>
</div>
