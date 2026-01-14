<?php

namespace WpAi\Anthropic\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WpAi\Anthropic\AnthropicAPI;
use WpAi\Anthropic\Models;

class MessagesResourceTest extends TestCase
{
    private AnthropicAPI $api;

    protected function setUp(): void
    {
        $this->api = new AnthropicAPI('test-api-key');
    }

    public function test_can_set_model(): void
    {
        $resource = $this->api->messages()->model(Models::CLAUDE_3_5_HAIKU);

        $this->assertInstanceOf(\WpAi\Anthropic\Resources\MessagesResource::class, $resource);
    }

    public function test_can_set_max_tokens(): void
    {
        $resource = $this->api->messages()->maxTokens(1024);

        $this->assertInstanceOf(\WpAi\Anthropic\Resources\MessagesResource::class, $resource);
    }

    public function test_max_tokens_must_be_positive(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->api->messages()->maxTokens(0);
    }

    public function test_can_set_messages_with_string_content(): void
    {
        $resource = $this->api->messages()->messages([
            ['role' => 'user', 'content' => 'Hello']
        ]);

        $this->assertInstanceOf(\WpAi\Anthropic\Resources\MessagesResource::class, $resource);
    }

    public function test_can_set_messages_with_array_content(): void
    {
        $resource = $this->api->messages()->messages([
            [
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => 'What is in this image?'],
                    ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => 'image/jpeg', 'data' => 'abc']]
                ]
            ]
        ]);

        $this->assertInstanceOf(\WpAi\Anthropic\Resources\MessagesResource::class, $resource);
    }

    public function test_messages_require_role(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->api->messages()->messages([
            ['content' => 'Hello']
        ]);
    }

    public function test_messages_require_content(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->api->messages()->messages([
            ['role' => 'user']
        ]);
    }

    public function test_can_set_temperature(): void
    {
        $resource = $this->api->messages()->temperature(0.7);

        $this->assertInstanceOf(\WpAi\Anthropic\Resources\MessagesResource::class, $resource);
    }

    public function test_temperature_must_be_between_0_and_1(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->api->messages()->temperature(1.5);
    }

    public function test_can_set_service_tier(): void
    {
        $resource = $this->api->messages()->serviceTier('auto');

        $this->assertInstanceOf(\WpAi\Anthropic\Resources\MessagesResource::class, $resource);
    }

    public function test_service_tier_must_be_valid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->api->messages()->serviceTier('invalid');
    }

    public function test_can_set_thinking(): void
    {
        $resource = $this->api->messages()->thinking([
            'type' => 'enabled',
            'budget_tokens' => 2000
        ]);

        $this->assertInstanceOf(\WpAi\Anthropic\Resources\MessagesResource::class, $resource);
    }

    public function test_thinking_budget_must_be_at_least_1024(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->api->messages()->thinking([
            'type' => 'enabled',
            'budget_tokens' => 500
        ]);
    }

    public function test_can_add_beta_headers(): void
    {
        $resource = $this->api->messages()
            ->withBeta('files-api-2025-04-14')
            ->withBeta('advanced-tool-use-2025-11-20');

        $this->assertInstanceOf(\WpAi\Anthropic\Resources\MessagesResource::class, $resource);
    }

    public function test_can_set_stop_sequences(): void
    {
        $resource = $this->api->messages()->stopSequences(['STOP', 'END']);

        $this->assertInstanceOf(\WpAi\Anthropic\Resources\MessagesResource::class, $resource);
    }

    public function test_get_request_includes_all_parameters(): void
    {
        $resource = $this->api->messages()
            ->model(Models::CLAUDE_3_5_HAIKU)
            ->maxTokens(1024)
            ->messages([['role' => 'user', 'content' => 'Hello']])
            ->system('You are helpful')
            ->temperature(0.7)
            ->topP(0.9)
            ->topK(40)
            ->serviceTier('auto')
            ->thinking(['type' => 'enabled', 'budget_tokens' => 2000])
            ->stopSequences(['STOP']);

        $request = $resource->getRequest();

        $this->assertEquals(Models::CLAUDE_3_5_HAIKU, $request['model']);
        $this->assertEquals(1024, $request['max_tokens']);
        $this->assertEquals([['role' => 'user', 'content' => 'Hello']], $request['messages']);
        $this->assertEquals('You are helpful', $request['system']);
        $this->assertEquals(0.7, $request['temperature']);
        $this->assertEquals(0.9, $request['top_p']);
        $this->assertEquals(40, $request['top_k']);
        $this->assertEquals('auto', $request['service_tier']);
        $this->assertEquals(['type' => 'enabled', 'budget_tokens' => 2000], $request['thinking']);
        $this->assertEquals(['STOP'], $request['stop_sequences']);
    }

    public function test_tool_choice_string_converts_to_array(): void
    {
        $resource = $this->api->messages()
            ->model(Models::CLAUDE_3_5_HAIKU)
            ->maxTokens(1024)
            ->messages([['role' => 'user', 'content' => 'Hello']])
            ->toolChoice('auto');

        $request = $resource->getRequest();

        $this->assertEquals(['type' => 'auto'], $request['tool_choice']);
    }
}
