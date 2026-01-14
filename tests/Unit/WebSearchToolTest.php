<?php

namespace WpAi\Anthropic\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WpAi\Anthropic\Tools\WebSearchTool;

class WebSearchToolTest extends TestCase
{
    public function test_basic_tool_creation(): void
    {
        $tool = WebSearchTool::make();

        $array = $tool->toArray();
        $this->assertEquals('web_search_20250305', $array['type']);
        $this->assertEquals('web_search', $array['name']);
    }

    public function test_allowed_domains(): void
    {
        $tool = WebSearchTool::make()
            ->allowedDomains(['example.com', 'test.com']);

        $array = $tool->toArray();
        $this->assertEquals(['example.com', 'test.com'], $array['allowed_domains']);
    }

    public function test_blocked_domains(): void
    {
        $tool = WebSearchTool::make()
            ->blockedDomains(['spam.com']);

        $array = $tool->toArray();
        $this->assertEquals(['spam.com'], $array['blocked_domains']);
    }

    public function test_max_uses(): void
    {
        $tool = WebSearchTool::make()
            ->maxUses(5);

        $array = $tool->toArray();
        $this->assertEquals(5, $array['max_uses']);
    }

    public function test_user_location_array(): void
    {
        $tool = WebSearchTool::make()
            ->userLocation([
                'type' => 'approximate',
                'country' => 'US',
                'region' => 'CA',
                'city' => 'San Francisco',
                'timezone' => 'America/Los_Angeles',
            ]);

        $array = $tool->toArray();
        $this->assertEquals('US', $array['user_location']['country']);
        $this->assertEquals('San Francisco', $array['user_location']['city']);
    }

    public function test_location_helper(): void
    {
        $tool = WebSearchTool::make()
            ->location('US', 'CA', 'San Francisco', 'America/Los_Angeles');

        $array = $tool->toArray();
        $this->assertEquals('approximate', $array['user_location']['type']);
        $this->assertEquals('US', $array['user_location']['country']);
        $this->assertEquals('CA', $array['user_location']['region']);
    }

    public function test_full_configuration(): void
    {
        $tool = WebSearchTool::make()
            ->allowedDomains(['docs.anthropic.com'])
            ->blockedDomains(['spam.com'])
            ->maxUses(10)
            ->location('US');

        $array = $tool->toArray();

        $this->assertEquals('web_search_20250305', $array['type']);
        $this->assertEquals('web_search', $array['name']);
        $this->assertEquals(['docs.anthropic.com'], $array['allowed_domains']);
        $this->assertEquals(['spam.com'], $array['blocked_domains']);
        $this->assertEquals(10, $array['max_uses']);
        $this->assertEquals('US', $array['user_location']['country']);
    }
}
