<?php

namespace WpAi\Anthropic;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use WpAi\Anthropic\Exceptions\ClientException as AnthropicClientException;
use WpAi\Anthropic\Responses\ErrorResponse;
use WpAi\Anthropic\Responses\StreamResponse;

class Client
{
    private GuzzleClient $client;

    public function __construct(private string $baseUrl, private array $headers = [])
    {
        $this->client = new GuzzleClient([
            'base_uri' => $this->baseUrl,
            'headers' => $headers,
        ]);
    }

    public function post(string $endpoint, array $args): array|ErrorResponse
    {
        try {
            $response = $this->client->post($endpoint, [
                'json' => $args,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            $this->badRequest($e);
        }

    }

    public function stream(string $endpoint, array $args): StreamResponse
    {
        try {
            $response = $this->client->post($endpoint, [
                'json' => $args,
                'stream' => true,
            ]);

            return new StreamResponse($response);
        } catch (RequestException $e) {
            $this->badRequest($e);
        }
    }

    private function badRequest(RequestException $e): void
    {
        $response = $e->getResponse();
        $error = (new ErrorResponse($response))->getError();

        throw new AnthropicClientException($error->getMessage(), $response->getStatusCode(), $e);
    }
}
