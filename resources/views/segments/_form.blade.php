<div class="card p-4">
    <div class="mb-4">
        <label class="font-bold" for="name">Segment Name *</label>
        <input
            type="text"
            name="name"
            id="name"
            value="{{ old('name', $segment->name ?? '') }}"
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
        >{{ old('description', $segment->description ?? '') }}</textarea>
        @error('description')
            <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4">
        <label class="flex items-center">
            <input
                type="checkbox"
                name="is_active"
                value="1"
                {{ old('is_active', $segment->is_active ?? true) ? 'checked' : '' }}
                class="mr-2"
            />
            <span class="font-bold">Active</span>
        </label>
        <p class="text-xs text-gray-600 mt-1">Only active segments will accept new visitors</p>
    </div>

    <div class="mb-4">
        <label class="font-bold" for="criteria">Criteria (JSON) *</label>
        <textarea
            name="criteria"
            id="criteria"
            rows="10"
            class="input-text font-mono text-sm"
            required
        >{{ old('criteria', json_encode($segment->criteria ?? [[
    'attribute' => 'country',
    'operator' => 'equals',
    'value' => 'US'
]], JSON_PRETTY_PRINT)) }}</textarea>
        <p class="text-xs text-gray-600 mt-1">
            Define segment criteria as JSON array. Visitors matching ALL conditions will be assigned to this segment.
        </p>
        <pre class="text-xs bg-gray-100 p-2 rounded mt-2 overflow-x-auto">
[
  {
    "attribute": "country",
    "operator": "equals",
    "value": "US"
  },
  {
    "attribute": "visit_count",
    "operator": "greater_than",
    "value": 5
  }
]</pre>
        <p class="text-xs text-gray-600 mt-2">
            <strong>Available operators:</strong> equals, not_equals, contains, not_contains, greater_than, less_than, in, not_in
        </p>
        <p class="text-xs text-gray-600 mt-2">
            <strong>Available attributes:</strong> country, city, device_type, browser, visit_count
        </p>
        @error('criteria')
            <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div class="flex justify-end gap-2">
        <a href="{{ cp_route('kai-personalize.segments.index') }}" class="btn">
            Cancel
        </a>
        <button type="submit" class="btn-primary">
            {{ $segment ? 'Update Segment' : 'Create Segment' }}
        </button>
    </div>
</div>
