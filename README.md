# Anthropic PHP SDK

This library provides convenient access to the Anthropic REST API from server-side PHP

## Installation

```sh
composer require wpai-inc/anthropic-sdk-php
```

## Usage

Set your API key in the constructor:

```php
$anthropic = new \WpAi\Anthropic\AnthropicAPI($apiKey);
```

### Messages Resource

The only resource available is `/messages`. Note, `/completions` is deprecated and so isn't available in this library.

```php
$messages = [
    [
        'role' => 'user',
        'content' => 'How can you help me?',
    ],
];
$options = [
    'model' => 'claude-3-opus-20240229',
    'maxTokens' => 1024,
    'messages' => $messages,
];

$anthropic->messages()->create($options);
```

The options above are required. You may also set them fluently like this:

```php
$anthropic->messages()->maxTokens(2048)->create($messages);
```

All other optional [options](https://docs.anthropic.com/claude/reference/messages_post) can be set in the same ways.

To include additional HTTP headers in the request, such as those required for enabling extended token limits, pass an array as the second argument to the `create()` method. For example, to enable support for 8192 max tokens:

```php
$response = Anthropic::messages()->create($query, [ 'anthropic-beta' => 'max-tokens-3-5-sonnet-2024-07-15' ]);
```

#### Stream

A streamed response follows all of the same options as `create()` but may be invoked with:

```php
$anthropic->messages()->stream($options);

while (! $stream->eof()) {
    echo $stream->read(1024);
    ob_flush();
    flush();
}
```

You may set extra HTTP headers by passing an array as a second argument to `stream()`.

## Laravel

This library may also be used in Laravel.

```php
use WpAi\Anthropic\Facades\Anthropic;

// Create a message
$response = Anthropic::messages()
    ->model('claude-v1')
    ->maxTokens(100)
    ->messages([
        ['role' => 'user', 'content' => 'Hello, Claude!'],
    ])
    ->create();

// Stream a message
$stream = Anthropic::messages()
    ->model('claude-v1')
    ->maxTokens(100)
    ->messages([
        ['role' => 'user', 'content' => 'Tell me a story.'],
    ])
    ->stream();
```

Publish the config with:

```sh
php artisan vendor:publish --provider="WpAi\Anthropic\Providers\AnthropicServiceProvider"
```
