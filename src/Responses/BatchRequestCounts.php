<?php

namespace WpAi\Anthropic\Responses;

class BatchRequestCounts
{
    public int $processing;

    public int $succeeded;

    public int $errored;

    public int $canceled;

    public int $expired;

    public function __construct(array $data)
    {
        $this->processing = $data['processing'] ?? 0;
        $this->succeeded = $data['succeeded'] ?? 0;
        $this->errored = $data['errored'] ?? 0;
        $this->canceled = $data['canceled'] ?? 0;
        $this->expired = $data['expired'] ?? 0;
    }
}
