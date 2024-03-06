<?php

namespace WpAi\Anthropic;

use WpAi\Anthropic\Responses\ErrorResponse;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\StreamInterface;

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
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $data = json_decode($response->getBody()->getContents(), true);

            return new ErrorResponse($data);
        }

    }

    public function stream(string $endpoint, array $args): StreamInterface
    {
        $response = $this->client->post($endpoint, [
            'json' => $args,
            'stream' => true,
        ]);

        return $response->getBody();
    }
}
