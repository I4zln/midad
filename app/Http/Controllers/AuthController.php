<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function loginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        if (! $request->filled('identifier') && $request->filled('email')) {
            $request->merge(['identifier' => $request->input('email')]);
        }

        $credentials = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);
        $key = Str::lower($request->input('identifier')).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'identifier' => 'محاولات كثيرة. حاول مجددًا بعد دقيقة.',
            ]);
        }

        $user = User::query()
            ->where('username', $credentials['identifier'])
            ->orWhere('email', $credentials['identifier'])
            ->first();

        if (! $user || ! Auth::attempt(['email' => $user->email, 'password' => $credentials['password']], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages([
                'identifier' => 'بيانات الدخول غير صحيحة.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        if ($this->isSpecialProfileEmail($user->email) && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')->with(
                'status',
                $this->sendVerificationEmail($user) ? 'verification-link-sent' : 'verification-link-failed',
            );
        }

        return redirect()->intended(route('home'))->with('status', 'مرحبًا بعودتك إلى أثر.');
    }

    public function registerForm(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:50'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::query()->create($validated);
        Auth::login($user);
        $request->session()->regenerate();

        if ($this->isSpecialProfileEmail($user->email)) {
            return redirect()->route('verification.notice')->with(
                'status',
                $this->sendVerificationEmail($user) ? 'verification-link-sent' : 'verification-link-failed',
            );
        }

        return redirect()->route('quotes.create')->with('status', 'أهلًا بك. أضف اقتباسك الأول الآن.');
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

        return back()->with('status', $this->sendVerificationEmail($request->user())
            ? 'verification-link-sent'
            : 'verification-link-failed');
    }

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
