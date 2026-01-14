<?php

namespace WpAi\Anthropic\Resources;

use InvalidArgumentException;
use WpAi\Anthropic\Contracts\APIResource;
use WpAi\Anthropic\Responses\MessageResponse;
use WpAi\Anthropic\Responses\Response;
use WpAi\Anthropic\Responses\StreamResponse;

class MessagesResource extends APIResource
{
    protected string $endpoint = 'messages';

    private string $model;

    private int $maxTokens;

    private array $messages = [];

    private ?string $system = null;

    private ?array $metadata = null;

    private ?array $stopSequences = null;

    private ?float $temperature = null;

    private ?float $topP = null;

    private ?int $topK = null;

    private ?array $tools = null;

    private ?array $toolChoice = null;

    private ?string $serviceTier = null;

    private ?array $thinking = null;

    private array $betaHeaders = [];

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
            if (! is_string($message['role'])) {
                throw new InvalidArgumentException('Message "role" must be a string.');
            }
            // Content can be a string or an array of content blocks (for images, documents, etc.)
            if (! is_string($message['content']) && ! is_array($message['content'])) {
                throw new InvalidArgumentException('Message "content" must be a string or an array of content blocks.');
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

    public function tools(array $tools): self
    {
        $this->tools = $tools;
        return $this;
    }

    public function toolChoice(string|array $toolChoice): self
    {
        $this->toolChoice = is_string($toolChoice)
            ? ['type' => $toolChoice]
            : $toolChoice;
        return $this;
    }

    public function serviceTier(string $serviceTier): self
    {
        if (!in_array($serviceTier, ['auto', 'standard_only'])) {
            throw new InvalidArgumentException('Service tier must be "auto" or "standard_only".');
        }
        $this->serviceTier = $serviceTier;
        return $this;
    }

    public function thinking(array $thinking): self
    {
        if (isset($thinking['budget_tokens']) && $thinking['budget_tokens'] < 1024) {
            throw new InvalidArgumentException('Thinking budget_tokens must be at least 1024.');
        }
        $this->thinking = $thinking;
        return $this;
    }

    public function withBeta(string $beta): self
    {
        if (!in_array($beta, $this->betaHeaders)) {
            $this->betaHeaders[] = $beta;
        }
        return $this;
    }

    public function stopSequences(array $sequences): self
    {
        $this->stopSequences = $sequences;
        return $this;
    }

    public function create(array $options = [], array $extraHeaders = []): Response
    {
        $this->validateOptions($options);
        $headers = array_merge($this->getBetaHeaders(), $extraHeaders);
        $res = $this->client->post($this->endpoint, $this->getRequest(), $headers);

        return new MessageResponse($res);
    }

    public function stream(array $options = [], array $extraHeaders = []): StreamResponse
    {
        $this->validateOptions($options);
        $headers = array_merge($this->getBetaHeaders(), $extraHeaders);

        return $this->client->stream($this->endpoint, [
            ...$this->getRequest(),
            'stream' => true,
        ], $headers);
    }

    private function getBetaHeaders(): array
    {
        if (empty($this->betaHeaders)) {
            return [];
        }
        return ['anthropic-beta' => implode(',', $this->betaHeaders)];
    }

    public function getRequest(): array
    {
        // Use callback to preserve valid falsy values like temperature=0.0 or top_k=0
        $optional = array_filter([
            'system' => $this->system,
            'metadata' => $this->metadata,
            'stop_sequences' => $this->stopSequences,
            'temperature' => $this->temperature,
            'top_p' => $this->topP,
            'top_k' => $this->topK,
            'tools' => $this->tools,
            'tool_choice' => $this->toolChoice,
            'service_tier' => $this->serviceTier,
            'thinking' => $this->thinking,
        ], fn($value) => $value !== null);

        return [
            'model' => $this->model,
            'max_tokens' => $this->maxTokens,
            'messages' => $this->messages,
            ...$optional,
        ];
    }

    private function validateOptions(array $options = []): void
    {
        // Merge options with fluent-set values
        if (isset($options['model'])) {
            $this->model = $options['model'];
        }
        if (isset($options['max_tokens'])) {
            $this->maxTokens($options['max_tokens']); // Use setter for validation
        }
        if (isset($options['messages'])) {
            $this->messages($options['messages']); // Use setter for validation
        }
        if (isset($options['system'])) {
            $this->system = $options['system'];
        }
        if (isset($options['metadata'])) {
            $this->metadata = $options['metadata'];
        }
        if (isset($options['stop_sequences'])) {
            $this->stopSequences = $options['stop_sequences'];
        }
        if (isset($options['temperature'])) {
            $this->temperature($options['temperature']); // Use setter for validation
        }
        if (isset($options['top_p'])) {
            $this->topP = $options['top_p'];
        }
        if (isset($options['top_k'])) {
            $this->topK = $options['top_k'];
        }
        if (isset($options['tools'])) {
            $this->tools = $options['tools'];
        }
        if (isset($options['tool_choice'])) {
            $this->toolChoice($options['tool_choice']); // Use setter for normalization
        }
        if (isset($options['service_tier'])) {
            $this->serviceTier($options['service_tier']); // Use setter for validation
        }
        if (isset($options['thinking'])) {
            $this->thinking($options['thinking']); // Use setter for validation
        }

        // Final validation of required fields
        if (empty($this->model)) {
            throw new InvalidArgumentException('Model is required.');
        }
        if (!isset($this->maxTokens)) {
            throw new InvalidArgumentException('Max tokens is required.');
        }
        if (empty($this->messages)) {
            throw new InvalidArgumentException('Messages are required.');
        }
    }
}
