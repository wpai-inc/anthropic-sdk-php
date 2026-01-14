<?php

namespace WpAi\Anthropic\Content;

use InvalidArgumentException;

class DocumentContent implements ContentBlock
{
    private string $sourceType;
    private ?string $mediaType = null;
    private ?string $data = null;
    private ?string $url = null;
    private ?string $title = null;
    private ?string $context = null;
    private ?array $citations = null;
    private ?array $cacheControl = null;

    private function __construct() {}

    /**
     * Create a document content block from a base64-encoded string.
     */
    public static function fromBase64(string $data, string $mediaType = 'application/pdf'): self
    {
        $instance = new self();
        $instance->sourceType = 'base64';
        $instance->data = $data;
        $instance->mediaType = $mediaType;

        return $instance;
    }

    /**
     * Create a document content block from a URL.
     */
    public static function fromUrl(string $url): self
    {
        $instance = new self();
        $instance->sourceType = 'url';
        $instance->url = $url;

        return $instance;
    }

    /**
     * Create a document content block from a file path.
     */
    public static function fromFile(string $path): self
    {
        if (!file_exists($path)) {
            throw new InvalidArgumentException("File not found: {$path}");
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimeType = match ($extension) {
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'md' => 'text/markdown',
            'csv' => 'text/csv',
            default => mime_content_type($path),
        };

        $data = base64_encode(file_get_contents($path));

        $instance = self::fromBase64($data, $mimeType);
        $instance->title = basename($path);

        return $instance;
    }

    /**
     * Set the document title.
     */
    public function title(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Set context for the document.
     */
    public function context(string $context): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Enable citations for this document.
     */
    public function withCitations(bool $enabled = true): self
    {
        $this->citations = ['enabled' => $enabled];
        return $this;
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
            'type' => 'document',
            'source' => $source,
        ];

        if ($this->title !== null) {
            $data['title'] = $this->title;
        }

        if ($this->context !== null) {
            $data['context'] = $this->context;
        }

        if ($this->citations !== null) {
            $data['citations'] = $this->citations;
        }

        if ($this->cacheControl !== null) {
            $data['cache_control'] = $this->cacheControl;
        }

        return $data;
    }
}
