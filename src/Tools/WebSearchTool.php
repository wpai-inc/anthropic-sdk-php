<?php

namespace WpAi\Anthropic\Tools;

class WebSearchTool
{
    private const TYPE = 'web_search_20250305';
    private const NAME = 'web_search';

    private array $allowedDomains = [];
    private array $blockedDomains = [];
    private ?int $maxUses = null;
    private ?array $userLocation = null;

    private function __construct() {}

    /**
     * Create a new web search tool instance.
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * Set allowed domains for search results.
     *
     * @param array $domains List of allowed domain names
     */
    public function allowedDomains(array $domains): self
    {
        $this->allowedDomains = $domains;
        return $this;
    }

    /**
     * Set blocked domains to exclude from search results.
     *
     * @param array $domains List of blocked domain names
     */
    public function blockedDomains(array $domains): self
    {
        $this->blockedDomains = $domains;
        return $this;
    }

    /**
     * Set maximum number of times the tool can be used.
     *
     * @param int $maxUses Maximum number of search calls
     */
    public function maxUses(int $maxUses): self
    {
        $this->maxUses = $maxUses;
        return $this;
    }

    /**
     * Set user location for location-aware search results.
     *
     * @param array $location Location config with keys: type, country, region, city, timezone
     */
    public function userLocation(array $location): self
    {
        $this->userLocation = $location;
        return $this;
    }

    /**
     * Set user location using individual parameters.
     *
     * @param string $country ISO 3166-1 alpha-2 country code
     * @param string|null $region Region/state code
     * @param string|null $city City name
     * @param string|null $timezone Timezone identifier (e.g., 'America/Los_Angeles')
     */
    public function location(
        string $country,
        ?string $region = null,
        ?string $city = null,
        ?string $timezone = null
    ): self {
        $location = [
            'type' => 'approximate',
            'country' => $country,
        ];

        if ($region !== null) {
            $location['region'] = $region;
        }
        if ($city !== null) {
            $location['city'] = $city;
        }
        if ($timezone !== null) {
            $location['timezone'] = $timezone;
        }

        $this->userLocation = $location;
        return $this;
    }

    /**
     * Convert to array format for API request.
     */
    public function toArray(): array
    {
        $tool = [
            'type' => self::TYPE,
            'name' => self::NAME,
        ];

        if (!empty($this->allowedDomains)) {
            $tool['allowed_domains'] = $this->allowedDomains;
        }

        if (!empty($this->blockedDomains)) {
            $tool['blocked_domains'] = $this->blockedDomains;
        }

        if ($this->maxUses !== null) {
            $tool['max_uses'] = $this->maxUses;
        }

        if ($this->userLocation !== null) {
            $tool['user_location'] = $this->userLocation;
        }

        return $tool;
    }
}
