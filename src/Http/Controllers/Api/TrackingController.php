<?php

namespace KeyAgency\KaiPersonalize\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use KeyAgency\KaiPersonalize\Models\Event;
use KeyAgency\KaiPersonalize\Models\Visitor;
use KeyAgency\KaiPersonalize\Models\VisitorSession;
use KeyAgency\KaiPersonalize\Services\TrackingSignatureService;

class TrackingController
{
    /**
     * Receive client-side behavioral events
     */
    public function track(Request $request): JsonResponse
    {
        // Check if behavioral tracking is enabled
        if (! config('kai-personalize.features.behavioral_tracking')) {
            return response()->json(['status' => 'disabled'], 200);
        }

        // Verify signature if enabled
        $signatureService = app(TrackingSignatureService::class);

        if ($signatureService->isEnabled()) {
            $signature = $request->input('signature');
            $timestamp = $request->input('timestamp');
            $nonce = $request->input('nonce');
            $visitorId = $request->input('visitor_id');

            if (! $signature || ! $timestamp || ! $nonce) {
                Log::warning('Kai tracking: Missing signature components', [
                    'ip' => $request->ip(),
                    'has_signature' => (bool) $signature,
                    'has_timestamp' => (bool) $timestamp,
                    'has_nonce' => (bool) $nonce,
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Signature verification required',
                ], 403);
            }

            // Build payload for verification
            $payload = [
                'nonce' => $nonce,
                'timestamp' => $timestamp,
                'visitor_id' => $visitorId,
            ];

            if (! $signatureService->verify($payload, $signature)) {
                Log::warning('Kai tracking: Invalid signature', [
                    'ip' => $request->ip(),
                    'visitor_id' => $visitorId,
                    'timestamp' => $timestamp,
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid or expired signature',
                ], 403);
            }
        }

        // Validate referer to prevent cross-origin attacks
        if (! $this->isValidReferer($request)) {
            Log::warning('Kai tracking: Invalid referer', [
                'ip' => $request->ip(),
                'referer' => $request->headers->get('referer'),
                'origin' => $request->headers->get('origin'),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Invalid origin',
            ], 403);
        }

        // Maximum events per request to prevent abuse
        $maxEvents = config('kai-personalize.performance.max_events_per_request', 50);

        $validator = Validator::make($request->all(), [
            'visitor_id' => 'required|string|max:255',
            'session_id' => 'required|string|max:255',
            'events' => "required|array|min:1|max:{$maxEvents}",
            'events.*.type' => 'required|string|max:50|regex:/^[a-z0-9_]+$/i',
            'events.*.data' => 'required|array',
            'fingerprint' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Sanitize event types to prevent injection
        $events = collect($request->input('events', []))->map(function ($event) {
            $event['type'] = preg_replace('/[^a-z0-9_]/i', '', $event['type']);
            // Remove any potentially dangerous keys from event data
            $event['data'] = $this->sanitizeEventData($event['data']);

            return $event;
        })->all();

        $visitorId = $request->input('visitor_id');
        $sessionId = $request->input('session_id');

        // Find visitor by fingerprint_hash (visitor_id from client is the fingerprint)
        $visitor = Visitor::where('fingerprint_hash', $visitorId)->first();

        if (! $visitor) {
            // Create a new visitor if not found
            $visitor = Visitor::createOrUpdate($visitorId, $sessionId);
        }

        // Find or create session
        $session = VisitorSession::where('session_id', $sessionId)->first();

        if (! $session) {
            // Get request data for session
            $ipAddress = config('kai-personalize.features.ip_tracking')
                ? $request->ip()
                : null;
            $userAgent = $request->userAgent();

            $session = VisitorSession::createOrUpdate(
                $visitor->id,
                $sessionId,
                $ipAddress,
                $userAgent
            );
        }

        $storedCount = 0;

        foreach ($events as $event) {
            try {
                Event::createEvent(
                    $visitor->id,
                    $session->id,
                    $event['type'],
                    $event['data']
                );
                $storedCount++;
            } catch (\Exception $e) {
                Log::warning('Failed to store Kai event', [
                    'event' => $event,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Update fingerprint if provided by client-side fingerprinting
        if ($request->has('fingerprint') && $request->input('fingerprint') !== $visitorId) {
            $newFingerprint = $request->input('fingerprint');

            // Check if this fingerprint already exists (visitor merge scenario)
            $existingVisitor = Visitor::where('fingerprint_hash', $newFingerprint)->first();

            if ($existingVisitor && $existingVisitor->id !== $visitor->id) {
                // Fingerprint already belongs to another visitor - use that visitor instead
                // This happens when the same user is tracked from different contexts
                // Update the session to reference the correct visitor
                $session->update([
                    'visitor_id' => $existingVisitor->id,
                ]);

                // Update stored events to reference the correct visitor
                Event::where('session_id', $session->id)
                    ->where('visitor_id', $visitor->id)
                    ->update(['visitor_id' => $existingVisitor->id]);

                // Use the existing visitor for response
                $visitor = $existingVisitor;

                Log::info('Kai tracking: Merged visitor to existing fingerprint', [
                    'temp_visitor_id' => $visitor->id,
                    'existing_visitor_id' => $existingVisitor->id,
                    'fingerprint_hash' => $newFingerprint,
                ]);
            } else {
                // Fingerprint is unique - safe to update
                try {
                    $visitor->update([
                        'fingerprint_hash' => $newFingerprint,
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    // Race condition: another request just created this fingerprint
                    // Log and continue with current visitor
                    Log::warning('Kai tracking: Failed to update fingerprint_hash (race condition)', [
                        'visitor_id' => $visitor->id,
                        'fingerprint_hash' => $newFingerprint,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'stored' => $storedCount,
            'visitor_id' => $visitor->id,
        ]);
    }

    /**
     * Get current visitor data for client-side tracking
     */
    public function visitor(Request $request): JsonResponse
    {
        $fingerprint = $request->query('fingerprint');
        $sessionId = $request->query('session_id');

        if (! $fingerprint || ! $sessionId) {
            return response()->json(['error' => 'Missing parameters'], 400);
        }

        $visitor = Visitor::where('fingerprint_hash', $fingerprint)->first();

        if (! $visitor) {
            return response()->json([
                'visitor_id' => null,
                'is_new' => true,
            ]);
        }

        $session = VisitorSession::where('session_id', $sessionId)->first();

        return response()->json([
            'visitor_id' => $visitor->id,
            'fingerprint_hash' => $visitor->fingerprint_hash,
            'session_id' => $session?->id,
            'visit_count' => $visitor->visit_count,
            'first_visit' => $visitor->first_visit_at?->toIso8601String(),
            'last_visit' => $visitor->last_visit_at?->toIso8601String(),
            'is_new' => false,
        ]);
    }

    /**
     * Sanitize event data to remove potentially dangerous values
     */
    protected function sanitizeEventData(array $data): array
    {
        $allowedKeys = [
            'max_depth', 'duration_ms', 'url', 'selector', 'element_id',
            'element_class', 'element_tag', 'click_x', 'click_y', 'page_height',
            'viewport_height', 'scroll_position', 'reading_time', 'visible',
            'form_id', 'form_name', 'field_name', 'field_type', 'video_id',
            'video_title', 'video_duration', 'video_position', 'video_percent',
            'custom', 'value', 'label', 'category', 'action',
        ];

        return collect($data)
            ->only($allowedKeys)
            ->map(function ($value) {
                // Sanitize string values
                if (is_string($value)) {
                    // Remove any HTML tags
                    $value = strip_tags($value);
                    // Limit length
                    $value = mb_substr($value, 0, 1000);
                }
                // Ensure numeric values are actually numeric
                elseif (is_numeric($value)) {
                    $value = is_float($value + 0) ? (float) $value : (int) $value;
                }
                // Ensure boolean values are actually boolean
                elseif (is_bool($value)) {
                    $value = (bool) $value;
                }

                return $value;
            })
            ->all();
    }

    /**
     * Validate the request comes from an allowed origin
     */
    protected function isValidReferer(Request $request): bool
    {
        $allowedOrigins = config('kai-personalize.tracking.allowed_origins');

        // If no allowed origins configured, allow all (for development)
        if (empty($allowedOrigins)) {
            return true;
        }

        $referer = $request->headers->get('referer');
        $origin = $request->headers->get('origin');

        // Check both referer and origin headers
        foreach ([$referer, $origin] as $header) {
            if (empty($header)) {
                continue;
            }

            foreach ($allowedOrigins as $allowed) {
                // Allow wildcard subdomains (e.g., *.example.com)
                if (str_starts_with($allowed, '*.')) {
                    $domain = substr($allowed, 2);
                    if (str_ends_with(parse_url($header, PHP_URL_HOST), $domain)) {
                        return true;
                    }
                }

                // Exact match or contains match
                if (str_contains($header, $allowed)) {
                    return true;
                }
            }
        }

        // Allow same-origin requests
        if ($referer && parse_url($referer, PHP_URL_HOST) === $request->getHost()) {
            return true;
        }

        return false;
    }
}
