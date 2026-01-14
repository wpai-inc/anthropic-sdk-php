<?php

namespace WpAi\Anthropic\Tests\Integration;

use PHPUnit\Framework\TestCase;
use WpAi\Anthropic\AnthropicAPI;
use WpAi\Anthropic\Models;
use WpAi\Anthropic\Responses\BatchListResponse;
use WpAi\Anthropic\Responses\BatchResponse;

class BatchesIntegrationTest extends TestCase
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

    public function test_create_batch(): void
    {
        $batch = $this->api->batches()->create([
            [
                'custom_id' => 'test-request-1',
                'params' => [
                    'model' => Models::CLAUDE_3_5_HAIKU,
                    'max_tokens' => 100,
                    'messages' => [
                        ['role' => 'user', 'content' => 'Say hello']
                    ]
                ]
            ],
            [
                'custom_id' => 'test-request-2',
                'params' => [
                    'model' => Models::CLAUDE_3_5_HAIKU,
                    'max_tokens' => 100,
                    'messages' => [
                        ['role' => 'user', 'content' => 'Say goodbye']
                    ]
                ]
            ]
        ]);

        $this->assertInstanceOf(BatchResponse::class, $batch);
        $this->assertNotEmpty($batch->id);
        $this->assertEquals('message_batch', $batch->type);
        $this->assertContains($batch->processingStatus, ['in_progress', 'ended']);
    }

    public function test_list_batches(): void
    {
        $batches = $this->api->batches()->list(['limit' => 5]);

        $this->assertInstanceOf(BatchListResponse::class, $batches);
        $this->assertIsArray($batches->data);
    }

    public function test_retrieve_batch(): void
    {
        // First create a batch
        $created = $this->api->batches()->create([
            [
                'custom_id' => 'retrieve-test-' . time(),
                'params' => [
                    'model' => Models::CLAUDE_3_5_HAIKU,
                    'max_tokens' => 50,
                    'messages' => [
                        ['role' => 'user', 'content' => 'Hi']
                    ]
                ]
            ]
        ]);

        // Then retrieve it
        $retrieved = $this->api->batches()->retrieve($created->id);

        $this->assertInstanceOf(BatchResponse::class, $retrieved);
        $this->assertEquals($created->id, $retrieved->id);
    }

    public function test_cancel_batch(): void
    {
        // Create a batch
        $batch = $this->api->batches()->create([
            [
                'custom_id' => 'cancel-test-' . time(),
                'params' => [
                    'model' => Models::CLAUDE_3_5_HAIKU,
                    'max_tokens' => 50,
                    'messages' => [
                        ['role' => 'user', 'content' => 'Hi']
                    ]
                ]
            ]
        ]);

        // Try to cancel it
        $canceled = $this->api->batches()->cancel($batch->id);

        $this->assertInstanceOf(BatchResponse::class, $canceled);
        // Status should be canceling or already ended
        $this->assertContains($canceled->processingStatus, ['canceling', 'ended']);
    }
}
