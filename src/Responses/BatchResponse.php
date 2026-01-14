<?php

namespace WpAi\Anthropic\Responses;

use Psr\Http\Message\ResponseInterface;

class BatchResponse extends Response
{
    public string $id;

    public string $type;

    public string $processingStatus;

    public BatchRequestCounts $requestCounts;

    public string $createdAt;

    public ?string $endedAt;

    public ?string $expiresAt;

    public ?string $cancelInitiatedAt;

    public ?string $archivedAt;

    public ?string $resultsUrl;

    public function __construct(protected ResponseInterface $response)
    {
        $data = json_decode($this->response->getBody()->getContents(), true);

        $this->id = $data['id'];
        $this->type = $data['type'];
        $this->processingStatus = $data['processing_status'];
        $this->requestCounts = new BatchRequestCounts($data['request_counts']);
        $this->createdAt = $data['created_at'];
        $this->endedAt = $data['ended_at'] ?? null;
        $this->expiresAt = $data['expires_at'] ?? null;
        $this->cancelInitiatedAt = $data['cancel_initiated_at'] ?? null;
        $this->archivedAt = $data['archived_at'] ?? null;
        $this->resultsUrl = $data['results_url'] ?? null;
    }

    /**
     * Check if the batch is still processing.
     */
    public function isProcessing(): bool
    {
        return $this->processingStatus === 'in_progress';
    }

    /**
     * Check if the batch has completed.
     */
    public function isComplete(): bool
    {
        return $this->processingStatus === 'ended';
    }

    /**
     * Check if the batch is being canceled.
     */
    public function isCanceling(): bool
    {
        return $this->processingStatus === 'canceling';
    }

    /**
     * Get the total number of requests.
     */
    public function totalRequests(): int
    {
        return $this->requestCounts->processing
            + $this->requestCounts->succeeded
            + $this->requestCounts->errored
            + $this->requestCounts->canceled
            + $this->requestCounts->expired;
    }
}
