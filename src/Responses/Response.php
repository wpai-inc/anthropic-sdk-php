<?php

namespace WpAi\Anthropic\Responses;

abstract class Response
{
    public function __construct(protected array $data)
    {
    }
}
