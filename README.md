# Anthropic PHP SDK

This library provides convenient access to the Anthropic REST API from server-side PHP.

## Requirements

- PHP 8.2 or higher
- Laravel 9.52+ / 10.x / 11.x / 12.x (optional, for Laravel integration)

## Installation

```sh
composer require wpai-inc/anthropic-sdk-php
```

## Quick Start

```php
use WpAi\Anthropic\AnthropicAPI;
use WpAi\Anthropic\Models;

$anthropic = new AnthropicAPI($apiKey);

$response = $anthropic->messages()
    ->model(Models::CLAUDE_3_5_HAIKU)
    ->maxTokens(1024)
    ->messages([
        ['role' => 'user', 'content' => 'Hello, Claude!']
    ])
    ->create();

echo $response->getText();
```

## Supported Models

Use the `Models` class for convenient access to all model identifiers:

```php
use WpAi\Anthropic\Models;

// Claude 4.5 Series (Latest)
Models::CLAUDE_OPUS_4_5         // claude-opus-4-5-20251101
Models::CLAUDE_SONNET_4_5       // claude-sonnet-4-5-20250929
Models::CLAUDE_HAIKU_4_5        // claude-haiku-4-5-20251001

// Claude 4 Series
Models::CLAUDE_OPUS_4           // claude-opus-4-20250514
Models::CLAUDE_SONNET_4         // claude-sonnet-4-20250514

// Claude 3.7 Series
Models::CLAUDE_3_7_SONNET       // claude-3-7-sonnet-20250219

// Claude 3.5 Series
Models::CLAUDE_3_5_SONNET       // claude-3-5-sonnet-20241022
Models::CLAUDE_3_5_HAIKU        // claude-3-5-haiku-20241022

// Helper methods
Models::recommended()  // Best balance of cost/performance (Sonnet 4.5)
Models::fast()         // Fastest/cheapest (Haiku 4.5)
Models::best()         // Most capable (Opus 4.5)
```

## Basic Usage

### Messages API

```php
$response = $anthropic->messages()
    ->model(Models::CLAUDE_3_5_HAIKU)
    ->maxTokens(2048)
    ->messages([
        ['role' => 'user', 'content' => 'How can you help me?']
    ])
    ->system('You are a helpful assistant')  // Optional system prompt
    ->temperature(0.7)                       // Controls randomness (0.0-1.0)
    ->topP(0.9)                             // Nucleus sampling
    ->topK(10)                              // Top-k sampling
    ->stopSequences(['STOP'])               // Custom stop sequences
    ->metadata(['user_id' => '123'])        // Custom metadata
    ->serviceTier('auto')                   // 'auto' or 'standard_only'
    ->create();

// Response properties
$response->id;                      // Message ID
$response->getText();               // Get concatenated text content
$response->content;                 // Array of content blocks
$response->usage->inputTokens;      // Input token count
$response->usage->outputTokens;     // Output token count
$response->stopReason;              // 'end_turn', 'max_tokens', 'tool_use', etc.
```

### Streaming

```php
$stream = $anthropic->messages()
    ->model(Models::CLAUDE_3_5_HAIKU)
    ->maxTokens(1024)
    ->messages([
        ['role' => 'user', 'content' => 'Tell me a story.']
    ])
    ->stream();

foreach ($stream as $chunk) {
    if ($chunk['type'] === 'content_block_delta') {
        echo $chunk['delta']['text'];
    }
}
```

## Vision (Image Analysis)

Analyze images using base64 data or URLs:

```php
use WpAi\Anthropic\Content\Message;
use WpAi\Anthropic\Content\ImageContent;

// Using the Message builder
$message = Message::user()
    ->text('What is in this image?')
    ->imageUrl('https://example.com/image.jpg');

$response = $anthropic->messages()
    ->model(Models::CLAUDE_3_5_HAIKU)
    ->maxTokens(1024)
    ->messages([$message->toArray()])
    ->create();

// Or with base64 data
$message = Message::user()
    ->text('Describe this image')
    ->imageBase64($base64Data, 'image/jpeg');

// Or load from file
$message = Message::user()
    ->text('What do you see?')
    ->imageFile('/path/to/image.jpg');
```

Supported formats: `image/jpeg`, `image/png`, `image/gif`, `image/webp`

## PDF & Document Support

Analyze PDF documents:

```php
use WpAi\Anthropic\Content\Message;
use WpAi\Anthropic\Content\DocumentContent;

// From URL
$message = Message::user()
    ->text('Summarize this document')
    ->documentUrl('https://example.com/document.pdf');

// From file
$message = Message::user()
    ->text('What are the key points?')
    ->documentFile('/path/to/document.pdf');

// With title, context, and citations
$doc = DocumentContent::fromFile('/path/to/doc.pdf')
    ->title('Annual Report')
    ->context('This is our Q4 financial report')
    ->withCitations();

$response = $anthropic->messages()
    ->model(Models::CLAUDE_3_5_HAIKU)
    ->maxTokens(4096)
    ->messages([
        [
            'role' => 'user',
            'content' => [
                ['type' => 'text', 'text' => 'Analyze this report'],
                $doc->toArray()
            ]
        ]
    ])
    ->create();

// Access citations
$citations = $response->getCitations();
```

## Extended Thinking

Enable Claude to show its reasoning process:

```php
$response = $anthropic->messages()
    ->model(Models::CLAUDE_SONNET_4_5)  // Supported on 4.5 models
    ->maxTokens(16000)
    ->thinking([
        'type' => 'enabled',
        'budget_tokens' => 5000  // Minimum 1024
    ])
    ->messages([
        ['role' => 'user', 'content' => 'Solve this complex problem...']
    ])
    ->create();

// Access thinking content
if ($response->hasThinking()) {
    echo "Thinking:\n" . $response->getThinking();
}
echo "\nAnswer:\n" . $response->getText();
```

## Tool Use

Define and use custom tools:

```php
$tools = [
    [
        'name' => 'get_weather',
        'description' => 'Get current weather for a location',
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'location' => [
                    'type' => 'string',
                    'description' => 'City name'
                ]
            ],
            'required' => ['location']
        ]
    ]
];

$response = $anthropic->messages()
    ->model(Models::CLAUDE_3_5_HAIKU)
    ->maxTokens(1024)
    ->tools($tools)
    ->toolChoice('auto')  // 'auto', 'any', 'none', or specific tool
    ->messages([
        ['role' => 'user', 'content' => 'What\'s the weather in Paris?']
    ])
    ->create();

if ($response->hasToolUse()) {
    foreach ($response->getToolUseBlocks() as $toolUse) {
        echo "Tool: {$toolUse['name']}\n";
        echo "Input: " . json_encode($toolUse['input']) . "\n";
    }
}
```

## Web Search Tool

Enable Claude to search the web:

```php
use WpAi\Anthropic\Tools\WebSearchTool;

$webSearch = WebSearchTool::make()
    ->allowedDomains(['docs.anthropic.com'])
    ->blockedDomains(['spam.com'])
    ->maxUses(5)
    ->location('US', 'CA', 'San Francisco');

$response = $anthropic->messages()
    ->model(Models::CLAUDE_3_5_HAIKU)
    ->maxTokens(1024)
    ->tools([$webSearch->toArray()])
    ->messages([
        ['role' => 'user', 'content' => 'What is the latest news about AI?']
    ])
    ->create();
```

## Prompt Caching

Reduce costs by caching frequently used content:

```php
use WpAi\Anthropic\Content\TextContent;

$cachedContent = TextContent::make($largeContext)->withCache('5m');  // or '1h'

$response = $anthropic->messages()
    ->model(Models::CLAUDE_3_5_HAIKU)
    ->maxTokens(1024)
    ->messages([
        [
            'role' => 'user',
            'content' => [
                $cachedContent->toArray(),
                ['type' => 'text', 'text' => 'Summarize the above']
            ]
        ]
    ])
    ->create();

// Check cache usage
if ($response->usage->usedCache()) {
    echo "Cache read tokens: " . $response->usage->cacheReadInputTokens;
    echo "Cache creation tokens: " . $response->usage->cacheCreationInputTokens;
}
```

## Token Counting

Count tokens before making a request:

```php
$count = $anthropic->countTokens()
    ->model(Models::CLAUDE_3_5_HAIKU)
    ->messages([
        ['role' => 'user', 'content' => 'Hello, how are you?']
    ])
    ->system('You are helpful')
    ->tools($tools)  // Optional
    ->count();

echo "Input tokens: " . $count->inputTokens;
```

## Batch Processing

Process multiple requests asynchronously at 50% discount:

```php
// Create a batch
$batch = $anthropic->batches()->create([
    [
        'custom_id' => 'request-1',
        'params' => [
            'model' => Models::CLAUDE_3_5_HAIKU,
            'max_tokens' => 1024,
            'messages' => [
                ['role' => 'user', 'content' => 'Hello']
            ]
        ]
    ],
    [
        'custom_id' => 'request-2',
        'params' => [
            'model' => Models::CLAUDE_3_5_HAIKU,
            'max_tokens' => 1024,
            'messages' => [
                ['role' => 'user', 'content' => 'Goodbye']
            ]
        ]
    ]
]);

echo "Batch ID: " . $batch->id;
echo "Status: " . $batch->processingStatus;

// Check batch status
$batch = $anthropic->batches()->retrieve($batch->id);

if ($batch->isComplete()) {
    $results = $anthropic->batches()->results($batch->id);
    foreach ($results as $result) {
        echo $result['custom_id'] . ": " . $result['result']['message']['content'][0]['text'];
    }
}

// List all batches
$batches = $anthropic->batches()->list(['limit' => 10]);

// Cancel a batch
$anthropic->batches()->cancel($batch->id);
```

## Beta Features

Enable beta features with headers:

```php
$response = $anthropic->messages()
    ->withBeta('files-api-2025-04-14')
    ->withBeta('advanced-tool-use-2025-11-20')
    ->model(Models::CLAUDE_3_5_HAIKU)
    ->maxTokens(1024)
    ->messages($messages)
    ->create();
```

## Error Handling

```php
use WpAi\Anthropic\Exceptions\ClientException;
use WpAi\Anthropic\Exceptions\StreamException;

try {
    $response = $anthropic->messages()->create($options);
} catch (ClientException $e) {
    // Handle API errors (rate limits, invalid requests, etc.)
    echo "API Error: " . $e->getMessage();
    echo "Status Code: " . $e->getCode();
} catch (StreamException $e) {
    // Handle streaming-specific errors
    echo "Stream Error: " . $e->getMessage();
}
```

## Laravel Integration

This library includes Laravel support via a service provider and facade.

### Configuration

Publish the config file:

```sh
php artisan vendor:publish --provider="WpAi\Anthropic\Providers\AnthropicServiceProvider"
```

Add to your `.env`:

```
ANTHROPIC_API_KEY=your-api-key
ANTHROPIC_API_VERSION=2023-06-01
```

### Usage

```php
use WpAi\Anthropic\Facades\Anthropic;
use WpAi\Anthropic\Models;

$response = Anthropic::messages()
    ->model(Models::CLAUDE_3_5_HAIKU)
    ->maxTokens(1024)
    ->messages([
        ['role' => 'user', 'content' => 'Hello, Claude!']
    ])
    ->create();

echo $response->getText();
```

## Testing

Run the test suite:

```sh
# Unit tests
./vendor/bin/phpunit tests/Unit/

# Integration tests (requires ANTHROPIC_API_KEY in .env)
./vendor/bin/phpunit tests/Integration/
```

## License

MIT
