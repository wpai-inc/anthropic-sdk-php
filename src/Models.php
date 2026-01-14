<?php

namespace WpAi\Anthropic;

/**
 * Constants for all available Claude models.
 *
 * @see https://docs.anthropic.com/en/docs/about-claude/models
 */
class Models
{
    // Claude 4.5 Series (Latest - Released 2025)
    public const CLAUDE_OPUS_4_5 = 'claude-opus-4-5-20251101';
    public const CLAUDE_OPUS_4_5_LATEST = 'claude-opus-4-5-latest';
    public const CLAUDE_SONNET_4_5 = 'claude-sonnet-4-5-20250929';
    public const CLAUDE_SONNET_4_5_LATEST = 'claude-sonnet-4-5-latest';
    public const CLAUDE_HAIKU_4_5 = 'claude-haiku-4-5-20251001';
    public const CLAUDE_HAIKU_4_5_LATEST = 'claude-haiku-4-5-latest';

    // Claude 4 Series
    public const CLAUDE_OPUS_4 = 'claude-opus-4-20250514';
    public const CLAUDE_OPUS_4_LATEST = 'claude-opus-4-latest';
    public const CLAUDE_SONNET_4 = 'claude-sonnet-4-20250514';
    public const CLAUDE_SONNET_4_LATEST = 'claude-sonnet-4-latest';

    // Claude 3.7 Series
    public const CLAUDE_3_7_SONNET = 'claude-3-7-sonnet-20250219';
    public const CLAUDE_3_7_SONNET_LATEST = 'claude-3-7-sonnet-latest';

    // Claude 3.5 Series
    public const CLAUDE_3_5_SONNET = 'claude-3-5-sonnet-20241022';
    public const CLAUDE_3_5_SONNET_LATEST = 'claude-3-5-sonnet-latest';
    public const CLAUDE_3_5_HAIKU = 'claude-3-5-haiku-20241022';
    public const CLAUDE_3_5_HAIKU_LATEST = 'claude-3-5-haiku-latest';

    // Claude 3 Series (Legacy)
    public const CLAUDE_3_OPUS = 'claude-3-opus-20240229';
    public const CLAUDE_3_OPUS_LATEST = 'claude-3-opus-latest';
    public const CLAUDE_3_SONNET = 'claude-3-sonnet-20240229';
    public const CLAUDE_3_HAIKU = 'claude-3-haiku-20240307';

    /**
     * Get all available model identifiers.
     */
    public static function all(): array
    {
        return [
            // 4.5 Series
            self::CLAUDE_OPUS_4_5,
            self::CLAUDE_OPUS_4_5_LATEST,
            self::CLAUDE_SONNET_4_5,
            self::CLAUDE_SONNET_4_5_LATEST,
            self::CLAUDE_HAIKU_4_5,
            self::CLAUDE_HAIKU_4_5_LATEST,
            // 4 Series
            self::CLAUDE_OPUS_4,
            self::CLAUDE_OPUS_4_LATEST,
            self::CLAUDE_SONNET_4,
            self::CLAUDE_SONNET_4_LATEST,
            // 3.7 Series
            self::CLAUDE_3_7_SONNET,
            self::CLAUDE_3_7_SONNET_LATEST,
            // 3.5 Series
            self::CLAUDE_3_5_SONNET,
            self::CLAUDE_3_5_SONNET_LATEST,
            self::CLAUDE_3_5_HAIKU,
            self::CLAUDE_3_5_HAIKU_LATEST,
            // 3 Series
            self::CLAUDE_3_OPUS,
            self::CLAUDE_3_OPUS_LATEST,
            self::CLAUDE_3_SONNET,
            self::CLAUDE_3_HAIKU,
        ];
    }

    /**
     * Check if a model identifier is valid.
     */
    public static function isValid(string $model): bool
    {
        return in_array($model, self::all(), true);
    }

    /**
     * Get recommended model for general use (best balance of cost/performance).
     */
    public static function recommended(): string
    {
        return self::CLAUDE_SONNET_4_5_LATEST;
    }

    /**
     * Get the cheapest/fastest model.
     */
    public static function fast(): string
    {
        return self::CLAUDE_HAIKU_4_5_LATEST;
    }

    /**
     * Get the most capable model.
     */
    public static function best(): string
    {
        return self::CLAUDE_OPUS_4_5_LATEST;
    }
}
