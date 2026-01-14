<?php

namespace WpAi\Anthropic\Tests\Integration;

use PHPUnit\Framework\TestCase;
use WpAi\Anthropic\AnthropicAPI;
use WpAi\Anthropic\Models;
use WpAi\Anthropic\Responses\TokenCountResponse;

class TokenCountIntegrationTest extends TestCase
{
    private ?AnthropicAPI $api = null;

    protected function setUp(): void
    {
        $apiKey = getenv('ANTHROPIC_API_KEY') ?: ($_ENV['ANTHROPIC_API_KEY'] ?? null);

        if (empty($apiKey)) {
            $envFile = dirname(__DIR__, 2) . '/.env';
            if (file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (str_starts_with($line, 'ANTHROPIC_API_KEY=')) {
                        $apiKey = substr($line, strlen('ANTHROPIC_API_KEY='));
                        break;
                    }
                }
            }
        }

        if (empty($apiKey)) {
            $this->markTestSkipped('ANTHROPIC_API_KEY not set');
        }

        $this->api = new AnthropicAPI($apiKey);
    }

    public function test_count_tokens_basic(): void
    {
        $response = $this->api->countTokens()
            ->model(Models::CLAUDE_3_5_HAIKU)
            ->messages([
                ['role' => 'user', 'content' => 'Hello, how are you?']
            ])
            ->count();

        $this->assertInstanceOf(TokenCountResponse::class, $response);
        $this->assertGreaterThan(0, $response->inputTokens);
    }

    public function test_count_tokens_with_system_prompt(): void
    {
        $response = $this->api->countTokens()
            ->model(Models::CLAUDE_3_5_HAIKU)
            ->system('You are a helpful assistant.')
            ->messages([
                ['role' => 'user', 'content' => 'Hi']
            ])
            ->count();

        $this->assertGreaterThan(0, $response->inputTokens);
    }

    public function test_count_tokens_with_tools(): void
    {
        $tools = [
            [
                'name' => 'get_weather',
                'description' => 'Get current weather for a location',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'location' => [
                            'type' => 'string',
                            'description' => 'The city name'
                        ]
                    ],
                    'required' => ['location']
                ]
            ]
        ];

        $response = $this->api->countTokens()
            ->model(Models::CLAUDE_3_5_HAIKU)
            ->messages([
                ['role' => 'user', 'content' => 'What is the weather in Paris?']
            ])
            ->tools($tools)
            ->count();

        $this->assertGreaterThan(0, $response->inputTokens);
    }

    public function test_count_tokens_longer_message(): void
    {
        $shortResponse = $this->api->countTokens()
            ->model(Models::CLAUDE_3_5_HAIKU)
            ->messages([
                ['role' => 'user', 'content' => 'Hi']
            ])
            ->count();

        $longResponse = $this->api->countTokens()
            ->model(Models::CLAUDE_3_5_HAIKU)
            ->messages([
                ['role' => 'user', 'content' => 'This is a much longer message that should result in more tokens being counted by the API.']
            ])
            ->count();

        $this->assertGreaterThan($shortResponse->inputTokens, $longResponse->inputTokens);
    }
}
