<?php

namespace WpAi\Anthropic\Responses;

use Psr\Http\Message\ResponseInterface;

class MessageResponse extends Response
{
    public string $id;

    public string $type;

    public string $role;

    public array $content;

    public string $model;

    public ?string $stopReason;

    public ?string $stopSequence;

    public Usage $usage;

    public ?array $toolCalls;

    public function __construct(protected ResponseInterface $response)
    {
        $data = json_decode($this->response->getBody()->getContents(), true);

        $this->id = $data['id'];
        $this->type = $data['type'];
        $this->role = $data['role'];
        $this->content = $data['content'];
        $this->model = $data['model'];
        $this->stopReason = $data['stop_reason'];
        $this->stopSequence = $data['stop_sequence'];
        $this->usage = new Usage($data['usage']);
        $this->toolCalls = $this->extractToolCalls($data['content']);
    }

    /**
     * Get the text content from the response.
     * Returns concatenated text from all text blocks.
     */
    public function getText(): string
    {
        $texts = [];
        foreach ($this->content as $block) {
            if (isset($block['type']) && $block['type'] === 'text') {
                $texts[] = $block['text'];
            }
        }
        return implode("\n", $texts);
    }

    /**
     * Get all thinking blocks from the response.
     */
    public function getThinkingBlocks(): array
    {
        return array_filter($this->content, function ($block) {
            return isset($block['type']) && $block['type'] === 'thinking';
        });
    }

    /**
     * Get the thinking text (concatenated from all thinking blocks).
     */
    public function getThinking(): ?string
    {
        $thinking = [];
        foreach ($this->content as $block) {
            if (isset($block['type']) && $block['type'] === 'thinking') {
                $thinking[] = $block['thinking'];
            }
        }
        return empty($thinking) ? null : implode("\n", $thinking);
    }

    /**
     * Get all text blocks from the response.
     */
    public function getTextBlocks(): array
    {
        return array_filter($this->content, function ($block) {
            return isset($block['type']) && $block['type'] === 'text';
        });
    }

    /**
     * Get all tool use blocks from the response.
     */
    public function getToolUseBlocks(): array
    {
        return array_filter($this->content, function ($block) {
            return isset($block['type']) && $block['type'] === 'tool_use';
        });
    }

    /**
     * Check if the response contains tool use.
     */
    public function hasToolUse(): bool
    {
        return $this->stopReason === 'tool_use' || !empty($this->getToolUseBlocks());
    }

    /**
     * Check if the response has thinking content.
     */
    public function hasThinking(): bool
    {
        return !empty($this->getThinkingBlocks());
    }

    /**
     * Get all citations from text blocks.
     */
    public function getCitations(): array
    {
        $citations = [];
        foreach ($this->content as $block) {
            if (isset($block['type']) && $block['type'] === 'text' && isset($block['citations'])) {
                $citations = array_merge($citations, $block['citations']);
            }
        }
        return $citations;
    }

    /**
     * Check if the model refused to respond.
     */
    public function isRefusal(): bool
    {
        return $this->stopReason === 'refusal';
    }

    /**
     * Extract tool calls from content blocks (legacy compatibility).
     */
    private function extractToolCalls(array $content): ?array
    {
        $toolCalls = [];
        foreach ($content as $block) {
            if (isset($block['type']) && $block['type'] === 'tool_use') {
                $toolCalls[] = $block;
            }
        }
        return empty($toolCalls) ? null : $toolCalls;
    }
}

class Usage
{
    public int $inputTokens;

    public int $outputTokens;

    public int $cacheCreationInputTokens;

    public int $cacheReadInputTokens;

    public function __construct(array $data)
    {
        $this->inputTokens = $data['input_tokens'];
        $this->outputTokens = $data['output_tokens'];
        $this->cacheCreationInputTokens = $data['cache_creation_input_tokens'] ?? 0;
        $this->cacheReadInputTokens = $data['cache_read_input_tokens'] ?? 0;
    }

    /**
     * Get total tokens used.
     */
    public function totalTokens(): int
    {
        return $this->inputTokens + $this->outputTokens;
    }

    /**
     * Check if caching was used.
     */
    public function usedCache(): bool
    {
        return $this->cacheReadInputTokens > 0 || $this->cacheCreationInputTokens > 0;
    }
}
