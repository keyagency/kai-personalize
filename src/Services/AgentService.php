<?php

namespace KeyAgency\KaiPersonalize\Services;

use Jenssegers\Agent\Agent;

class AgentService
{
    protected Agent $agent;

    public function __construct(?string $userAgent = null)
    {
        $this->agent = new Agent;

        if ($userAgent) {
            $this->agent->setUserAgent($userAgent);
        }
    }

    /**
     * Set the user agent string to parse
     */
    public function setUserAgent(string $userAgent): self
    {
        $this->agent->setUserAgent($userAgent);

        return $this;
    }

    /**
     * Get all browser-related attributes
     */
    public function getAttributes(): array
    {
        return [
            // Browser info
            'browser' => $this->getBrowser(),
            'browser_version' => $this->getBrowserVersion(),
            'browser_version_major' => $this->getBrowserVersionMajor(),

            // Platform/OS info
            'platform' => $this->getPlatform(),
            'platform_version' => $this->getPlatformVersion(),

            // Device info
            'device' => $this->getDevice(),
            'device_type' => $this->getDeviceType(),

            // Device capabilities
            'is_mobile' => $this->isMobile(),
            'is_tablet' => $this->isTablet(),
            'is_desktop' => $this->isDesktop(),
            'is_phone' => $this->isPhone(),

            // Bot detection
            'is_bot' => $this->isBot(),
            'bot_name' => $this->getBotName(),

            // Additional info
            'languages' => $this->getLanguages(),
            'robot' => $this->getRobot(),
        ];
    }

    /**
     * Get browser name
     */
    public function getBrowser(): ?string
    {
        $browser = $this->agent->browser();

        return $browser ?: null;
    }

    /**
     * Get full browser version
     */
    public function getBrowserVersion(): ?string
    {
        $version = $this->agent->version($this->agent->browser());

        return $version ?: null;
    }

    /**
     * Get major browser version number
     */
    public function getBrowserVersionMajor(): ?int
    {
        $version = $this->getBrowserVersion();

        if (! $version) {
            return null;
        }

        $parts = explode('.', $version);

        return (int) $parts[0];
    }

    /**
     * Get platform/OS name
     */
    public function getPlatform(): ?string
    {
        $platform = $this->agent->platform();

        return $platform ?: null;
    }

    /**
     * Get platform/OS version
     */
    public function getPlatformVersion(): ?string
    {
        $platform = $this->agent->platform();

        if (! $platform) {
            return null;
        }

        $version = $this->agent->version($platform);

        return $version ?: null;
    }

    /**
     * Get device name (e.g., iPhone, iPad, Nexus)
     */
    public function getDevice(): ?string
    {
        $device = $this->agent->device();

        return $device ?: null;
    }

    /**
     * Get device type (mobile, tablet, desktop)
     */
    public function getDeviceType(): string
    {
        if ($this->agent->isTablet()) {
            return 'tablet';
        }

        if ($this->agent->isMobile()) {
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * Check if device is mobile (includes phones and tablets)
     */
    public function isMobile(): bool
    {
        return $this->agent->isMobile();
    }

    /**
     * Check if device is a tablet
     */
    public function isTablet(): bool
    {
        return $this->agent->isTablet();
    }

    /**
     * Check if device is desktop
     */
    public function isDesktop(): bool
    {
        return $this->agent->isDesktop();
    }

    /**
     * Check if device is a phone (mobile but not tablet)
     */
    public function isPhone(): bool
    {
        return $this->agent->isPhone();
    }

    /**
     * Check if user agent is a bot/crawler
     */
    public function isBot(): bool
    {
        return $this->agent->isRobot();
    }

    /**
     * Get bot/robot name if detected
     */
    public function getBotName(): ?string
    {
        if (! $this->agent->isRobot()) {
            return null;
        }

        return $this->agent->robot();
    }

    /**
     * Get robot name (alias for getBotName)
     */
    public function getRobot(): ?string
    {
        $robot = $this->agent->robot();

        return $robot ?: null;
    }

    /**
     * Get accepted languages from headers
     */
    public function getLanguages(): array
    {
        return $this->agent->languages();
    }

    /**
     * Check if browser matches a specific name
     */
    public function isBrowser(string $browser): bool
    {
        return $this->agent->is($browser);
    }

    /**
     * Check specific browsers
     */
    public function isChrome(): bool
    {
        return $this->agent->is('Chrome');
    }

    public function isFirefox(): bool
    {
        return $this->agent->is('Firefox');
    }

    public function isSafari(): bool
    {
        return $this->agent->is('Safari');
    }

    public function isEdge(): bool
    {
        return $this->agent->is('Edge');
    }

    public function isOpera(): bool
    {
        return $this->agent->is('Opera');
    }

    public function isIE(): bool
    {
        return $this->agent->is('IE');
    }

    /**
     * Check specific platforms
     */
    public function isWindows(): bool
    {
        return $this->agent->is('Windows');
    }

    public function isMacOS(): bool
    {
        return $this->agent->is('OS X');
    }

    public function isLinux(): bool
    {
        return $this->agent->is('Linux');
    }

    public function isAndroid(): bool
    {
        return $this->agent->is('Android') || $this->agent->isAndroidOS();
    }

    public function isIOS(): bool
    {
        return $this->agent->is('iOS') || $this->agent->isiOS();
    }

    /**
     * Get the underlying Agent instance
     */
    public function getAgent(): Agent
    {
        return $this->agent;
    }
}
