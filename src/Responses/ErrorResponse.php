<?php

namespace WpAi\Anthropic\Responses;

class ErrorResponse extends Response
{
    private string $type;

    private ErrorDetails $error;

    public function __construct(array $data)
    {
        $this->type = $data['type'];
        $this->error = new ErrorDetails($data['error']);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getError(): ErrorDetails
    {
        return $this->error;
    }
}

class ErrorDetails
{
    private string $type;

    private string $message;

    public function __construct(array $data)
    {
        $this->type = $data['type'];
        $this->message = $data['message'];
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
