<?php

namespace WpAi\Anthropic\Facades;

use Illuminate\Support\Facades\Facade;

class Anthropic extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \WpAi\Anthropic\AnthropicAPI::class;
    }
}