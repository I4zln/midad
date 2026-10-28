<?php

namespace App\Http\Controllers;

use App\Models\SpecialProfileMessage;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    // Why a guest was sent to log in, keyed by the ?reason= value used on guest action links.
    private const LOGIN_REASONS = [
        'like' => 'سجّل الدخول لتتمكن من الإعجاب بالاقتباس، وسنعيدك إلى حيث كنت.',
        'save' => 'سجّل الدخول لحفظ الاقتباس في محفوظاتك، وسنعيدك إلى حيث كنت.',
        'add' => 'سجّل الدخول لإضافة اقتباسك إلى المكتبة.',
        'comment' => 'سجّل الدخول لتشارك في التعليقات، وسنعيدك إلى الاقتباس نفسه.',
        'follow' => 'سجّل الدخول لمتابعة هذا القارئ، وسنعيدك إلى ملفه.',
    ];

    public function loginForm(Request $request): View
    {
        $this->rememberIntendedPage($request);

        return view('auth.login', ['reason' => $this->loginReason($request)]);
    }

    public function login(Request $request): RedirectResponse
    {
        // Older forms posted the address as "identifier".
        if (! $request->filled('email') && $request->filled('identifier')) {
            $request->merge(['email' => $request->input('identifier')]);
        }
        // Addresses are stored lowercase; accept them however they are typed.
        $request->merge(['email' => PasswordResetController::normalizeEmail($request->input('email'))]);

        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);
        $key = Str::lower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'محاولات كثيرة. انتظر '.RateLimiter::availableIn($key).' ثانية ثم حاول مجددًا.',
            ]);
        }

        if (! Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages([
                'password' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة. تحقّق منهما وحاول مجددًا.',
            ]);
        }

        $user = Auth::user();
        RateLimiter::clear($key);
        $request->session()->regenerate();

        // The special card needs a verified address, but the rest of the site never waits for it: go to the profile, which explains.
        // Until the private message has been shown, the special account goes straight to its profile, where it appears.
        if ($this->isSpecialProfileEmail($user->email)
            && (! $user->hasVerifiedEmail() || ! SpecialProfileMessage::query()->where('user_id', $user->id)->whereNotNull('shown_at')->exists())) {
            return redirect()->route('profile.show', $user)->with('status', 'مرحبًا بعودتك يا '.$user->name.'. لديك رسالة خاصة في ملفك.');
        }

        return redirect()->intended(route('home'))->with('status', 'مرحبًا بعودتك يا '.$user->name.'.');
    }

    public function registerForm(Request $request): View
    {
        $this->rememberIntendedPage($request);

        return view('auth.register', ['reason' => $this->loginReason($request)]);
    }

    public function register(Request $request): RedirectResponse
    {
        // Lowercase before the unique check, so "Name@Mail.com" cannot become a second account.
        $request->merge(['email' => PasswordResetController::normalizeEmail($request->input('email'))]);
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:50'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'password_confirmation' => ['required', 'same:password'],
        ], [
            'password_confirmation.same' => 'تأكيد كلمة المرور لا يطابق كلمة المرور.',
        ]);

        $user = User::query()->create(collect($validated)->except('password_confirmation')->all());
        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        if ($this->isSpecialProfileEmail($user->email)) {
            $this->sendVerificationEmail($user);

            return redirect()->route('profile.show', $user)->with('status', 'أهلًا بك في مِداد يا '.$user->name.'. لديك رسالة خاصة في ملفك.');
        }

        // A guest who signed up from an action (like, save…) goes back there; everyone else writes a first quote.
        return redirect()->intended(route('quotes.create'))->with('status', 'أهلًا بك في مِداد يا '.$user->name.'.');
    }

    // Store ?next= as the post-login destination, but only for paths on this site.
    private function rememberIntendedPage(Request $request): void
    {
        $next = $request->query('next');
        if (! is_string($next) || $next === '') {
            return;
        }

        $parts = parse_url($next);
        $path = $parts['path'] ?? '';
        $sameHost = ! isset($parts['host']) || $parts['host'] === $request->getHost();
        if ($parts === false || ! $sameHost || ! str_starts_with($path, '/') || str_starts_with($path, '//') || isset($parts['user'])) {
            return;
        }

        $request->session()->put('url.intended', url($path.(isset($parts['query']) ? '?'.$parts['query'] : '').(isset($parts['fragment']) ? '#'.$parts['fragment'] : '')));
    }

    private function loginReason(Request $request): ?string
    {
        $reason = self::LOGIN_REASONS[(string) $request->query('reason')] ?? null;

        return $reason ?? ($request->session()->has('url.intended') ? 'هذه الصفحة تحتاج إلى تسجيل الدخول، وسنعيدك إليها مباشرة بعده.' : null);
    }

    public function verificationNotice(): View
    {
        return view('auth.verify-email');
    }

    public function verifyEmail(EmailVerificationRequest $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));

            if ($this->isSpecialProfileEmail($user->email)) {
                $recipients = config('ethraa.signup_notification_emails', []);
                if (config('mail.default') !== 'log' && $recipients !== []) {
                    try {
                        Mail::to($recipients)->send(new \App\Mail\SignupVerifiedNotice($user->name));
                    } catch (\Throwable) {
                        Log::warning('Midad signup notice could not be delivered.');
                    }
                }
            }
        }

        return redirect()->route('profile.show', $user)->with('status', 'تم تأكيد بريدك الإلكتروني.');
    }

    public function resendVerification(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('profile.show', $request->user());
        }

        return back()->with('status', $this->sendVerificationEmail($request->user()) ? self::LINK_SENT : self::LINK_FAILED);
    }

    public const LINK_SENT = 'أرسلنا رابط التأكيد إلى بريدك. افتحه لتظهر رسالتك الخاصة.';
    public const LINK_FAILED = 'تعذّر إرسال رابط التأكيد الآن لأن خدمة البريد غير متصلة. حاول لاحقًا.';

    private function isSpecialProfileEmail(string $email): bool
    {
        $target = (string) config('ethraa.special_profile_email', '');

        return $target !== '' && hash_equals(mb_strtolower($target), mb_strtolower($email));
    }

    private function sendVerificationEmail(User $user): bool
    {
        if (! $this->isSpecialProfileEmail($user->email) || config('mail.default') === 'log') {
            return false;
        }

        try {
            $user->sendEmailVerificationNotification();
            return true;
        } catch (\Throwable) {
            Log::warning('Midad verification email could not be delivered.');
            return false;
        }
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'تم تسجيل الخروج.');
    }
}
