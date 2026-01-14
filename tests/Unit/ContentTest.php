<?php

namespace WpAi\Anthropic\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WpAi\Anthropic\Content\DocumentContent;
use WpAi\Anthropic\Content\ImageContent;
use WpAi\Anthropic\Content\Message;
use WpAi\Anthropic\Content\TextContent;

class ContentTest extends TestCase
{
    public function test_text_content_to_array(): void
    {
        $content = TextContent::make('Hello world');

        $this->assertEquals([
            'type' => 'text',
            'text' => 'Hello world',
        ], $content->toArray());
    }

    public function test_text_content_with_cache(): void
    {
        $content = TextContent::make('Hello world')->withCache('1h');

        $array = $content->toArray();
        $this->assertEquals('text', $array['type']);
        $this->assertEquals('Hello world', $array['text']);
        $this->assertEquals(['type' => 'ephemeral', 'ttl' => '1h'], $array['cache_control']);
    }

    public function test_image_content_from_base64(): void
    {
        $content = ImageContent::fromBase64('aGVsbG8=', 'image/jpeg');

        $array = $content->toArray();
        $this->assertEquals('image', $array['type']);
        $this->assertEquals('base64', $array['source']['type']);
        $this->assertEquals('image/jpeg', $array['source']['media_type']);
        $this->assertEquals('aGVsbG8=', $array['source']['data']);
    }

    public function test_image_content_from_url(): void
    {
        $content = ImageContent::fromUrl('https://example.com/image.jpg');

        $array = $content->toArray();
        $this->assertEquals('image', $array['type']);
        $this->assertEquals('url', $array['source']['type']);
        $this->assertEquals('https://example.com/image.jpg', $array['source']['url']);
    }

    public function test_image_content_validates_media_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ImageContent::fromBase64('data', 'invalid/type');
    }

    public function test_image_content_with_cache(): void
    {
        $content = ImageContent::fromUrl('https://example.com/image.jpg')->withCache('5m');

        $array = $content->toArray();
        $this->assertArrayHasKey('cache_control', $array);
        $this->assertEquals('ephemeral', $array['cache_control']['type']);
    }

    public function test_document_content_from_base64(): void
    {
        $content = DocumentContent::fromBase64('cGRmZGF0YQ==', 'application/pdf');

        $array = $content->toArray();
        $this->assertEquals('document', $array['type']);
        $this->assertEquals('base64', $array['source']['type']);
        $this->assertEquals('application/pdf', $array['source']['media_type']);
    }

    public function test_document_content_from_url(): void
    {
        $content = DocumentContent::fromUrl('https://example.com/doc.pdf');

        $array = $content->toArray();
        $this->assertEquals('document', $array['type']);
        $this->assertEquals('url', $array['source']['type']);
        $this->assertEquals('https://example.com/doc.pdf', $array['source']['url']);
    }

    public function test_document_content_with_title_and_context(): void
    {
        $content = DocumentContent::fromUrl('https://example.com/doc.pdf')
            ->title('My Document')
            ->context('This is a test document');

        $array = $content->toArray();
        $this->assertEquals('My Document', $array['title']);
        $this->assertEquals('This is a test document', $array['context']);
    }

    public function test_document_content_with_citations(): void
    {
        $content = DocumentContent::fromUrl('https://example.com/doc.pdf')
            ->withCitations();

        $array = $content->toArray();
        $this->assertEquals(['enabled' => true], $array['citations']);
    }

    public function test_message_builder_user(): void
    {
        $message = Message::user()->text('Hello');

        $array = $message->toArray();
        $this->assertEquals('user', $array['role']);
        $this->assertEquals('Hello', $array['content']);
    }

    public function test_message_builder_with_multiple_content(): void
    {
        $message = Message::user()
            ->text('What is in this image?')
            ->imageUrl('https://example.com/image.jpg');

        $array = $message->toArray();
        $this->assertEquals('user', $array['role']);
        $this->assertIsArray($array['content']);
        $this->assertCount(2, $array['content']);
        $this->assertEquals('text', $array['content'][0]['type']);
        $this->assertEquals('image', $array['content'][1]['type']);
    }

    public function test_message_builder_assistant(): void
    {
        $message = Message::assistant()->text('I am Claude');

        $array = $message->toArray();
        $this->assertEquals('assistant', $array['role']);
    }
}
