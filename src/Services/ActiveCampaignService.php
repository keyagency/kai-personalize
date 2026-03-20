<?php

namespace KeyAgency\KaiPersonalize\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use KeyAgency\KaiPersonalize\Edition;

class ActiveCampaignService
{
    protected string $apiUrl;

    protected string $apiKey;

    protected string $cookieName;

    protected int $cacheTtl;

    public function __construct()
    {
        $this->apiUrl = rtrim(config('kai-personalize.activecampaign.api_url', ''), '/');
        $this->apiKey = config('kai-personalize.activecampaign.api_key', '');
        $this->cookieName = config('kai-personalize.activecampaign.cookie_name', 'vgo_ee');
        $this->cacheTtl = config('kai-personalize.activecampaign.cache_ttl', 1440); // 24 hours
    }

    /**
     * Get contact data from ActiveCampaign tracking cookie
     */
    public function getContactFromCookie(): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $email = $this->getEmailFromCookie();

        if (! $email) {
            return null;
        }

        return $this->getContactByEmail($email);
    }

    /**
     * Get contact data by email address
     */
    public function getContactByEmail(string $email): ?array
    {
        if (! $this->isEnabled() || ! $email) {
            return null;
        }

        // Check cache first
        $cacheKey = "kai_ac_contact_{$email}";
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        try {
            // Fetch contact from ActiveCampaign API
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Api-Token' => $this->apiKey,
            ])->timeout(10)->get("{$this->apiUrl}/api/3/contacts", [
                'email' => $email,
            ]);

            if (! $response->successful()) {
                Log::warning('ActiveCampaign API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();

            // Check if contact exists
            $contact = $data['contacts'][0] ?? null;

            if (! $contact) {
                Log::debug('ActiveCampaign contact not found', ['email' => $email]);

                return null;
            }

            // Fetch related data (tags, lists)
            $contactId = $contact['id'];
            $formatted = $this->formatContactData($contact);

            // Fetch tags
            $tags = $this->fetchContactTags($contactId);
            $formatted['ac_tags'] = $tags;

            // Fetch lists
            $lists = $this->fetchContactLists($contactId);
            $formatted['ac_lists'] = $lists;

            // Fetch custom fields
            $fields = $this->fetchContactFields($contact);
            $formatted['ac_custom_fields'] = $fields;

            // Store in cache
            Cache::put($cacheKey, $formatted, $this->cacheTtl * 60); // Convert minutes to seconds

            return $formatted;

        } catch (\Exception $e) {
            Log::error('ActiveCampaign API request failed', [
                'error' => $e->getMessage(),
                'email' => $email,
            ]);

            return null;
        }
    }

    /**
     * Fetch tags for a contact
     */
    protected function fetchContactTags(string $contactId): array
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Api-Token' => $this->apiKey,
            ])->timeout(10)->get("{$this->apiUrl}/api/3/contacts/{$contactId}/contactTags");

            if (! $response->successful()) {
                return [];
            }

            $data = $response->json();
            $tags = [];

            foreach ($data['contactTags'] ?? [] as $contactTag) {
                $tag = $contactTag['tag'] ?? null;
                if ($tag) {
                    $tags[] = $tag['tag'];
                }
            }

            return $tags;

        } catch (\Exception $e) {
            Log::warning('Failed to fetch ActiveCampaign tags', [
                'error' => $e->getMessage(),
                'contact_id' => $contactId,
            ]);

            return [];
        }
    }

    /**
     * Fetch lists for a contact
     */
    protected function fetchContactLists(string $contactId): array
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Api-Token' => $this->apiKey,
            ])->timeout(10)->get("{$this->apiUrl}/api/3/contacts/{$contactId}/contactLists");

            if (! $response->successful()) {
                return [];
            }

            $data = $response->json();
            $lists = [];

            foreach ($data['contactLists'] ?? [] as $contactList) {
                $list = $contactList['list'] ?? null;
                if ($list) {
                    $lists[$list['name']] = [
                        'id' => $list['id'],
                        'status' => $contactList['status'] ?? 0, // 1 = active, 2 = unsubscribed
                    ];
                }
            }

            return $lists;

        } catch (\Exception $e) {
            Log::warning('Failed to fetch ActiveCampaign lists', [
                'error' => $e->getMessage(),
                'contact_id' => $contactId,
            ]);

            return [];
        }
    }

    /**
     * Fetch custom field values for a contact
     */
    protected function fetchContactFields(array $contact): array
    {
        try {
            $fields = [];
            $fieldData = $contact['fields'] ?? [];

            foreach ($fieldData as $field) {
                $fieldDef = $field['field'] ?? null;
                if ($fieldDef) {
                    $fields[$fieldDef['title']] = $field['value'] ?? null;
                }
            }

            return $fields;

        } catch (\Exception $e) {
            Log::warning('Failed to parse ActiveCampaign fields', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Format contact data for storage as visitor attributes
     */
    protected function formatContactData(array $contact): array
    {
        return [
            'ac_contact_id' => $contact['id'] ?? null,
            'ac_email' => $contact['email'] ?? null,
            'ac_first_name' => $contact['firstName'] ?? null,
            'ac_last_name' => $contact['lastName'] ?? null,
            'ac_phone' => $contact['phone'] ?? null,
            'ac_created_at' => $contact['cdate'] ?? null,
            'ac_updated_at' => $contact['udate'] ?? null,
            'ac_tags' => [], // Filled separately
            'ac_lists' => [], // Filled separately
            'ac_custom_fields' => [], // Filled separately
        ];
    }

    /**
     * Decode email from ActiveCampaign tracking cookie
     */
    protected function getEmailFromCookie(): ?string
    {
        $cookieValue = $_COOKIE[$this->cookieName] ?? null;

        if (! $cookieValue) {
            // Try alternative cookie names
            $cookieValue = $_COOKIE['__actc'] ?? $_COOKIE['contact_email'] ?? null;
        }

        if (! $cookieValue) {
            return null;
        }

        return $this->decodeEmail($cookieValue);
    }

    /**
     * Decode email from cookie value
     * ActiveCampaign uses various encoding methods
     */
    protected function decodeEmail(string $encoded): ?string
    {
        // Method 1: Base64 decode
        $decoded = base64_decode($encoded);
        if ($decoded && filter_var($decoded, FILTER_VALIDATE_EMAIL)) {
            return $decoded;
        }

        // Method 2: URL decode first, then base64
        $urlDecoded = urldecode($encoded);
        $decoded = base64_decode($urlDecoded);
        if ($decoded && filter_var($decoded, FILTER_VALIDATE_EMAIL)) {
            return $decoded;
        }

        // Method 3: Already plain text email (with validation)
        if (filter_var($encoded, FILTER_VALIDATE_EMAIL)) {
            return $encoded;
        }

        // Method 4: URL decode only
        $urlDecoded = urldecode($encoded);
        if (filter_var($urlDecoded, FILTER_VALIDATE_EMAIL)) {
            return $urlDecoded;
        }

        // Method 5: Try rawurldecode
        $rawDecoded = rawurldecode($encoded);
        if ($rawDecoded && filter_var($rawDecoded, FILTER_VALIDATE_EMAIL)) {
            return $rawDecoded;
        }

        return null;
    }

    /**
     * Test API connection
     */
    public function test(): array
    {
        if (! $this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'ActiveCampaign is not configured.',
            ];
        }

        try {
            // Use contacts endpoint with limit=0 instead of accounts (requires higher tier)
            // This tests the API connection without fetching any actual data
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Api-Token' => $this->apiKey,
            ])->timeout(10)->get("{$this->apiUrl}/api/3/contacts", ['limit' => 0]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Connection successful!',
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'message' => 'Connection failed: '.$response->body(),
                'status' => $response->status(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check if ActiveCampaign is properly configured and enabled
     */
    public function isEnabled(): bool
    {
        return Edition::isPro()
            && config('kai-personalize.features.activecampaign', false)
            && ! empty($this->apiUrl)
            && ! empty($this->apiKey);
    }

    /**
     * Set the cookie name (for testing purposes)
     */
    public function setCookieName(string $name): void
    {
        $this->cookieName = $name;
    }

    /**
     * Set cookie value (for testing purposes)
     */
    public function setTestCookie(string $email): void
    {
        $_COOKIE[$this->cookieName] = base64_encode($email);
    }
}
