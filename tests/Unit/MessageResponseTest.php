<?php

namespace WpAi\Anthropic\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WpAi\Anthropic\Responses\MessageResponse;
use WpAi\Anthropic\Responses\Usage;

class MessageResponseTest extends TestCase
{
    private function createMockResponse(array $data): MessageResponse
    {
        $body = $this->createMock(\Psr\Http\Message\StreamInterface::class);
        $body->method('getContents')->willReturn(json_encode($data));

        $response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $response->method('getBody')->willReturn($body);

        return new MessageResponse($response);
    }

    public function test_parses_basic_response(): void
    {
        $response = $this->createMockResponse([
            'id' => 'msg_123',
            'type' => 'message',
            'role' => 'assistant',
            'content' => [
                ['type' => 'text', 'text' => 'Hello world']
            ],
            'model' => 'claude-3-5-haiku-20241022',
            'stop_reason' => 'end_turn',
            'stop_sequence' => null,
            'usage' => [
                'input_tokens' => 10,
                'output_tokens' => 20
            ]
        ]);

        $this->assertEquals('msg_123', $response->id);
        $this->assertEquals('assistant', $response->role);
        $this->assertEquals('end_turn', $response->stopReason);
        $this->assertEquals(10, $response->usage->inputTokens);
        $this->assertEquals(20, $response->usage->outputTokens);
    }

    public function test_get_text_returns_concatenated_text(): void
    {
        $response = $this->createMockResponse([
            'id' => 'msg_123',
            'type' => 'message',
            'role' => 'assistant',
            'content' => [
                ['type' => 'text', 'text' => 'First part. '],
                ['type' => 'text', 'text' => 'Second part.']
            ],
            'model' => 'claude-3-5-haiku-20241022',
            'stop_reason' => 'end_turn',
            'stop_sequence' => null,
            'usage' => ['input_tokens' => 10, 'output_tokens' => 20]
        ]);

        $this->assertEquals("First part. \nSecond part.", $response->getText());
    }

    public function test_parses_thinking_blocks(): void
    {
        $response = $this->createMockResponse([
            'id' => 'msg_123',
            'type' => 'message',
            'role' => 'assistant',
            'content' => [
                ['type' => 'thinking', 'thinking' => 'Let me think...', 'signature' => 'abc123'],
                ['type' => 'text', 'text' => 'The answer is 42.']
            ],
            'model' => 'claude-sonnet-4-5-20250929',
            'stop_reason' => 'end_turn',
            'stop_sequence' => null,
            'usage' => ['input_tokens' => 10, 'output_tokens' => 50]
        ]);

        $this->assertTrue($response->hasThinking());
        $this->assertEquals('Let me think...', $response->getThinking());
        $this->assertEquals('The answer is 42.', $response->getText());

        $thinkingBlocks = $response->getThinkingBlocks();
        $this->assertCount(1, $thinkingBlocks);
    }

    public function test_parses_tool_use_blocks(): void
    {
        $response = $this->createMockResponse([
            'id' => 'msg_123',
            'type' => 'message',
            'role' => 'assistant',
            'content' => [
                [
                    'type' => 'tool_use',
                    'id' => 'toolu_123',
                    'name' => 'get_weather',
                    'input' => ['location' => 'Paris']
                ]
            ],
            'model' => 'claude-3-5-haiku-20241022',
            'stop_reason' => 'tool_use',
            'stop_sequence' => null,
            'usage' => ['input_tokens' => 10, 'output_tokens' => 20]
        ]);

        $this->assertTrue($response->hasToolUse());
        $this->assertEquals('tool_use', $response->stopReason);

        $toolUseBlocks = $response->getToolUseBlocks();
        $this->assertCount(1, $toolUseBlocks);
        $this->assertEquals('get_weather', array_values($toolUseBlocks)[0]['name']);

        // Also check legacy toolCalls property
        $this->assertNotNull($response->toolCalls);
        $this->assertEquals('get_weather', $response->toolCalls[0]['name']);
    }

    public function test_parses_citations(): void
    {
        $response = $this->createMockResponse([
            'id' => 'msg_123',
            'type' => 'message',
            'role' => 'assistant',
            'content' => [
                [
                    'type' => 'text',
                    'text' => 'According to the document...',
                    'citations' => [
                        [
                            'type' => 'char_location',
                            'cited_text' => 'important fact',
                            'document_index' => 0,
                            'start_char_index' => 100,
                            'end_char_index' => 114
                        ]
                    ]
                ]
            ],
            'model' => 'claude-3-5-haiku-20241022',
            'stop_reason' => 'end_turn',
            'stop_sequence' => null,
            'usage' => ['input_tokens' => 10, 'output_tokens' => 20]
        ]);

        $citations = $response->getCitations();
        $this->assertCount(1, $citations);
        $this->assertEquals('important fact', $citations[0]['cited_text']);
    }

    public function test_is_refusal(): void
    {
        $response = $this->createMockResponse([
            'id' => 'msg_123',
            'type' => 'message',
            'role' => 'assistant',
            'content' => [['type' => 'text', 'text' => 'I cannot help with that.']],
            'model' => 'claude-3-5-haiku-20241022',
            'stop_reason' => 'refusal',
            'stop_sequence' => null,
            'usage' => ['input_tokens' => 10, 'output_tokens' => 20]
        ]);

        $this->assertTrue($response->isRefusal());
    }

    public function test_has_tool_use_without_stop_reason(): void
    {
        // Tool use can be present even without stop_reason='tool_use'
        $response = $this->createMockResponse([
            'id' => 'msg_123',
            'type' => 'message',
            'role' => 'assistant',
            'content' => [
                ['type' => 'text', 'text' => 'Let me check...'],
                ['type' => 'tool_use', 'id' => 'toolu_1', 'name' => 'search', 'input' => []]
            ],
            'model' => 'claude-3-5-haiku-20241022',
            'stop_reason' => 'end_turn',
            'stop_sequence' => null,
            'usage' => ['input_tokens' => 10, 'output_tokens' => 20]
        ]);

        $this->assertTrue($response->hasToolUse());
    }
}

class UsageTest extends TestCase
{
    public function test_parses_basic_usage(): void
    {
        $usage = new Usage([
            'input_tokens' => 100,
            'output_tokens' => 50
        ]);

        $this->assertEquals(100, $usage->inputTokens);
        $this->assertEquals(50, $usage->outputTokens);
        $this->assertEquals(150, $usage->totalTokens());
    }

    public function test_parses_cache_usage(): void
    {
        $usage = new Usage([
            'input_tokens' => 100,
            'output_tokens' => 50,
            'cache_creation_input_tokens' => 1000,
            'cache_read_input_tokens' => 500
        ]);

        $this->assertEquals(1000, $usage->cacheCreationInputTokens);
        $this->assertEquals(500, $usage->cacheReadInputTokens);
        $this->assertTrue($usage->usedCache());
    }

    public function test_used_cache_false_when_no_cache(): void
    {
        $usage = new Usage([
            'input_tokens' => 100,
            'output_tokens' => 50
        ]);

        $this->assertFalse($usage->usedCache());
        $this->assertEquals(0, $usage->cacheCreationInputTokens);
        $this->assertEquals(0, $usage->cacheReadInputTokens);
    }
}
