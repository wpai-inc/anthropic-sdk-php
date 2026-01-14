<?php

namespace WpAi\Anthropic\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WpAi\Anthropic\Models;

class ModelsTest extends TestCase
{
    public function test_model_constants_are_valid_strings(): void
    {
        $this->assertIsString(Models::CLAUDE_OPUS_4_5);
        $this->assertIsString(Models::CLAUDE_SONNET_4_5);
        $this->assertIsString(Models::CLAUDE_HAIKU_4_5);
        $this->assertIsString(Models::CLAUDE_3_5_HAIKU);
    }

    public function test_all_returns_array_of_models(): void
    {
        $models = Models::all();

        $this->assertIsArray($models);
        $this->assertNotEmpty($models);
        $this->assertContains(Models::CLAUDE_OPUS_4_5, $models);
        $this->assertContains(Models::CLAUDE_SONNET_4_5, $models);
        $this->assertContains(Models::CLAUDE_HAIKU_4_5, $models);
    }

    public function test_is_valid_returns_true_for_valid_models(): void
    {
        $this->assertTrue(Models::isValid(Models::CLAUDE_OPUS_4_5));
        $this->assertTrue(Models::isValid(Models::CLAUDE_SONNET_4_5));
        $this->assertTrue(Models::isValid('claude-3-5-haiku-20241022'));
    }

    public function test_is_valid_returns_false_for_invalid_models(): void
    {
        $this->assertFalse(Models::isValid('invalid-model'));
        $this->assertFalse(Models::isValid(''));
        $this->assertFalse(Models::isValid('gpt-4'));
    }

    public function test_recommended_returns_string(): void
    {
        $this->assertIsString(Models::recommended());
        $this->assertTrue(Models::isValid(Models::recommended()));
    }

    public function test_fast_returns_haiku_model(): void
    {
        $fast = Models::fast();
        $this->assertIsString($fast);
        $this->assertStringContainsString('haiku', $fast);
    }

    public function test_best_returns_opus_model(): void
    {
        $best = Models::best();
        $this->assertIsString($best);
        $this->assertStringContainsString('opus', $best);
    }
}
