<?php

namespace WpAi\Anthropic\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use WpAi\Anthropic\AnthropicAPI;

class AnthropicServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(AnthropicAPI::class, function ($app) {
            return new AnthropicAPI(
                Config::get('anthropic.api_key'),
                Config::get('anthropic.api_version')
            );
        });

        $this->mergeConfigFrom(
            __DIR__.'/../config/anthropic.php', 'anthropic'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/anthropic.php' => config_path('anthropic.php'),
        ]);
    }
}
