/**
 * Kai Personalize - Behavioral Tracking
 * Tracks user interactions for personalization and analytics
 */

(function() {
    'use strict';

    // Configuration from server (set by PHP tag)
    const config = window.KaiConfig || {
        visitorId: '',
        sessionId: '',
        endpoint: '',
        features: {
            scroll: true,
            click: true,
            form: false,
            video: false,
            fingerprint: true,
        },
        respectDnt: true,
    };

    // Tracker version
    const TRACKER_VERSION = '1.2.5';

    // ========== Tracker Queue Settings ==========
    // These can be overridden by server-side config via window.KaiConfig.queueSettings
    const queueSettings = Object.assign({
        // Number of events to trigger auto-send
        threshold: 5,

        // Interval in milliseconds for periodic sending (20000 = 20 seconds)
        sendInterval: 20000,

        // Enable/disable localStorage persistence
        persistQueue: true,

        // localStorage key name
        storageKey: 'kai_tracker_queue',

        // Maximum age of queued events in milliseconds (optional - 1 hour default)
        // Set to 0 to disable age checking
        maxEventAge: 3600000,
    }, window.KaiConfig.queueSettings || {});

    // Event queue for batching
    const eventQueue = [];
    let isInitialized = false;

    // ========== localStorage Helper Functions ==========
    // Save queue to localStorage
    function saveQueue() {
        if (!queueSettings.persistQueue) return;

        try {
            localStorage.setItem(queueSettings.storageKey, JSON.stringify({
                events: eventQueue,
                timestamp: Date.now(),
            }));
        } catch (e) {
            // Silently fail on quota/error (don't break tracking)
            console.warn('[Kai Tracker] Could not save queue to localStorage:', e.message);
        }
    }

    // Load queue from localStorage
    function loadQueue() {
        if (!queueSettings.persistQueue) return [];

        try {
            const stored = localStorage.getItem(queueSettings.storageKey);
            if (stored) {
                const parsed = JSON.parse(stored);

                // Handle old format (array only) for backward compatibility
                if (Array.isArray(parsed)) {
                    return parsed;
                }

                // Handle new format (object with events + timestamp)
                if (parsed.events && Array.isArray(parsed.events)) {
                    // Check if events are too old
                    if (queueSettings.maxEventAge > 0 && parsed.timestamp) {
                        const age = Date.now() - parsed.timestamp;
                        if (age > queueSettings.maxEventAge) {
                            console.log('[Kai Tracker] Discarding stale queued events (age:', Math.round(age / 1000), 's)');
                            clearStoredQueue();
                            return [];
                        }
                    }
                    return parsed.events;
                }
            }
        } catch (e) {
            console.warn('[Kai Tracker] Could not load queue from localStorage:', e.message);
        }
        return [];
    }

    // Clear queue from localStorage
    function clearStoredQueue() {
        if (!queueSettings.persistQueue) return;

        try {
            localStorage.removeItem(queueSettings.storageKey);
        } catch (e) {
            console.warn('[Kai Tracker] Could not clear queue from localStorage:', e.message);
        }
    }

    // Check consent and DNT
    function hasConsent() {
        // Check DNT header
        if (config.respectDnt && navigator.doNotTrack === '1') {
            return false;
        }

        // Check for cookie consent (common implementations)
        if (window.KaiConsentCallback) {
            return window.KaiConsentCallback();
        }

        // Check for common consent cookie names
        const consentCookies = ['cookie_consent', 'cookieconsent_status', 'cc_cookie', 'catConsent'];
        for (const name of consentCookies) {
            const value = getCookie(name);
            if (value === 'false' || value === 'deny') {
                return false;
            }
        }

        return true;
    }

    function getCookie(name) {
        const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? match[2] : null;
    }

    // Send events via sendBeacon or fetch
    function sendEvents() {
        if (eventQueue.length === 0 || !hasConsent()) {
            return;
        }

        const events = [...eventQueue];
        eventQueue.length = 0;

        // Clear from localStorage since we're attempting to send
        clearStoredQueue();

        const payload = {
            visitor_id: config.visitorId,
            session_id: config.sessionId,
            tracker_version: TRACKER_VERSION,
            events: events,
        };

        // Add fingerprint if generated
        if (window.KaiFingerprint) {
            payload.fingerprint = window.KaiFingerprint;
        }

        // Prefer sendBeacon for reliability
        if (navigator.sendBeacon) {
            const blob = new Blob([JSON.stringify(payload)], { type: 'application/json' });
            const success = navigator.sendBeacon(config.endpoint, blob);

            // sendBeacon returns false if queue is full, requeue events
            if (!success) {
                eventQueue.push(...events);
                saveQueue();
            }
        } else {
            fetch(config.endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
                keepalive: true,
            }).catch(() => {
                // Requeue events on failure
                eventQueue.push(...events);
                saveQueue();
            });
        }
    }

    // Queue an event
    function queueEvent(type, data) {
        if (!hasConsent()) {
            return;
        }

        // Add timestamp to event for age tracking
        eventQueue.push({
            type,
            data,
            timestamp: Date.now(),
        });

        // Save to localStorage for persistence (if enabled)
        saveQueue();

        // Auto-send when queue reaches threshold (configurable, default: 5)
        if (eventQueue.length >= queueSettings.threshold) {
            sendEvents();
        }
    }

    // ========== Scroll Tracking ==========
    function initScrollTracking() {
        if (!config.features.scroll) return;
        if (window.KaiTrackerState?.scrollTrackingInitialized) return;

        const thresholds = [25, 50, 75, 90, 100];
        const reached = new Set();
        let maxDepth = 0;
        let scrollStart = Date.now();
        let totalScrollTime = 0;

        const onScroll = () => {
            const scrollTop = window.scrollY;
            const docHeight = document.documentElement.scrollHeight - window.innerHeight;
            const depth = Math.round((scrollTop / docHeight) * 100);

            if (depth > maxDepth) {
                maxDepth = depth;
            }

            for (const threshold of thresholds) {
                if (depth >= threshold && !reached.has(threshold)) {
                    reached.add(threshold);
                    queueEvent('scroll_depth', {
                        threshold: threshold,
                        max_depth: maxDepth,
                        url: window.location.pathname,
                    });
                }
            }
        };

        let scrollTimeout;
        window.addEventListener('scroll', () => {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(onScroll, 100);
        }, { passive: true });

        // Track active reading time
        window.addEventListener('scroll', () => {
            if (document.hidden) return;
            scrollStart = Date.now();
        });

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                totalScrollTime += Date.now() - scrollStart;
                queueEvent('reading_time', {
                    duration_ms: totalScrollTime,
                    max_depth: maxDepth,
                    url: window.location.pathname,
                });
            } else {
                scrollStart = Date.now();
            }
        });

        // Mark as initialized
        if (!window.KaiTrackerState) window.KaiTrackerState = {};
        window.KaiTrackerState.scrollTrackingInitialized = true;
    }

    // ========== Visibility Tracking ==========
    function initVisibilityTracking() {
        if (!config.features.scroll) return;
        if (window.KaiTrackerState?.visibilityTrackingInitialized) return;

        let visibleStart = Date.now();
        let totalTime = 0;

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                totalTime += Date.now() - visibleStart;
                queueEvent('visibility', {
                    action: 'hidden',
                    active_time_ms: totalTime,
                    url: window.location.pathname,
                });
            } else {
                visibleStart = Date.now();
                queueEvent('visibility', {
                    action: 'visible',
                    url: window.location.pathname,
                });
            }
        });

        // Send final visibility event on page unload
        window.addEventListener('pagehide', () => {
            totalTime += Date.now() - visibleStart;
            queueEvent('visibility', {
                action: 'pagehide',
                active_time_ms: totalTime,
                url: window.location.pathname,
            });
        });

        // Mark as initialized
        if (!window.KaiTrackerState) window.KaiTrackerState = {};
        window.KaiTrackerState.visibilityTrackingInitialized = true;
    }

    // ========== Click Tracking ==========
    function initClickTracking() {
        if (!config.features.click) return;
        if (window.KaiTrackerState?.clickTrackingInitialized) return;

        const clickTargets = new Map();
        const rageThreshold = 3;
        const rageWindow = 2000;
        const deadSelectors = ['div', 'span', 'p', 'article', 'section'];

        document.addEventListener('click', (e) => {
            const target = e.target;
            const selector = getSelector(target);

            // Track hesitation (time from mouseenter to click)
            const hesitation = target.dataset.kaiHoverTime
                ? Date.now() - parseInt(target.dataset.kaiHoverTime)
                : null;

            const clickData = {
                selector: selector,
                tag: target.tagName.toLowerCase(),
                id: target.id || null,
                classes: target.className || null,
                has_href: !!target.closest('a'),
                has_button: target.tagName === 'BUTTON' || target.type === 'submit',
                hesitation_ms: hesitation,
                x: Math.round(e.clientX / 10) * 10,
                y: Math.round(e.clientY / 10) * 10,
                url: window.location.pathname,
            };

            // Check for rage clicks
            const now = Date.now();
            const key = selector;
            const recent = clickTargets.get(key) || [];

            recent.push(now);
            clickTargets.set(key, recent.filter(t => now - t < rageWindow));

            if (recent.length >= rageThreshold) {
                queueEvent('rage_click', {
                    ...clickData,
                    count: recent.length,
                });
            } else {
                queueEvent('click', clickData);
            }

            // Check for dead clicks (click on non-interactive elements)
            const isDead = deadSelectors.includes(target.tagName.toLowerCase()) &&
                !target.onclick &&
                !target.closest('a') &&
                !target.closest('button') &&
                target.type !== 'submit' &&
                target.type !== 'button';

            if (isDead) {
                queueEvent('dead_click', clickData);
            }
        }, true);


        // Track hover time for hesitation detection
        document.addEventListener('mouseenter', (e) => {
            if (e.target && e.target.dataset) {
                e.target.dataset.kaiHoverTime = Date.now();
            }
        }, true);

        document.addEventListener('mouseleave', (e) => {
            if (e.target && e.target.dataset) {
                delete e.target.dataset.kaiHoverTime;
            }
        }, true);

        // Mark as initialized
        if (!window.KaiTrackerState) window.KaiTrackerState = {};
        window.KaiTrackerState.clickTrackingInitialized = true;
    }

    function getSelector(el) {
        if (el.id) return '#' + el.id;
        if (el.className) return '.' + el.className.split(' ').join('.');
        return el.tagName.toLowerCase();
    }

    // ========== Device Capabilities ==========
    function initDeviceCapabilities() {
        const data = {
            viewport: {
                width: window.innerWidth,
                height: window.innerHeight,
            },
            screen: {
                width: window.screen.width,
                height: window.screen.height,
                color_depth: window.screen.colorDepth,
                available: {
                    width: window.screen.availWidth,
                    height: window.screen.availHeight,
                },
                device_pixel_ratio: window.devicePixelRatio,
                orientation: window.innerWidth > window.innerHeight ? 'landscape' : 'portrait',
            },
            touch: 'ontouchstart' in window || navigator.maxTouchPoints > 0,
            connection: navigator.connection ? {
                effective_type: navigator.connection.effectiveType,
                downlink: navigator.connection.downlink,
                rtt: navigator.connection.rtt,
            } : null,
        };

        queueEvent('device_capabilities', data);
    }

    // ========== User Preferences ==========
    function initUserPreferences() {
        const data = {
            dark_mode: window.matchMedia('(prefers-color-scheme: dark)').matches,
            reduced_motion: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
            language: navigator.language,
            languages: navigator.languages,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        };

        queueEvent('user_preferences', data);
    }

    // ========== Exit Intent ==========
    function initExitIntent() {
        if (!config.features.scroll) return;
        if (window.KaiTrackerState?.exitIntentInitialized) return;

        document.addEventListener('mouseout', (e) => {
            if (e.clientY < 10 && e.relatedTarget === null) {
                queueEvent('exit_intent', {
                    url: window.location.pathname,
                    scroll_depth: Math.round(
                        (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100
                    ),
                });
            }
        });

        // Mark as initialized
        if (!window.KaiTrackerState) window.KaiTrackerState = {};
        window.KaiTrackerState.exitIntentInitialized = true;
    }

    // ========== Idle Detection ==========
    function initIdleDetection() {
        if (window.KaiTrackerState?.idleDetectionInitialized) return;

        const idleTime = 60000; // 60 seconds
        let idleTimer;

        function resetIdleTimer() {
            clearTimeout(idleTimer);
            idleTimer = setTimeout(() => {
                queueEvent('idle', {
                    duration_ms: idleTime,
                    url: window.location.pathname,
                });
            }, idleTime);
        }

        ['mousedown', 'keydown', 'scroll', 'touchstart'].forEach(event => {
            window.addEventListener(event, resetIdleTimer, { passive: true });
        });

        resetIdleTimer();

        // Mark as initialized
        if (!window.KaiTrackerState) window.KaiTrackerState = {};
        window.KaiTrackerState.idleDetectionInitialized = true;
    }

    // ========== Browser Fingerprinting ==========
    function initFingerprinting() {
        if (!config.features.fingerprint) return;
        if (config.respectDnt && navigator.doNotTrack === '1') return;

        Promise.all([
            getCanvasHash(),
            getWebGLHash(),
        ]).then(([canvas, webgl]) => {
            const components = [];

            if (canvas) components.push(canvas);
            if (webgl) components.push(webgl);

            // Add screen properties
            components.push(window.screen.width + 'x' + window.screen.height);
            components.push(window.screen.colorDepth);
            components.push(new Date().getTimezoneOffset());
            components.push(navigator.language);
            components.push(navigator.platform);

            if (components.length > 0) {
                const fingerprint = components.join('|');
                window.KaiFingerprint = simpleHash(fingerprint);

                // Send fingerprint update
                queueEvent('fingerprint', {
                    hash: window.KaiFingerprint,
                });
            }
        }).catch(() => {
            // Silently fail on error
        });
    }

    async function getCanvasHash() {
        try {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            if (!ctx) return null;

            canvas.width = 280;
            canvas.height = 60;
            ctx.textBaseline = 'top';
            ctx.font = '14px Arial';
            ctx.fillStyle = '#f60';
            ctx.fillRect(100, 1, 62, 20);
            ctx.fillStyle = '#069';
            ctx.fillText('Kai Personalize Fingerprint', 2, 15);
            ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
            ctx.fillText('kai-personalize', 4, 45);

            return canvas.toDataURL().slice(0, 100);
        } catch {
            return null;
        }
    }

    async function getWebGLHash() {
        try {
            const canvas = document.createElement('canvas');
            const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
            if (!gl) return null;

            const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
            if (!debugInfo) return null;

            const vendor = gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL);
            const renderer = gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL);

            return vendor + '|' + renderer;
        } catch {
            return null;
        }
    }

    // Simple hash function for fingerprint
    function simpleHash(str) {
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash;
        }
        return hash.toString(16);
    }

    // ========== Page View Tracking ==========
    function trackPageView() {
        queueEvent('page_view', {
            url: window.location.pathname,
            title: document.title,
            referrer: document.referrer || null,
            screen_width: window.screen.width,
            screen_height: window.screen.height,
        });
    }

    // ========== Initialization ==========
    function init() {
        if (isInitialized || !hasConsent()) {
            return;
        }

        isInitialized = true;

        // Restore any queued events from previous session
        const restoredEvents = loadQueue();
        if (restoredEvents.length > 0) {
            eventQueue.push(...restoredEvents);
            console.log('[Kai Tracker] Restored', restoredEvents.length, 'events from previous session');
        }

        // Track initial page view
        trackPageView();

        // Initialize features
        initScrollTracking();
        initVisibilityTracking();
        initClickTracking();
        initDeviceCapabilities();
        initUserPreferences();
        initExitIntent();
        initIdleDetection();
        initFingerprinting();

        // Send events on page visibility change
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                sendEvents();
            }
        });

        // Send events on page unload
        window.addEventListener('pagehide', sendEvents);

        // Periodic send using configurable interval (default: 20 seconds)
        setInterval(sendEvents, queueSettings.sendInterval);
    }

    // ========== Swup Integration ==========
    // Reinitialize tracking on Swup page transitions
    window.addEventListener('swup:init', function() {
        // Track page view for the new page
        trackPageView();

        // Note: Event listeners (scroll, click, visibility, etc.) are already attached
        // to window/document and persist across Swup navigations, so we don't need to
        // reinitialize them. The trackPageView() call above will record the new page.
    });

    // Send pending events before Swup content replacement
    window.addEventListener('swup:cleanup', function() {
        sendEvents();
        // If sendEvents didn't clear the queue (e.g., consent revoked), save it
        if (eventQueue.length > 0) {
            saveQueue();
        }
    });

    // Auto-initialize after DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose API for manual control
    window.KaiTracker = {
        init: init,
        track: queueEvent,
        send: sendEvents,
        hasConsent: hasConsent,
        queueEvent: queueEvent,
    };
})();
