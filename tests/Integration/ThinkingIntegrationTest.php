<?php

namespace WpAi\Anthropic\Tests\Integration;

use PHPUnit\Framework\TestCase;
use WpAi\Anthropic\AnthropicAPI;
use WpAi\Anthropic\Models;

class ThinkingIntegrationTest extends TestCase
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

    public function test_extended_thinking(): void
    {
        $response = $this->api->messages()
            ->model(Models::CLAUDE_SONNET_4_5)
            ->maxTokens(8000)
            ->thinking([
                'type' => 'enabled',
                'budget_tokens' => 5000
            ])
            ->messages([
                ['role' => 'user', 'content' => 'What is 15 * 23? Think through this step by step.']
            ])
            ->create();

        // Extended thinking should return thinking blocks
        $this->assertTrue($response->hasThinking());
        $thinking = $response->getThinking();
        $this->assertNotNull($thinking);
        $this->assertNotEmpty($thinking);

        // Should also have a text response with the answer
        $text = $response->getText();
        $this->assertStringContainsString('345', $text);
    }

    public function test_extended_thinking_streaming(): void
    {
        $stream = $this->api->messages()
            ->model(Models::CLAUDE_SONNET_4_5)
            ->maxTokens(8000)
            ->thinking([
                'type' => 'enabled',
                'budget_tokens' => 2000
            ])
            ->messages([
                ['role' => 'user', 'content' => 'What is 7 + 8?']
            ])
            ->stream();

        $hasThinkingDelta = false;
        $hasTextDelta = false;

        foreach ($stream as $chunk) {
            if (isset($chunk['type'])) {
                if ($chunk['type'] === 'content_block_delta') {
                    if (isset($chunk['delta']['type'])) {
                        if ($chunk['delta']['type'] === 'thinking_delta') {
                            $hasThinkingDelta = true;
                        }
                        if ($chunk['delta']['type'] === 'text_delta') {
                            $hasTextDelta = true;
                        }
                    }
                }
            }
        }

        $this->assertTrue($hasThinkingDelta, 'Expected thinking_delta events in stream');
        $this->assertTrue($hasTextDelta, 'Expected text_delta events in stream');
    }
}
