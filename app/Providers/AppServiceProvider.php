<?php

namespace App\Providers;

use App\Listeners\SendWelcomeEmail;
use App\Models\User;
use App\Models\Quote;
use App\Policies\QuotePolicy;
use Illuminate\Auth\Events\Registered;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Quote::class, QuotePolicy::class);
        Gate::define('admin', fn (User $user): bool => $user->is_admin);
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
