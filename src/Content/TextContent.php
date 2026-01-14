<?php

namespace WpAi\Anthropic\Content;

class TextContent implements ContentBlock
{
    private ?array $cacheControl = null;

    public function __construct(
        private string $text
    ) {}

    public static function make(string $text): self
    {
        return new self($text);
    }

    public function withCache(string $ttl = '5m'): self
    {
        $this->cacheControl = [
            'type' => 'ephemeral',
            'ttl' => $ttl,
        ];
        return $this;
    }

    public function toArray(): array
    {
        $data = [
            'type' => 'text',
            'text' => $this->text,
        ];

        if ($this->cacheControl !== null) {
            $data['cache_control'] = $this->cacheControl;
        }

        return $data;
    }
}
