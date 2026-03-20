<div class="card p-4">
    <div class="mb-4">
        <label class="font-bold" for="name">Rule Name *</label>
        <input
            type="text"
            name="name"
            id="name"
            value="{{ old('name', $rule->name ?? '') }}"
            class="input-text"
            required
        />
        @error('name')
            <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4">
        <label class="font-bold" for="description">Description</label>
        <textarea
            name="description"
            id="description"
            rows="3"
            class="input-text"
        >{{ old('description', $rule->description ?? '') }}</textarea>
        @error('description')
            <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4">
        <label class="font-bold" for="priority">Priority *</label>
        <input
            type="number"
            name="priority"
            id="priority"
            value="{{ old('priority', $rule->priority ?? 0) }}"
            min="0"
            class="input-text"
            required
        />
        <p class="text-xs text-gray-600 mt-1">Higher priority rules are evaluated first</p>
        @error('priority')
            <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4">
        <label class="flex items-center">
            <input
                type="checkbox"
                name="is_active"
                value="1"
                {{ old('is_active', $rule->is_active ?? true) ? 'checked' : '' }}
                class="mr-2"
            />
            <span class="font-bold">Active</span>
        </label>
        <p class="text-xs text-gray-600 mt-1">Only active rules are evaluated</p>
    </div>

    <div class="mb-4">
        <label class="font-bold" for="conditions">Conditions (JSON) *</label>
        <textarea
            name="conditions"
            id="conditions"
            rows="10"
            class="input-text font-mono text-sm"
            required
        >{{ old('conditions', json_encode($rule->conditions ?? [[
    'attribute' => 'country',
    'operator' => 'equals',
    'value' => 'US'
]], JSON_PRETTY_PRINT)) }}</textarea>
        <p class="text-xs text-gray-600 mt-1">
            Define conditions as JSON array. Example:
        </p>
        <pre class="text-xs bg-gray-100 p-2 rounded mt-2 overflow-x-auto">
[
  {
    "attribute": "country",
    "operator": "equals",
    "value": "US"
  },
  {
    "attribute": "device_type",
    "operator": "equals",
    "value": "mobile"
  }
]</pre>
        <p class="text-xs text-gray-600 mt-2">
            <strong>Available operators:</strong> equals, not_equals, contains, not_contains, greater_than, less_than, in, not_in
        </p>
        @error('conditions')
            <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div class="flex justify-end gap-2">
        <a href="{{ cp_route('kai-personalize.rules.index') }}" class="btn">
            Cancel
        </a>
        <button type="submit" class="btn-primary">
            {{ $rule ? 'Update Rule' : 'Create Rule' }}
        </button>
    </div>
</div>
