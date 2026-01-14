<?php

namespace WpAi\Anthropic\Content;

use InvalidArgumentException;

class ImageContent implements ContentBlock
{
    private const VALID_MEDIA_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    private string $sourceType;
    private ?string $mediaType = null;
    private ?string $data = null;
    private ?string $url = null;
    private ?array $cacheControl = null;

    private function __construct() {}

    /**
     * Create an image content block from a base64-encoded string.
     */
    public static function fromBase64(string $data, string $mediaType): self
    {
        if (!in_array($mediaType, self::VALID_MEDIA_TYPES)) {
            throw new InvalidArgumentException(
                'Invalid media type. Must be one of: ' . implode(', ', self::VALID_MEDIA_TYPES)
            );
        }

        $instance = new self();
        $instance->sourceType = 'base64';
        $instance->data = $data;
        $instance->mediaType = $mediaType;

        return $instance;
    }

    /**
     * Create an image content block from a URL.
     */
    public static function fromUrl(string $url): self
    {
        $instance = new self();
        $instance->sourceType = 'url';
        $instance->url = $url;

        return $instance;
    }

    /**
     * Create an image content block from a file path.
     */
    public static function fromFile(string $path): self
    {
        if (!file_exists($path)) {
            throw new InvalidArgumentException("File not found: {$path}");
        }

        $mimeType = mime_content_type($path);
        if (!in_array($mimeType, self::VALID_MEDIA_TYPES)) {
            throw new InvalidArgumentException(
                "Invalid file type: {$mimeType}. Must be one of: " . implode(', ', self::VALID_MEDIA_TYPES)
            );
        }

        $data = base64_encode(file_get_contents($path));

        return self::fromBase64($data, $mimeType);
    }

    /**
     * Add cache control for prompt caching.
     */
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
        $source = match ($this->sourceType) {
            'base64' => [
                'type' => 'base64',
                'media_type' => $this->mediaType,
                'data' => $this->data,
            ],
            'url' => [
                'type' => 'url',
                'url' => $this->url,
            ],
        };

        $data = [
            'type' => 'image',
            'source' => $source,
        ];

        if ($this->cacheControl !== null) {
            $data['cache_control'] = $this->cacheControl;
        }

        return $data;
    }
}
