<?php

namespace App\Providers;

use App\Modules\Assistant\Gateway\CompatibleChatCompletionsGateway;
use App\Modules\Assistant\Gateway\ModelGateway;
use App\Modules\Operations\Presenters\AdminNavigation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ModelGateway::class, CompatibleChatCompletionsGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        View::composer('components.layouts.admin', fn ($view) => $view->with('navItems', AdminNavigation::items()));
    }

    /**
     * Every guest message costs an LLM round trip, so it is limited per
     * conversation and, as a backstop against rotating tokens, per address.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('barista-start', fn (Request $request) => Limit::perMinute(10)->by('start:'.$request->ip()));

        RateLimiter::for('barista-message', fn (Request $request) => [
            Limit::perMinute(12)->by('conversation:'.($request->input('guest_token') ?: $request->ip())),
            Limit::perMinute(40)->by('ip:'.$request->ip()),
        ]);

        RateLimiter::for('barista-history', fn (Request $request) => Limit::perMinute(60)->by('history:'.$request->ip()));
    }
}
