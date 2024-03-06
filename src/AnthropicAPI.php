<?php

namespace WpAi\Anthropic;

use WpAi\Anthropic\Resources\MessagesResource;

class AnthropicAPI
{
    private string $version = '2023-06-01';

    private string $baseUrl = 'https://api.anthropic.com/v1/';

    private Client $client;

    public function __construct(private string $apiKey, string $apiVersion = null)
    {
        if ($apiVersion) {
            $this->version = $apiVersion;
        }
        
        $this->client = new Client($this->baseUrl, $this->headers());
    }

    public function messages(): MessagesResource
    {
        return new MessagesResource($this->client);
    }

    private function headers(): array
    {
        return [
            'x-api-key' => $this->apiKey,
            'content-type' => 'application/json',
            'anthropic-version' => $this->version,
        ];
    }
}
