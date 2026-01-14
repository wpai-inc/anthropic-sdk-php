<?php

namespace WpAi\Anthropic\Content;

class Message
{
    private string $role;
    private array $content = [];

    private function __construct(string $role)
    {
        $this->role = $role;
    }

    /**
     * Create a user message.
     */
    public static function user(): self
    {
        return new self('user');
    }

    /**
     * Create an assistant message.
     */
    public static function assistant(): self
    {
        return new self('assistant');
    }

    /**
     * Add text content to the message.
     */
    public function text(string $text): self
    {
        $this->content[] = TextContent::make($text);
        return $this;
    }

    /**
     * Add an image from base64 data.
     */
    public function imageBase64(string $data, string $mediaType): self
    {
        $this->content[] = ImageContent::fromBase64($data, $mediaType);
        return $this;
    }

    /**
     * Add an image from a URL.
     */
    public function imageUrl(string $url): self
    {
        $this->content[] = ImageContent::fromUrl($url);
        return $this;
    }

    /**
     * Add an image from a file path.
     */
    public function imageFile(string $path): self
    {
        $this->content[] = ImageContent::fromFile($path);
        return $this;
    }

    /**
     * Add a document from base64 data.
     */
    public function documentBase64(string $data, string $mediaType = 'application/pdf'): self
    {
        $this->content[] = DocumentContent::fromBase64($data, $mediaType);
        return $this;
    }

    /**
     * Add a document from a URL.
     */
    public function documentUrl(string $url): self
    {
        $this->content[] = DocumentContent::fromUrl($url);
        return $this;
    }

    /**
     * Add a document from a file path.
     */
    public function documentFile(string $path): self
    {
        $this->content[] = DocumentContent::fromFile($path);
        return $this;
    }

    /**
     * Add a raw content block.
     */
    public function content(ContentBlock|array $content): self
    {
        $this->content[] = $content;
        return $this;
    }

    /**
     * Convert the message to an array for the API.
     */
    public function toArray(): array
    {
        $content = array_map(function ($item) {
            if ($item instanceof ContentBlock) {
                return $item->toArray();
            }
            return $item;
        }, $this->content);

        // If there's only one text content, simplify to a string
        if (count($content) === 1 && isset($content[0]['type']) && $content[0]['type'] === 'text') {
            return [
                'role' => $this->role,
                'content' => $content[0]['text'],
            ];
        }

        return [
            'role' => $this->role,
            'content' => $content,
        ];
    }
}
