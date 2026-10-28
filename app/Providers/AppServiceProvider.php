<?php

namespace App\Providers;

use App\Listeners\SendWelcomeEmail;
use App\Models\User;
use App\Models\Quote;
use App\Policies\QuotePolicy;
use Illuminate\Auth\Events\Registered;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\App\Services\FeedRanker::class, fn () => \App\Services\FeedRanker::fromConfig());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Quote::class, QuotePolicy::class);
        Gate::define('admin', fn (User $user): bool => $user->is_admin);
        // Named limits keep their own counters: plain throttle:N,1 shares one counter per visitor across routes,
        // so scrolling «اكتشف» would otherwise use up the sign-in and sign-up allowance.
        RateLimiter::for('discover-feed', fn (Request $request) => Limit::perMinute(60)->by('discover-feed|'.($request->user()?->id ?? $request->ip())));
        RateLimiter::for('discover-mode', fn (Request $request) => Limit::perMinute(30)->by('discover-mode|'.($request->user()?->id ?? $request->ip())));
        Paginator::defaultView('pagination.midad');

        if ($this->app->environment('production') && config('app.force_https')) {
            URL::forceScheme('https');
        }

        // Laravel attaches SendEmailVerificationNotification to Registered, which would email every new member a
        // verification link (and fail registration if SMTP is down). Verification here is opt-in and sent by
        // AuthController; a new account gets only the welcome email.
        $this->app->booted(function () {
            Event::forget(Registered::class);
            Event::listen(Registered::class, SendWelcomeEmail::class);
        });
    }
}
