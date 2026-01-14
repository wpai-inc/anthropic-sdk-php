<?php

namespace WpAi\Anthropic\Resources;

use InvalidArgumentException;
use WpAi\Anthropic\Contracts\APIResource;
use WpAi\Anthropic\Responses\BatchListResponse;
use WpAi\Anthropic\Responses\BatchResponse;

class BatchesResource extends APIResource
{
    protected string $endpoint = 'messages/batches';

    /**
     * Create a new message batch.
     *
     * @param array $requests Array of batch requests, each with 'custom_id' and 'params'
     */
    public function create(array $requests): BatchResponse
    {
        if (empty($requests)) {
            throw new InvalidArgumentException('At least one request is required.');
        }

        foreach ($requests as $index => $request) {
            if (!isset($request['custom_id'])) {
                throw new InvalidArgumentException("Request at index {$index} is missing 'custom_id'.");
            }
            if (!isset($request['params'])) {
                throw new InvalidArgumentException("Request at index {$index} is missing 'params'.");
            }
        }

        $response = $this->client->post($this->endpoint, [
            'requests' => $requests,
        ]);

        return new BatchResponse($response);
    }

    /**
     * Retrieve a message batch by ID.
     */
    public function retrieve(string $batchId): BatchResponse
    {
        $response = $this->client->get("{$this->endpoint}/{$batchId}");

        return new BatchResponse($response);
    }

    /**
     * List all message batches.
     *
     * @param array $options Optional parameters: before_id, after_id, limit
     */
    public function list(array $options = []): BatchListResponse
    {
        $query = array_filter([
            'before_id' => $options['before_id'] ?? null,
            'after_id' => $options['after_id'] ?? null,
            'limit' => $options['limit'] ?? null,
        ]);

        $response = $this->client->get($this->endpoint, $query);

        return new BatchListResponse($response);
    }

    /**
     * Cancel a message batch.
     */
    public function cancel(string $batchId): BatchResponse
    {
        $response = $this->client->post("{$this->endpoint}/{$batchId}/cancel", []);

        return new BatchResponse($response);
    }

    /**
     * Get results from a completed batch.
     * Downloads and parses the JSONL results file.
     *
     * @return array Array of batch results
     */
    public function results(string $batchId): array
    {
        $batch = $this->retrieve($batchId);

        if ($batch->processingStatus !== 'ended') {
            throw new InvalidArgumentException('Batch has not finished processing yet.');
        }

        if (empty($batch->resultsUrl)) {
            throw new InvalidArgumentException('No results URL available.');
        }

        // Fetch the JSONL results
        $client = new \GuzzleHttp\Client();
        $response = $client->get($batch->resultsUrl);
        $content = $response->getBody()->getContents();

        // Parse JSONL
        $results = [];
        $lines = explode("\n", trim($content));
        foreach ($lines as $line) {
            if (!empty($line)) {
                $results[] = json_decode($line, true);
            }
        }

        return $results;
    }
}
