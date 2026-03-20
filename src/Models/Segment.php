<?php

namespace KeyAgency\KaiPersonalize\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Segment extends Model
{
    protected $table = 'kai_personalize_segments';

    protected $fillable = [
        'name',
        'description',
        'criteria',
        'is_active',
        'visitor_count',
    ];

    protected $casts = [
        'criteria' => 'json',
        'is_active' => 'boolean',
        'visitor_count' => 'integer',
    ];

    public function visitors(): BelongsToMany
    {
        return $this->belongsToMany(Visitor::class, 'kai_personalize_segment_visitor')
            ->withTimestamps()
            ->withPivot('assigned_at');
    }

    /**
     * Scope to get active segments
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Evaluate segment criteria against visitor data
     */
    public function evaluate(array $visitorData): bool
    {
        if (! $this->is_active) {
            return false;
        }

        foreach ($this->criteria as $condition) {
            if (! $this->evaluateCondition($condition, $visitorData)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a single condition
     */
    protected function evaluateCondition(array $condition, array $visitorData): bool
    {
        $attribute = $condition['attribute'] ?? null;
        $operator = $condition['operator'] ?? 'equals';
        $value = $condition['value'] ?? null;

        if (! $attribute || ! isset($visitorData[$attribute])) {
            return false;
        }

        $actualValue = $visitorData[$attribute];

        return match ($operator) {
            'equals' => $actualValue == $value,
            'not_equals' => $actualValue != $value,
            'contains' => str_contains((string) $actualValue, (string) $value),
            'not_contains' => ! str_contains((string) $actualValue, (string) $value),
            'greater_than' => $actualValue > $value,
            'less_than' => $actualValue < $value,
            'in' => in_array($actualValue, (array) $value),
            'not_in' => ! in_array($actualValue, (array) $value),
            default => false,
        };
    }

    /**
     * Assign a visitor to this segment
     */
    public function assignVisitor(Visitor $visitor): void
    {
        if (! $this->visitors()->where('visitor_id', $visitor->id)->exists()) {
            $this->visitors()->attach($visitor->id, ['assigned_at' => now()]);
            $this->increment('visitor_count');
        }
    }

    /**
     * Remove a visitor from this segment
     */
    public function removeVisitor(Visitor $visitor): void
    {
        if ($this->visitors()->where('visitor_id', $visitor->id)->exists()) {
            $this->visitors()->detach($visitor->id);
            $this->decrement('visitor_count');
        }
    }

    /**
     * Refresh visitor count
     */
    public function refreshVisitorCount(): void
    {
        $count = $this->visitors()->count();
        $this->update(['visitor_count' => $count]);
    }

    /**
     * Check if visitor belongs to this segment
     */
    public function hasVisitor(Visitor $visitor): bool
    {
        return $this->visitors()->where('visitor_id', $visitor->id)->exists();
    }

    /**
     * Assign all matching visitors to this segment
     */
    public function assignMatchingVisitors(): int
    {
        if (! $this->is_active) {
            return 0;
        }

        $assigned = 0;
        $visitors = Visitor::with(['attributes', 'sessions'])->get();

        foreach ($visitors as $visitor) {
            // Build visitor data array for evaluation
            $visitorData = [
                'country' => $visitor->getVisitorAttribute('country'),
                'city' => $visitor->getVisitorAttribute('city'),
                'device_type' => $visitor->getVisitorAttribute('device_type'),
                'browser' => $visitor->getVisitorAttribute('browser'),
                'visit_count' => $visitor->visit_count,
            ];

            if ($this->evaluate($visitorData)) {
                $this->assignVisitor($visitor);
                $assigned++;
            }
        }

        return $assigned;
    }
}
