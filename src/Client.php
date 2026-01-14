<?php

namespace WpAi\Anthropic;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
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

    public function post(string $endpoint, array $args, array $extraHeaders = []): ResponseInterface|ErrorResponse
    {
        try {
            return $this->client->post($endpoint, [
                'json' => $args,
                'headers' => array_merge($this->client->getConfig('headers'), $extraHeaders),
            ]);
        } catch (ConnectException $e) {
            // Network errors (DNS, timeout, connection refused) have no response
            throw new AnthropicClientException(
                'Connection error: ' . $e->getMessage(),
                0,
                $e
            );
        } catch (RequestException $e) {
            $this->handleRequestException($e);
        }
    }

    public function get(string $endpoint, array $query = [], array $extraHeaders = []): ResponseInterface|ErrorResponse
    {
        try {
            $options = [
                'headers' => array_merge($this->client->getConfig('headers'), $extraHeaders),
            ];
            if (!empty($query)) {
                $options['query'] = $query;
            }
            return $this->client->get($endpoint, $options);
        } catch (ConnectException $e) {
            throw new AnthropicClientException(
                'Connection error: ' . $e->getMessage(),
                0,
                $e
            );
        } catch (RequestException $e) {
            $this->handleRequestException($e);
        }
    }

    public function stream(string $endpoint, array $args, array $extraHeaders = []): StreamResponse
    {
        try {
            $response = $this->client->post($endpoint, [
                'json' => $args,
                'stream' => true,
                'headers' => array_merge($this->client->getConfig('headers'), $extraHeaders),
            ]);

            return new StreamResponse($response);
        } catch (ConnectException $e) {
            throw new AnthropicClientException(
                'Connection error: ' . $e->getMessage(),
                0,
                $e
            );
        } catch (RequestException $e) {
            $this->handleRequestException($e);
        }
    }

    private function handleRequestException(RequestException $e): never
    {
        $response = $e->getResponse();

        // Handle cases where there's no response (shouldn't happen after ConnectException catch, but be safe)
        if ($response === null) {
            throw new AnthropicClientException(
                'Request failed: ' . $e->getMessage(),
                0,
                $e
            );
        }

        $error = (new ErrorResponse($response))->getError();

        throw new AnthropicClientException($error->getMessage(), $response->getStatusCode(), $e);
    }
}
