<?php

namespace WpAi\Anthropic\Tests\Integration;

use PHPUnit\Framework\TestCase;
use WpAi\Anthropic\AnthropicAPI;
use WpAi\Anthropic\Models;
use WpAi\Anthropic\Responses\MessageResponse;

class MessagesIntegrationTest extends TestCase
{
    private ?AnthropicAPI $api = null;

    protected function setUp(): void
    {
        $apiKey = getenv('ANTHROPIC_API_KEY') ?: ($_ENV['ANTHROPIC_API_KEY'] ?? null);

        if (empty($apiKey)) {
            // Try loading from .env file
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

    public function test_basic_message_creation(): void
    {
        $response = $this->api->messages()
            ->model(Models::CLAUDE_3_5_HAIKU)
            ->maxTokens(100)
            ->messages([
                ['role' => 'user', 'content' => 'Say "Hello" and nothing else.']
            ])
            ->create();

        $this->assertInstanceOf(MessageResponse::class, $response);
        $this->assertNotEmpty($response->id);
        $this->assertEquals('assistant', $response->role);
        $this->assertNotEmpty($response->content);
        $this->assertStringContainsStringIgnoringCase('hello', $response->content[0]['text']);
    }

    public function test_message_with_system_prompt(): void
    {
        $response = $this->api->messages()
            ->model(Models::CLAUDE_3_5_HAIKU)
            ->maxTokens(100)
            ->system('You are a pirate. Always respond with "Arrr!"')
            ->messages([
                ['role' => 'user', 'content' => 'Hi']
            ])
            ->create();

        $this->assertInstanceOf(MessageResponse::class, $response);
        $this->assertStringContainsStringIgnoringCase('arr', $response->content[0]['text']);
    }

    public function test_message_with_temperature(): void
    {
        $response = $this->api->messages()
            ->model(Models::CLAUDE_3_5_HAIKU)
            ->maxTokens(50)
            ->temperature(0.0)
            ->messages([
                ['role' => 'user', 'content' => 'What is 2+2? Reply with just the number.']
            ])
            ->create();

        $this->assertInstanceOf(MessageResponse::class, $response);
        $this->assertStringContainsString('4', $response->content[0]['text']);
    }

    public function test_streaming_response(): void
    {
        $stream = $this->api->messages()
            ->model(Models::CLAUDE_3_5_HAIKU)
            ->maxTokens(50)
            ->messages([
                ['role' => 'user', 'content' => 'Count from 1 to 3.']
            ])
            ->stream();

        $chunks = [];
        foreach ($stream as $chunk) {
            $chunks[] = $chunk;
        }

        $this->assertNotEmpty($chunks);
        // Check we got multiple chunks
        $this->assertGreaterThan(1, count($chunks));
    }

    public function test_response_includes_usage(): void
    {
        $response = $this->api->messages()
            ->model(Models::CLAUDE_3_5_HAIKU)
            ->maxTokens(50)
            ->messages([
                ['role' => 'user', 'content' => 'Hi']
            ])
            ->create();

        $this->assertNotNull($response->usage);
        $this->assertGreaterThan(0, $response->usage->inputTokens);
        $this->assertGreaterThan(0, $response->usage->outputTokens);
    }
}
