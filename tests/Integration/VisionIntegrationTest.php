<?php

namespace WpAi\Anthropic\Tests\Integration;

use PHPUnit\Framework\TestCase;
use WpAi\Anthropic\AnthropicAPI;
use WpAi\Anthropic\Content\Message;
use WpAi\Anthropic\Models;

class VisionIntegrationTest extends TestCase
{
    private ?AnthropicAPI $api = null;

    protected function setUp(): void
    {
        $apiKey = getenv('ANTHROPIC_API_KEY') ?: ($_ENV['ANTHROPIC_API_KEY'] ?? null);

        if (empty($apiKey)) {
            $envFile = dirname(__DIR__, 2) . '/.env';
            if (file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (str_starts_with($line, 'ANTHROPIC_API_KEY=')) {
                        $apiKey = substr($line, strlen('ANTHROPIC_API_KEY='));
                        break;
                    }
                }
            }
        }

        if (empty($apiKey)) {
            $this->markTestSkipped('ANTHROPIC_API_KEY not set');
        }

        $this->api = new AnthropicAPI($apiKey);
    }

    public function test_image_analysis_with_base64(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension not available');
        }

        // Create a tiny 10x10 red PNG for testing
        $image = imagecreatetruecolor(10, 10);
        $red = imagecolorallocate($image, 255, 0, 0);
        imagefill($image, 0, 0, $red);

        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        $base64 = base64_encode($imageData);

        $response = $this->api->messages()
            ->model(Models::CLAUDE_3_5_HAIKU)
            ->maxTokens(50)
            ->messages([
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => 'What color is this image? Reply with just the color name.'],
                        [
                            'type' => 'image',
                            'source' => [
                                'type' => 'base64',
                                'media_type' => 'image/png',
                                'data' => $base64,
                            ]
                        ]
                    ]
                ]
            ])
            ->create();

        $this->assertStringContainsStringIgnoringCase('red', $response->content[0]['text']);
    }

    public function test_image_analysis_with_content_helper(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension not available');
        }

        // Create a tiny green PNG
        $image = imagecreatetruecolor(10, 10);
        $green = imagecolorallocate($image, 0, 255, 0);
        imagefill($image, 0, 0, $green);

        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        $base64 = base64_encode($imageData);

        $message = Message::user()
            ->text('What color is this square? Reply with just the color.')
            ->imageBase64($base64, 'image/png');

        $response = $this->api->messages()
            ->model(Models::CLAUDE_3_5_HAIKU)
            ->maxTokens(50)
            ->messages([$message->toArray()])
            ->create();

        $this->assertStringContainsStringIgnoringCase('green', $response->content[0]['text']);
    }

    public function test_image_analysis_with_blue_square(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension not available');
        }

        // Create a tiny blue PNG
        $image = imagecreatetruecolor(10, 10);
        $blue = imagecolorallocate($image, 0, 0, 255);
        imagefill($image, 0, 0, $blue);

        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        $base64 = base64_encode($imageData);

        $message = Message::user()
            ->text('What color is this image? Reply with only the color name, nothing else.')
            ->imageBase64($base64, 'image/png');

        $response = $this->api->messages()
            ->model(Models::CLAUDE_3_5_HAIKU)
            ->maxTokens(50)
            ->messages([$message->toArray()])
            ->create();

        $this->assertStringContainsStringIgnoringCase('blue', $response->content[0]['text']);
    }
}
