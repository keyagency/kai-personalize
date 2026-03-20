<?php

namespace KeyAgency\KaiPersonalize\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rule extends Model
{
    protected $table = 'kai_personalize_rules';

    protected $fillable = [
        'name',
        'description',
        'conditions',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'conditions' => 'json',
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(Log::class);
    }

    /**
     * Scope to get active rules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get rules ordered by priority
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Evaluate rule conditions against visitor data
     */
    public function evaluate(array $visitorData): bool
    {
        if (! $this->is_active) {
            return false;
        }

        foreach ($this->conditions as $condition) {
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
}
