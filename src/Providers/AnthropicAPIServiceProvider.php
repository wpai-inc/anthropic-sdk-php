<?php

namespace WpAi\Anthropic\Providers;

use WpAi\Anthropic\AnthropicAPI;
use Illuminate\Support\ServiceProvider;

class AnthropicAPIServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(AnthropicAPI::class, function ($app) {
            return new AnthropicAPI(config('services.anthropic.api_key'));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
