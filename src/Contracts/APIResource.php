<?php

namespace ErsinDemirtas\Anthropic\Contracts;

use ErsinDemirtas\Anthropic\Client;

abstract class APIResource
{
    protected string $endpoint;

    public function __construct(protected Client $client)
    {
    }

    abstract public function create(array $options = []);
}
