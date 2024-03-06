<?php

namespace WpAi\Anthropic;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\StreamInterface;
use WpAi\Anthropic\Exceptions\ClientException as AnthropicClientException;
use WpAi\Anthropic\Responses\ErrorResponse;

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

    public function stream(string $endpoint, array $args): StreamInterface
    {
        try {
            $response = $this->client->post($endpoint, [
                'json' => $args,
                'stream' => true,
            ]);

            return $response->getBody();
        } catch (RequestException $e) {
            $this->badRequest($e);
        }
    }

    private function badRequest(RequestException $e): void
    {
        $response = $e->getResponse();
        $data = json_decode($response->getBody()->getContents(), true);
        $error = (new ErrorResponse($data))->getError();

        throw new AnthropicClientException($error->getMessage(), $response->getStatusCode(), $e);
    }
}
