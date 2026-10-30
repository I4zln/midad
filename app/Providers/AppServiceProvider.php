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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\App\Services\FeedRanker::class, fn () => \App\Services\FeedRanker::fromConfig());
        // HTTP client for Brevo's mail API; a short timeout so an outage cannot hang sign-up or a password reset.
        $this->app->bind('midad.brevo.http', fn () => HttpClient::create(['timeout' => 10]));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Quote::class, QuotePolicy::class);
        Gate::define('admin', fn (User $user): bool => (bool) $user->is_admin);
        // Named limits keep their own counters: plain throttle:N,1 shares one counter per visitor across routes,
        // so scrolling «اكتشف» would otherwise use up the sign-in and sign-up allowance.
        RateLimiter::for('discover-feed', fn (Request $request) => Limit::perMinute(60)->by('discover-feed|'.($request->user()?->id ?? $request->ip())));
        RateLimiter::for('discover-mode', fn (Request $request) => Limit::perMinute(30)->by('discover-mode|'.($request->user()?->id ?? $request->ip())));
        // Members' own actions, per account: far above what a person does, low enough to stop a script flooding the site.
        RateLimiter::for('reactions', fn (Request $request) => Limit::perMinute(60)->by('reactions|'.($request->user()?->id ?? $request->ip())));
        RateLimiter::for('comments', fn (Request $request) => Limit::perMinute(10)->by('comments|'.($request->user()?->id ?? $request->ip())));
        RateLimiter::for('follows', fn (Request $request) => Limit::perMinute(20)->by('follows|'.($request->user()?->id ?? $request->ip())));
        RateLimiter::for('profile', fn (Request $request) => Limit::perMinute(10)->by('profile|'.($request->user()?->id ?? $request->ip())));
        Paginator::defaultView('pagination.midad');
        // MAIL_MAILER=brevo sends through Brevo's HTTPS API with BREVO_API_KEY (hosts that block SMTP, such as Render's free plan).
        Mail::extend('brevo', fn () => (new BrevoTransportFactory(null, $this->app->make('midad.brevo.http')))
            ->create(new Dsn('brevo+api', 'default', (string) config('services.brevo.key'))));

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
