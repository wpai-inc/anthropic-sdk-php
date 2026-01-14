<?php

namespace WpAi\Anthropic\Responses;

use Psr\Http\Message\ResponseInterface;

class BatchListResponse extends Response
{
    public array $data;

    public bool $hasMore;

    public ?string $firstId;

    public ?string $lastId;

    public function __construct(protected ResponseInterface $response)
    {
        $data = json_decode($this->response->getBody()->getContents(), true);

        $this->data = array_map(function ($item) {
            return new BatchItem($item);
        }, $data['data'] ?? []);
        $this->hasMore = $data['has_more'] ?? false;
        $this->firstId = $data['first_id'] ?? null;
        $this->lastId = $data['last_id'] ?? null;
    }
}

class BatchItem
{
    public string $id;

    public string $type;

    public string $processingStatus;

    public BatchRequestCounts $requestCounts;

    public string $createdAt;

    public ?string $endedAt;

    public ?string $expiresAt;

    public ?string $resultsUrl;

    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->type = $data['type'];
        $this->processingStatus = $data['processing_status'];
        $this->requestCounts = new BatchRequestCounts($data['request_counts']);
        $this->createdAt = $data['created_at'];
        $this->endedAt = $data['ended_at'] ?? null;
        $this->expiresAt = $data['expires_at'] ?? null;
        $this->resultsUrl = $data['results_url'] ?? null;
    }
}
