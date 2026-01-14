<?php

namespace WpAi\Anthropic\Resources;

use InvalidArgumentException;
use WpAi\Anthropic\Contracts\APIResource;
use WpAi\Anthropic\Responses\TokenCountResponse;

class TokenCountResource extends APIResource
{
    protected string $endpoint = 'messages/count_tokens';

    private string $model;

    private array $messages = [];

    private ?string $system = null;

    private ?array $tools = null;

    private ?array $toolChoice = null;

    private ?array $thinking = null;

    public function model(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    public function messages(array $messages): self
    {
        $this->messages = $messages;
        return $this;
    }

    public function system(string $system): self
    {
        $this->system = $system;
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

    public function thinking(array $thinking): self
    {
        $this->thinking = $thinking;
        return $this;
    }

    public function count(): TokenCountResponse
    {
        $this->validate();

        $request = $this->getRequest();
        $response = $this->client->post($this->endpoint, $request);

        return new TokenCountResponse($response);
    }

    private function getRequest(): array
    {
        // Use callback to preserve valid falsy values
        $optional = array_filter([
            'system' => $this->system,
            'tools' => $this->tools,
            'tool_choice' => $this->toolChoice,
            'thinking' => $this->thinking,
        ], fn($value) => $value !== null);

        return [
            'model' => $this->model,
            'messages' => $this->messages,
            ...$optional,
        ];
    }

    private function validate(): void
    {
        if (empty($this->model)) {
            throw new InvalidArgumentException('Model is required.');
        }
        if (empty($this->messages)) {
            throw new InvalidArgumentException('Messages are required.');
        }
    }
}
