<?php

namespace WpAi\Anthropic\Responses;

use Psr\Http\Message\ResponseInterface;

class ErrorResponse extends Response
{
    private string $type;

    private ErrorDetails $error;

    public function __construct(protected ResponseInterface $response)
    {
        $data = json_decode($this->response->getBody()->getContents(), true);
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
