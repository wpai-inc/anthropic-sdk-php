<?php

namespace ErsinDemirtas\Anthropic\Facades;

use Illuminate\Support\Facades\Facade;

class Anthropic extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \ErsinDemirtas\Anthropic\AnthropicAPI::class;
    }
}
