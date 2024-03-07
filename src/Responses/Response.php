<?php

namespace WpAi\Anthropic\Responses;

use Psr\Http\Message\ResponseInterface;

abstract class Response
{
    public function __construct(protected ResponseInterface $response)
    {
    }
}
