<?php

namespace KeyAgency\KaiPersonalize\Tags;

use Statamic\Tags\Tags;

class KaiTracking extends Tags
{
    protected static $handle = 'kai_tracking';

    /**
     * {{ kai:tracking }}
     * Returns the tracking endpoint and whether behavioural tracking is on
     */
    public function index(): array
    {
        return [
            'url' => $this->url(),
            'enabled' => (bool) config('kai-personalize.features.behavioral_tracking'),
        ];
    }

    /**
     * Get tracking URL
     */
    public function url(): string
    {
        return route('statamic.track');
    }
}
