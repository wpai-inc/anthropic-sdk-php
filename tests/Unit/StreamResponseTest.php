<?php

namespace WpAi\Anthropic\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WpAi\Anthropic\Exceptions\StreamException;
use WpAi\Anthropic\Responses\StreamResponse;

class StreamResponseTest extends TestCase
{
    private function createMockStreamResponse(string $streamContent): StreamResponse
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $streamContent);
        rewind($stream);

        $body = $this->createMock(\Psr\Http\Message\StreamInterface::class);

        $position = 0;
        $body->method('eof')->willReturnCallback(function () use (&$position, $streamContent) {
            return $position >= strlen($streamContent);
        });
        $body->method('read')->willReturnCallback(function ($length) use (&$position, $streamContent) {
            $result = substr($streamContent, $position, $length);
            $position += $length;
            return $result !== false ? $result : '';
        });

        $response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $response->method('getBody')->willReturn($body);

        return new StreamResponse($response);
    }

    public function test_parses_text_delta_events(): void
    {
        $streamContent = <<<EOT
event: message_start
data: {"type":"message_start","message":{"id":"msg_1","type":"message","role":"assistant","content":[],"model":"claude-3-5-haiku","usage":{"input_tokens":10,"output_tokens":0}}}

event: content_block_start
data: {"type":"content_block_start","index":0,"content_block":{"type":"text","text":""}}

event: content_block_delta
data: {"type":"content_block_delta","index":0,"delta":{"type":"text_delta","text":"Hello"}}

event: content_block_delta
data: {"type":"content_block_delta","index":0,"delta":{"type":"text_delta","text":" world"}}

event: content_block_stop
data: {"type":"content_block_stop","index":0}

event: message_delta
data: {"type":"message_delta","delta":{"stop_reason":"end_turn"},"usage":{"output_tokens":5}}

event: message_stop
data: {"type":"message_stop"}

data: [DONE]

EOT;

        $stream = $this->createMockStreamResponse($streamContent);

        $chunks = [];
        foreach ($stream as $chunk) {
            $chunks[] = $chunk;
        }

        $this->assertNotEmpty($chunks);

        // Find text_delta events
        $textDeltas = array_filter($chunks, function ($chunk) {
            return $chunk['type'] === 'content_block_delta'
                && isset($chunk['delta']['type'])
                && $chunk['delta']['type'] === 'text_delta';
        });

        $this->assertCount(2, $textDeltas);
    }

    public function test_parses_thinking_delta_events(): void
    {
        $streamContent = <<<EOT
data: {"type":"content_block_start","index":0,"content_block":{"type":"thinking"}}

data: {"type":"content_block_delta","index":0,"delta":{"type":"thinking_delta","thinking":"Let me think..."}}

data: {"type":"content_block_delta","index":0,"delta":{"type":"signature_delta","signature":"abc123"}}

data: {"type":"content_block_stop","index":0}

data: [DONE]

EOT;

        $stream = $this->createMockStreamResponse($streamContent);

        $chunks = [];
        foreach ($stream as $chunk) {
            $chunks[] = $chunk;
        }

        // Find thinking_delta event
        $thinkingDeltas = array_filter($chunks, function ($chunk) {
            return $chunk['type'] === 'content_block_delta'
                && isset($chunk['delta']['type'])
                && $chunk['delta']['type'] === 'thinking_delta';
        });

        $this->assertCount(1, $thinkingDeltas);
        $this->assertEquals('Let me think...', array_values($thinkingDeltas)[0]['delta']['thinking']);

        // Find signature_delta event
        $signatureDeltas = array_filter($chunks, function ($chunk) {
            return $chunk['type'] === 'content_block_delta'
                && isset($chunk['delta']['type'])
                && $chunk['delta']['type'] === 'signature_delta';
        });

        $this->assertCount(1, $signatureDeltas);
    }

    public function test_throws_stream_exception_on_error(): void
    {
        $streamContent = <<<EOT
data: {"type":"error","error":{"type":"overloaded_error","message":"Service temporarily overloaded"}}

EOT;

        $stream = $this->createMockStreamResponse($streamContent);

        $this->expectException(StreamException::class);

        foreach ($stream as $chunk) {
            // Should throw before completing
        }
    }

    public function test_stops_on_done_marker(): void
    {
        $streamContent = <<<EOT
data: {"type":"content_block_delta","index":0,"delta":{"type":"text_delta","text":"Hello"}}

data: [DONE]

data: {"type":"content_block_delta","index":0,"delta":{"type":"text_delta","text":"This should not appear"}}

EOT;

        $stream = $this->createMockStreamResponse($streamContent);

        $chunks = [];
        foreach ($stream as $chunk) {
            $chunks[] = $chunk;
        }

        // Should only have one chunk (before [DONE])
        $this->assertCount(1, $chunks);
    }

    public function test_skips_non_data_lines(): void
    {
        $streamContent = <<<EOT
: this is a comment
event: message_start

data: {"type":"message_start","message":{"id":"msg_1"}}

retry: 1000

data: {"type":"message_stop"}

data: [DONE]

EOT;

        $stream = $this->createMockStreamResponse($streamContent);

        $chunks = [];
        foreach ($stream as $chunk) {
            $chunks[] = $chunk;
        }

        // Should only have 2 chunks (the two data: lines)
        $this->assertCount(2, $chunks);
    }

    public function test_parses_tool_use_input_json_delta(): void
    {
        $streamContent = <<<EOT
data: {"type":"content_block_start","index":0,"content_block":{"type":"tool_use","id":"toolu_1","name":"get_weather"}}

data: {"type":"content_block_delta","index":0,"delta":{"type":"input_json_delta","partial_json":"{\"location\":"}}

data: {"type":"content_block_delta","index":0,"delta":{"type":"input_json_delta","partial_json":"\"Paris\"}"}}

data: {"type":"content_block_stop","index":0}

data: [DONE]

EOT;

        $stream = $this->createMockStreamResponse($streamContent);

        $chunks = [];
        foreach ($stream as $chunk) {
            $chunks[] = $chunk;
        }

        // Find input_json_delta events
        $jsonDeltas = array_filter($chunks, function ($chunk) {
            return $chunk['type'] === 'content_block_delta'
                && isset($chunk['delta']['type'])
                && $chunk['delta']['type'] === 'input_json_delta';
        });

        $this->assertCount(2, $jsonDeltas);
    }
}
