<?php

namespace WpAi\Anthropic\Exceptions;

use Exception;

class ClientException extends Exception
{
    public function __construct($message, $code)
    {
        parent::__construct($message, $code);
    }
}
