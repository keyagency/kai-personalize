<?php

namespace KeyAgency\KaiPersonalize\Tags;

use Illuminate\Support\Facades\Session;
use Statamic\Tags\Tags;

class KaiSession extends Tags
{
    // Note: This class is instantiated internally by the Kai tag class

    /**
     * {{ kai:session:set key="value" }}
     */
    public function set(): string
    {
        $key = $this->params->get('key');
        $value = $this->params->get('value');

        if ($key) {
            Session::put('kai_'.$key, $value);
        }

        return '';
    }

    /**
     * {{ kai:session:get key="key" }}
     */
    public function get(): mixed
    {
        $key = $this->params->get('key');

        if (! $key) {
            return null;
        }

        return Session::get('kai_'.$key);
    }

    /**
     * {{ kai:session:tracked }}
     */
    public function tracked(): bool
    {
        return Session::has(config('kai-personalize.session.visitor_id_key'));
    }

    /**
     * {{ kai:session:forget key="key" }}
     */
    public function forget(): string
    {
        $key = $this->params->get('key');

        if ($key) {
            Session::forget('kai_'.$key);
        }

        return '';
    }
}
