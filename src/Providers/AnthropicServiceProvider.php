<?php

namespace WpAi\Anthropic\Providers;

use WpAi\Anthropic\AnthropicAPI;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;

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
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/anthropic.php' => Config::get('anthropic.php'),
            ], 'config');
        }
    }
}
