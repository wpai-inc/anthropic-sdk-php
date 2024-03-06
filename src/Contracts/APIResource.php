<?php

namespace WpAi\Anthropic\Contracts;

use WpAi\Anthropic\Client;

abstract class APIResource
{
    protected string $endpoint;

    public function __construct(protected Client $client)
    {
    }

    abstract public function create(array $options = []);
}
