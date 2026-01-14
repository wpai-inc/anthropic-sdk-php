<?php

namespace WpAi\Anthropic\Responses;

use Psr\Http\Message\ResponseInterface;

class TokenCountResponse extends Response
{
    public int $inputTokens;

    public function __construct(protected ResponseInterface $response)
    {
        $data = json_decode($this->response->getBody()->getContents(), true);
        $this->inputTokens = $data['input_tokens'];
    }
}
