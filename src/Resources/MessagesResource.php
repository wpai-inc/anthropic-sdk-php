<?php

namespace ErsinDemirtas\Anthropic\Resources;

use InvalidArgumentException;
use ErsinDemirtas\Anthropic\Contracts\APIResource;
use ErsinDemirtas\Anthropic\Responses\MessageResponse;
use ErsinDemirtas\Anthropic\Responses\Response;
use ErsinDemirtas\Anthropic\Responses\StreamResponse;

class MessagesResource extends APIResource
{
    protected string $endpoint = 'messages';

    private string $model;

    private int $maxTokens;

    private array $messages;

    private ?string $system = null;

    private ?array $metadata = null;

    private ?float $temperature = null;

    private ?float $topP = null;

    private ?int $topK = null;

    public function model(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function maxTokens(int $maxTokens): self
    {
        if ($maxTokens <= 0) {
            throw new InvalidArgumentException('Max tokens must be a positive integer.');
        }
        $this->maxTokens = $maxTokens;

        return $this;
    }

    public function messages(array $messages): self
    {
        foreach ($messages as $message) {
            if (! isset($message['role']) || ! isset($message['content'])) {
                throw new InvalidArgumentException('Each message must have a "role" and "content" key.');
            }
            if (! is_string($message['role']) || ! is_string($message['content'])) {
                throw new InvalidArgumentException('Message "role" and "content" must be strings.');
            }
        }
        $this->messages = $messages;

        return $this;
    }

    public function system(string $system): self
    {
        $this->system = $system;

        return $this;
    }

    public function metadata(array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function temperature(float $temperature): self
    {
        if ($temperature < 0.0 || $temperature > 1.0) {
            throw new InvalidArgumentException('Temperature must be between 0.0 and 1.0.');
        }
        $this->temperature = $temperature;

        return $this;
    }

    public function topP(float $topP): self
    {
        $this->topP = $topP;

        return $this;
    }

    public function topK(int $topK): self
    {
        $this->topK = $topK;

        return $this;
    }

    public function create(array $options = []): Response
    {
        $this->validateOptions($options);
        $res = $this->client->post($this->endpoint, $this->request());

        return new MessageResponse($res);
    }

    public function stream(array $options = []): StreamResponse
    {
        $this->validateOptions($options);

        return $this->client->stream($this->endpoint, [
            ...$this->request(),
            'stream' => true,
        ]);
    }

    private function request(): array
    {
        $optional = array_filter([
            'system' => $this->system,
            'metadata' => $this->metadata,
            'temperature' => $this->temperature,
            'top_p' => $this->topP,
            'top_k' => $this->topK,
        ]);

        return [
            'model' => $this->model,
            'max_tokens' => $this->maxTokens,
            'messages' => $this->messages,
            ...$optional,
        ];
    }

    private function validateOptions(array $options = []): void
    {
        $this->model = $options['model'] ?? $this->model;
        $this->maxTokens = $options['max_tokens'] ?? $this->maxTokens;
        $this->messages = $options['messages'] ?? $this->messages;
        $this->system = $options['system'] ?? $this->system;
        $this->metadata = $options['metadata'] ?? $this->metadata;
        $this->temperature = $options['temperature'] ?? $this->temperature;
        $this->topP = $options['top_p'] ?? $this->topP;
        $this->topK = $options['top_k'] ?? $this->topK;

        if (empty($this->model)) {
            throw new InvalidArgumentException('Model is required.');
        }
        if (! isset($this->maxTokens)) {
            throw new InvalidArgumentException('Max tokens is required.');
        }
        if (empty($this->messages)) {
            throw new InvalidArgumentException('Messages are required.');
        }
    }
}
